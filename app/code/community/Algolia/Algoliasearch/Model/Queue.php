<?php

class Algolia_Algoliasearch_Model_Queue
{
    const SUCCESS_LOG = 'algoliasearch_queue_log.txt';
    const ERROR_LOG = 'algoliasearch_queue_errors.log';

    const UNLOCK_STACKED_JOBS_AFTER_MINUTES = 15;

    protected $table;
    protected $logTable;
    protected $archiveTable;

    /** @var Magento_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var Algolia_Algoliasearch_Helper_Config */
    protected $config;

    /** @var Algolia_Algoliasearch_Helper_Logger */
    protected $logger;

    protected $maxSingleJobDataSize;

    private $staticJobMethods = array(
        'saveSettings',
        'moveProductsTmpIndex',
        'deleteProductsStoreIndices',
        'removeCategories',
        'deleteCategoriesStoreIndices',
        'moveStoreSuggestionIndex',
    );

    private $noOfFailedJobs = 0;

    private $logRecord = array();

    public function __construct()
    {
        /** @var Mage_Core_Model_Resource $coreResource */
        $coreResource = Mage::getSingleton('core/resource');

        $this->table = $coreResource->getTableName('algoliasearch/queue');
        $this->logTable = $coreResource->getTableName('algoliasearch/queue_log');
        $this->archiveTable = $coreResource->getTableName('algoliasearch/queue_archive');

        $this->db = $coreResource->getConnection('core_write');

        $this->config = Mage::helper('algoliasearch/config');
        $this->logger = Mage::helper('algoliasearch/logger');

        $this->maxSingleJobDataSize = $this->config->getNumberOfElementByPage();
    }

    public function add($class, $method, $data, $data_size)
    {
        // Insert a row for the new job
        $this->db->insert($this->table, array(
            'created'   => date('Y-m-d H:i:s'),
            'class'     => $class,
            'method'    => $method,
            'data'      => json_encode($data),
            'data_size' => $data_size,
            'pid'       => null,
        ));
    }

    /**
     * Return the average processing time for the 2 last two days
     * (null if there was less than 100 runs with processed jobs)
     *
     * @throws \Zend_Db_Statement_Exception
     *
     * @return float|null
     */
    public function getAverageProcessingTime()
    {
        $data = $this->db->query(
            $this->db->select()
                ->from($this->logTable, array('number_of_runs' => 'COUNT(duration)', 'average_time' => 'AVG(duration)'))
                ->where('processed_jobs > 0 AND with_empty_queue = 0 AND started >= (CURDATE() - INTERVAL 2 DAY)')
        );
        $result = $data->fetch();

        return (int) $result['number_of_runs'] >= 100 && isset($result['average_time']) ?
            (float) $result['average_time'] :
            null;
    }

    public function runCron($nbJobs = null, $force = false)
    {
        if (!$this->config->isQueueActive() && $force === false) {
            return;
        }

        $this->clearOldLogRecords();
        $this->unlockStackedJobs();

        $this->logRecord = array(
            'started' => date('Y-m-d H:i:s'),
            'processed_jobs' => 0,
            'with_empty_queue' => 0,
        );

        $started = time();

        if ($nbJobs === null) {
            $nbJobs = $this->config->getNumberOfJobToRun();
            if (getenv('EMPTY_QUEUE') && getenv('EMPTY_QUEUE') == '1') {
                $nbJobs = -1;

                $this->logRecord['with_empty_queue'] = 1;
            }
        }

        $this->run($nbJobs);

        $this->logRecord['duration'] = time() - $started;

        $this->db->insert($this->logTable, $this->logRecord);

        $this->db->closeConnection();
    }

    public function run($maxJobs)
    {
        $pid = getmypid();

        $jobs = $this->getJobs($maxJobs, $pid);

        if (empty($jobs)) {
            return;
        }

        // Run all reserved jobs
        foreach ($jobs as $job) {
            // If there are some failed jobs before move, we want to skip the move
            // as most probably not all products have prices reindexed
            // and therefore are not indexed yet in TMP index
            if ($job['method'] === 'moveProductsTmpIndex' && $this->noOfFailedJobs > 0) {
                // Set pid to NULL so it's not deleted after
                $this->db->query("UPDATE {$this->db->quoteIdentifier($this->table, true)} SET pid = NULL, locked_at = NULL WHERE job_id = ".$job['job_id']);

                continue;
            }

            try {
                $model = Mage::getSingleton($job['class']);
                $method = $job['method'];
                $model->{$method}(new Varien_Object($job['data']));

                // Delete one by one
                $this->db->delete($this->table, array('job_id IN (?)' => $job['merged_ids']));


                $this->logRecord['processed_jobs'] += count($job['merged_ids']);
            } catch (\Exception $e) {
                $this->noOfFailedJobs++;

                // Log error information
                $logMessage = 'Queue processing ' . $job['pid'] . ' [KO]: 
                     Class: ' . $job['class'] . ', 
                     Method: ' . $job['method'] . ', 
                     Parameters: ' . json_encode($job['data']);
                $this->logger->log($logMessage);

                $logMessage = date('c') . ' ERROR: ' . get_class($e) . ': 
                    ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() .
                    "\nStack trace:\n" . $e->getTraceAsString();
                $this->logger->log($logMessage);

                // Increment retries, set the job ID back to NULL
                $updateQuery = "UPDATE {$this->db->quoteIdentifier($this->table, true)} 
                  SET pid = NULL, locked_at = NULL, retries = retries + 1 , error_log = '" . addslashes($logMessage) . "'
                  WHERE job_id IN (".implode(', ', (array) $job['merged_ids']).")";
                $this->db->query($updateQuery);
            }
        }

        $isFullReindex = ($maxJobs === -1);
        if ($isFullReindex) {
            $this->run(-1);

            return;
        }
    }

