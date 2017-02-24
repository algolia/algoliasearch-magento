<?php

class Algolia_Algoliasearch_Model_Queue
{
    const SUCCESS_LOG = 'algoliasearch_queue_log.txt';
    const ERROR_LOG = 'algoliasearch_queue_errors.log';

    protected $table;

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

    public function __construct()
    {
        /** @var Mage_Core_Model_Resource $coreResource */
        $coreResource = Mage::getSingleton('core/resource');

        $this->table = $coreResource->getTableName('algoliasearch/queue');
        $this->db = $coreResource->getConnection('core_write');

        $this->config = Mage::helper('algoliasearch/config');
        $this->logger = Mage::helper('algoliasearch/logger');

        $this->maxSingleJobDataSize = $this->config->getNumberOfElementByPage();
    }

    public function add($class, $method, $data, $data_size)
    {
        // Insert a row for the new job
        $this->db->insert($this->table, array(
            'class'     => $class,
            'method'    => $method,
            'data'      => json_encode($data),
            'data_size' => $data_size,
            'pid'       => null,
        ));
    }

    public function runCron()
    {
        if (!$this->config->isQueueActive()) {
            return;
        }

        $nbJobs = $this->config->getNumberOfJobToRun();

        if (getenv('EMPTY_QUEUE') && getenv('EMPTY_QUEUE') == '1') {
            $nbJobs = -1;
        }

        $this->run($nbJobs);
    }

    public function run($maxJobs)
    {
        $pid = getmypid();

        $jobs = $this->getJobs($maxJobs, $pid);

        if (empty($jobs)) {
            $this->db->closeConnection();
        }

        // Run all reserved jobs
        foreach ($jobs as $job) {
            // If there are some failed jobs before move, we want to skip the move
            // as most probably not all products have prices reindexed
            // and therefore are not indexed yet in TMP index
            if ($job['method'] === 'moveProductsTmpIndex' && $this->noOfFailedJobs > 0) {
                // Set pid to NULL so it's not deleted after
                $this->db->query("UPDATE {$this->db->quoteIdentifier($this->table, true)} SET pid = NULL WHERE job_id = ".$job['job_id']);

                continue;
            }

            try {
                $model = Mage::getSingleton($job['class']);
                $method = $job['method'];
                $model->{$method}(new Varien_Object($job['data']));
            } catch (\Exception $e) {
                $this->noOfFailedJobs++;

                // Increment retries, set the job ID back to NULL
                $updateQuery = "UPDATE {$this->db->quoteIdentifier($this->table, true)} SET pid = NULL, retries = retries + 1 WHERE job_id IN (".implode(', ', (array) $job['merged_ids']).")";
                $this->db->query($updateQuery);

                // log error information
                $this->logger->log("Queue processing {$job['pid']} [KO]: Mage::getSingleton({$job['class']})->{$job['method']}(".json_encode($job['data']).')');
                $this->logger->log(date('c').' ERROR: '.get_class($e).": '{$e->getMessage()}' in {$e->getFile()}:{$e->getLine()}\n"."Stack trace:\n".$e->getTraceAsString());
            }
        }

        // Delete only when finished to be able to debug the queue if needed
        $where = $this->db->quoteInto('pid = ?', $pid);
        $this->db->delete($this->table, $where);

        $isFullReindex = ($maxJobs === -1);
        if ($isFullReindex) {
            $this->run(-1);

            return;
        }

        $this->db->closeConnection();
    }

    private function getJobs($maxJobs, $pid)
    {
        // Clear jobs with crossed max retries count
        $retryLimit = $this->config->getRetryLimit();
        if ($retryLimit > 0) {
            $where = $this->db->quoteInto('retries >= ?', $retryLimit);
            $this->db->delete($this->table, $where);
        } else {
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

                // If $jobs is empty, it's the first run
                if (empty($jobs)) {
                    $firstJobId = $rawJobs[0]['job_id'];
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

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->db->closeConnection();

            throw $e;
        }


        if (isset($firstJobId)) {
            $lastJobId = $this->maxValueInArray($jobs, 'job_id');

            // Reserve all new jobs since last run
            $this->db->query("UPDATE {$this->db->quoteIdentifier($this->table, true)} SET pid = ".$pid.' WHERE job_id >= '.$firstJobId." AND job_id <= $lastJobId");
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

    private function maxValueInArray($array, $keyToSearch)
    {
        $currentMax = null;

        foreach ($array as $arr) {
            foreach ($arr as $key => $value) {
                if ($key == $keyToSearch && ($value >= $currentMax)) {
                    $currentMax = $value;
                }
            }
        }

        return $currentMax;
    }
}
