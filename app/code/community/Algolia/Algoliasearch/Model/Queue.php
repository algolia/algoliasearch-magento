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

    protected $by_page;

    private $staticJobMethods = array(
        'saveSettings',
        'moveProductsTmpIndex',
        'deleteProductsStoreIndices',
        'removeCategories',
        'deleteCategoriesStoreIndices',
        'moveStoreSuggestionIndex',
    );

    public function __construct()
    {
        /** @var Mage_Core_Model_Resource $coreResource */
        $coreResource = Mage::getSingleton('core/resource');

        $this->table = $coreResource->getTableName('algoliasearch/queue');
        $this->db = $coreResource->getConnection('core_write');

        $this->config = Mage::helper('algoliasearch/config');
        $this->logger = Mage::helper('algoliasearch/logger');

        $this->by_page = $this->config->getNumberOfElementByPage();
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
        $isFullReindex = ($maxJobs === -1);
        $limit = $isFullReindex ? $this->config->getNumberOfJobToRun() : $maxJobs;

        $jobs = array();

        $actualBatchSize = 0;
        $maxBatchSize = $this->config->getNumberOfElementByPage() * $limit;

        $offset = 0;

        try {
            $this->db->beginTransaction();

            while ($actualBatchSize < $maxBatchSize) {
                $data = $this->db->query($this->db->select()->from($this->table, '*')->where('pid IS NULL')
                                                  ->order(array('job_id'))->limit($limit, $limit * $offset)
                                                  ->forUpdate());
                $data = $data->fetchAll();
                $rowsCount = count($data);

                $offset++;

                if ($rowsCount <= 0) {
                    break;
                } elseif ($rowsCount == $maxJobs) {
                    $jobs = $data;
                    break;
                }

                foreach ($data as $job) {
                    $jobSize = (int) $job['data_size'];

                    if ($actualBatchSize + $jobSize <= $maxBatchSize) {
                        $jobs[] = $job;
                        $actualBatchSize += $jobSize;
                    } else {
                        break 2;
                    }
                }
            }

            if (count($jobs) <= 0) {
                $this->db->commit();
                $this->db->closeConnection();

                return;
            }

            $firstJobsId = $jobs[0]['job_id'];
            $lastJobsId = $jobs[count($jobs) - 1]['job_id'];

            $pid = getmypid();

            // Reserve all new jobs since last run
            $this->db->query("UPDATE {$this->db->quoteIdentifier($this->table, true)} SET pid = ".$pid.' WHERE job_id >= '.$firstJobsId." AND job_id <= $lastJobsId");

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->db->closeConnection();

            throw $e;
        }

        $jobs = $this->prepareJobs($jobs);
        $jobs = $this->sortAndMergeJob($jobs);

        // Run all reserved jobs
        foreach ($jobs as $job) {
            try {
                $model = Mage::getSingleton($job['class']);
                $method = $job['method'];
                $model->$method(new Varien_Object($job['data']));
            } catch (Exception $e) {
                // Increment retries and log error information
                $this->logger->log("Queue processing {$job['pid']} [KO]: Mage::getSingleton({$job['class']})->{$job['method']}(".json_encode($job['data']).')');
                $this->logger->log(date('c').' ERROR: '.get_class($e).": '{$e->getMessage()}' in {$e->getFile()}:{$e->getLine()}\n"."Stack trace:\n".$e->getTraceAsString());
            }
        }

        // Delete only when finished to be able to debug the queue if needed
        $where = $this->db->quoteInto('pid = ?', $pid);
        $this->db->delete($this->table, $where);

        if ($isFullReindex) {
            $this->run(-1);
            return;
        }

        $this->db->closeConnection();
    }

    private function prepareJobs($jobs)
    {
        foreach ($jobs as &$job) {
            $job['data'] = json_decode($job['data'], true);
        }

        return $jobs;
    }

    protected function sortAndMergeJob($oldJobs)
    {
        $oldJobs = $this->sortJobs($oldJobs);

        $jobs = array();

        $current_job = array_shift($oldJobs);
        $next_job = null;

        while ($current_job !== null) {
            if (count($oldJobs) > 0) {
                $next_job = array_shift($oldJobs);

                if ($this->mergeable($current_job, $next_job)) {
                    if (isset($current_job['data']['product_ids'])) {
                        $current_job['data']['product_ids'] = array_merge($current_job['data']['product_ids'], $next_job['data']['product_ids']);
                    } else {
                        $current_job['data']['category_ids'] = array_merge($current_job['data']['category_ids'], $next_job['data']['category_ids']);
                    }

                    continue;
                }
            } else {
                $next_job = null;
            }

            if (isset($current_job['data']['product_ids'])) {
                $current_job['data']['product_ids'] = array_unique($current_job['data']['product_ids']);
            }

            if (isset($current_job['data']['category_ids'])) {
                $current_job['data']['category_ids'] = array_unique($current_job['data']['category_ids']);
            }

            $jobs[] = $current_job;
            $current_job = $next_job;
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

    protected function mergeable($j1, $j2)
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

        if (isset($j1['data']['product_ids']) && count($j1['data']['product_ids']) + count($j2['data']['product_ids']) > $this->by_page) {
            return false;
        }

        if (isset($j1['data']['category_ids']) && count($j1['data']['category_ids']) + count($j2['data']['category_ids']) > $this->by_page) {
            return false;
        }

        return true;
    }

    public function arrayMultisort()
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
}