    private function archiveFailedJobs($whereClause)
    {
        $this->db->query(
            "INSERT INTO {$this->archiveTable} (pid, class, method, data, error_log, data_size, created_at) 
                  SELECT pid, class, method, data, error_log, data_size, NOW()
                  FROM {$this->table}
                  WHERE " . $whereClause
        );
    }

    private function getJobs($maxJobs, $pid)
    {
        // Clear jobs with crossed max retries count
        $retryLimit = $this->config->getRetryLimit();
        if ($retryLimit > 0) {
            $where = $this->db->quoteInto('retries >= ?', $retryLimit);
            $this->archiveFailedJobs($where);
            $this->db->delete($this->table, $where);
        } else {
            $this->archiveFailedJobs('retries > max_retries');
            $this->db->delete($this->table, 'retries > max_retries');
        }

        $jobs = array();

        $limit = $maxJobs = ($maxJobs === -1) ? $this->config->getNumberOfJobToRun() : $maxJobs;
        $offset = 0;

        $maxBatchSize = $this->config->getNumberOfElementByPage() * $limit;
        $actualBatchSize = 0;

        try {
            $this->db->beginTransaction();

            while ($actualBatchSize < $maxBatchSize) {
                $data = $this->db->query($this->db->select()->from($this->table, '*')->where('pid IS NULL')
                                                  ->order(array('job_id'))->limit($limit, $offset)
                                                  ->forUpdate());
                $rawJobs = $data->fetchAll();
                $rowsCount = count($rawJobs);

                if ($rowsCount <= 0) {
                    break;
                }

                $rawJobs = $this->prepareJobs($rawJobs);
                $rawJobs = array_merge($jobs, $rawJobs);
                $rawJobs = $this->mergeJobs($rawJobs);

                $rawJobsCount = count($rawJobs);

                $offset += $limit;
                $limit = max(0, $maxJobs - $rawJobsCount);

                // $jobs will always be completely set from $rawJobs
                // Without resetting not-merged jobs would be stacked
                $jobs = array();

                if (count($rawJobs) == $maxJobs) {
                    $jobs = $rawJobs;
                    break;
                }

                foreach ($rawJobs as $job) {
                    $jobSize = (int) $job['data_size'];

                    if ($actualBatchSize + $jobSize <= $maxBatchSize || empty($jobs)) {
                        $jobs[] = $job;
                        $actualBatchSize += $jobSize;
                    } else {
                        break 2;
                    }
                }
            }

            $this->lockJobs($jobs);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->db->closeConnection();

            throw $e;
        }

        return $jobs;
    }

    private function prepareJobs($jobs)
    {
        foreach ($jobs as &$job) {
            $job['data'] = json_decode($job['data'], true);
            $job['merged_ids'][] = $job['job_id'];
        }

        return $jobs;
    }

    protected function mergeJobs($oldJobs)
    {
        $oldJobs = $this->sortJobs($oldJobs);

        $jobs = array();

        $currentJob = array_shift($oldJobs);
        $nextJob = null;

        while ($currentJob !== null) {
            if (count($oldJobs) > 0) {
                $nextJob = array_shift($oldJobs);

                if ($this->mergeable($currentJob, $nextJob)) {
                    // Use the job_id of the the very last job to properly mark processed jobs
                    $currentJob['job_id'] = max((int) $currentJob['job_id'], (int) $nextJob['job_id']);

                    $currentJob['merged_ids'][] = $nextJob['job_id'];

                    if (isset($currentJob['data']['product_ids'])) {
                        $currentJob['data']['product_ids'] = array_merge($currentJob['data']['product_ids'], $nextJob['data']['product_ids']);
                    } elseif (isset($currentJob['data']['category_ids'])) {
                        $currentJob['data']['category_ids'] = array_merge($currentJob['data']['category_ids'], $nextJob['data']['category_ids']);
                    }

                    continue;
                }
            } else {
                $nextJob = null;
            }

            if (isset($currentJob['data']['product_ids'])) {
                $currentJob['data']['product_ids'] = array_unique($currentJob['data']['product_ids']);
                $currentJob['data_size'] = count($currentJob['data']['product_ids']);
            }

            if (isset($currentJob['data']['category_ids'])) {
                $currentJob['data']['category_ids'] = array_unique($currentJob['data']['category_ids']);
                $currentJob['data_size'] = count($currentJob['data']['category_ids']);
            }

            $jobs[] = $currentJob;
            $currentJob = $nextJob;
        }

        return $jobs;
    }

    private function sortJobs($oldJobs)
    {
        $sortedJobs = array();

        $tempSortableJobs = array();
        foreach ($oldJobs as $job) {
            if (in_array($job['method'], $this->staticJobMethods, true)) {
                $sortedJobs = $this->stackSortedJobs($sortedJobs, $tempSortableJobs, $job);
                $tempSortableJobs = array();

                continue;
            }

            // This one is needed for proper sorting
            if (isset($job['data']['store_id'])) {
                $job['store_id'] = $job['data']['store_id'];
            }

            $tempSortableJobs[] = $job;
        }

        $sortedJobs = $this->stackSortedJobs($sortedJobs, $tempSortableJobs);

        return $sortedJobs;
    }

    private function stackSortedJobs($sortedJobs, $tempSortableJobs, $job = null)
    {
        if (!empty($tempSortableJobs)) {
            $tempSortableJobs = $this->arrayMultisort($tempSortableJobs, 'class', SORT_ASC, 'method', SORT_ASC, 'store_id', SORT_ASC, 'job_id', SORT_ASC);
        }

        $sortedJobs = array_merge($sortedJobs, $tempSortableJobs);

        if ($job !== null) {
            $sortedJobs = array_merge($sortedJobs, array($job));
        }

        return $sortedJobs;
    }

    private function mergeable($j1, $j2)
    {
        if ($j1['class'] !== $j2['class']) {
            return false;
        }

        if ($j1['method'] !== $j2['method']) {
            return false;
        }

        if (isset($j1['data']['store_id']) && isset($j2['data']['store_id']) && $j1['data']['store_id'] !== $j2['data']['store_id']) {
            return false;
        }

        if ((!isset($j1['data']['product_ids']) || count($j1['data']['product_ids']) <= 0) && (!isset($j1['data']['category_ids']) || count($j1['data']['category_ids']) < 0)) {
            return false;
        }

        if ((!isset($j2['data']['product_ids']) || count($j2['data']['product_ids']) <= 0) && (!isset($j2['data']['category_ids']) || count($j2['data']['category_ids']) < 0)) {
            return false;
        }

        if (isset($j1['data']['product_ids']) && count($j1['data']['product_ids']) + count($j2['data']['product_ids']) > $this->maxSingleJobDataSize) {
            return false;
        }

        if (isset($j1['data']['category_ids']) && count($j1['data']['category_ids']) + count($j2['data']['category_ids']) > $this->maxSingleJobDataSize) {
            return false;
        }

        return true;
    }

    private function arrayMultisort()
    {
        $args = func_get_args();

        $data = array_shift($args);

        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();

                foreach ($data as $key => $row) {
                    $tmp[$key] = $row[$field];
                }

                $args[$n] = $tmp;
            }
        }

        $args[] = &$data;

        call_user_func_array('array_multisort', $args);

        return array_pop($args);
    }

    /**
     * @param array $jobs
     */
    private function lockJobs($jobs)
    {
        $jobsIds = $this->getJobsIdsFromMergedJobs($jobs);

        if ($jobsIds !== array()) {
            $pid = getmypid();
            $this->db->update($this->table, array(
                'pid' => $pid,
                'locked_at' => date('Y-m-d H:i:s'),
            ), array('job_id IN (?)' => $jobsIds));
        }
    }

    /**
     * @param array $mergedJobs
     *
     * @return string[]
     */
    private function getJobsIdsFromMergedJobs($mergedJobs)
    {
        $jobsIds = array();
        foreach ($mergedJobs as $job) {
            $jobsIds = array_merge($jobsIds, $job['merged_ids']);
        }

        return $jobsIds;
    }

    private function clearOldLogRecords()
    {
        $idsToDelete = $this->db->query("SELECT id FROM {$this->logTable} ORDER BY started DESC, id DESC LIMIT 25000, ".PHP_INT_MAX)
                        ->fetchAll(\PDO::FETCH_COLUMN, 0);

        if ($idsToDelete) {
            $this->db->query("DELETE FROM {$this->logTable} WHERE id IN (" . implode(", ", $idsToDelete) . ")");
        }
    }

    public function clearQueue($canClear = false)
    {
        if ($canClear) {
            $this->db->truncateTable($this->table);
            $this->logger->log("{$this->table} table has been truncated.");
        }
    }

    private function unlockStackedJobs()
    {
        $this->db->update($this->table, array(
            'locked_at' => null,
            'pid' => null,
        ), 'locked_at < (NOW() - INTERVAL ' . self::UNLOCK_STACKED_JOBS_AFTER_MINUTES . ' MINUTE)');
    }
}
