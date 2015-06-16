<?php

class Algolia_Algoliasearch_Model_Queue
{
    const TABLE_NAME  = 'algoliasearch_queue';
    const SUCCESS_LOG = 'algoliasearch_queue_log.txt';
    const ERROR_LOG   = 'algoliasearch_queue_errors.log';

    const XML_PATH_MAX_RETRIES = 'algoliasearch/queue/retries';
    const XML_PATH_IS_ACTIVE   = 'algoliasearch/queue/active';

    /**
     * @var string
     */
    private $_table;

    /**
     * @var Varien_Db_Adapter_Pdo_Mysql
     */
    private $_db;

    public function __construct()
    {
        $this->_table = Mage::getSingleton('core/resource')->getTableName('algoliasearch/queue');
        $this->_db = Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    /**
     * Add a job to the queue
     *
     * @param string $class   The Magento singleton identifier
     * @param string $method  The name of the method to be called
     * @param array  $data    The arguments to be passed to the method as a Varien_Object
     * @param int    $retries The maximum number of retries before giving up and logging an error
     */
    public function add($class, $method, $data, $retries = NULL)
    {
        // Insert a row for the new job
        $this->_db->insert($this->_table, array(
            'class' => $class,
            'method' => $method,
            'data' => serialize($data),
            'max_retries' => max(array(1, (int)($retries ? $retries : Mage::getStoreConfig(self::XML_PATH_MAX_RETRIES)))),
            'pid' => NULL,
        ));
    }

    /**
     * Method for cron job
     */
    public function runCron()
    {
        if (!Mage::getStoreConfigFlag(self::XML_PATH_IS_ACTIVE)) {
            return;
        }
        $this->run(300);
    }

    /**
     * Run the jobs that are in the queue
     *
     * @param mixed  $limit Limit of jobs to run
     * @return  int  Number of jobs run
     */
    public function run($limit = NULL)
    {
        // Cleanup crashed jobs
        $pids = $this->_db->fetchCol("SELECT pid FROM {$this->_table} WHERE pid IS NOT NULL GROUP BY pid");
        foreach ($pids as $pid) {
            // Old pid is no longer running, release it's reserved tasks
            if (is_numeric($pid) && !file_exists("/proc/{$pid}/status")) {
                Mage::log("A crashed job queue process was detected for pid {$pid}", Zend_Log::NOTICE, self::ERROR_LOG);
                $this->_db->update(
                    $this->_table,
                    array(
                        'pid' => new Zend_Db_Expr('NULL'),
                        'retries' => new Zend_Db_Expr('retries + 1')
                    ),
                    array('pid = ?' => $pid)
                );
            }
        }

        // Reserve all new jobs since last run
        $pid = getmypid();
        $limit = ($limit ? "LIMIT $limit":'');
        $batchSize = $this->_db->query("UPDATE {$this->_db->quoteIdentifier($this->_table,true)} SET pid = {$pid} WHERE pid IS NULL ORDER BY job_id $limit")->rowCount();

        // Run all reserved jobs
        $result = $this->_db->query($this->_db->select()->from($this->_table, '*')->where('pid = ?', $pid)->order(array('job_id')));
        while ($row = $result->fetch()) {
            $where = $this->_db->quoteInto('job_id = ?', $row['job_id']);
            $data = (substr($row['data'],0,1) == '{') ? json_decode($row['data'], TRUE) : $data = unserialize($row['data']);

            // Check retries
            if ($row['retries'] >= $row['max_retries']) {
                $this->_db->delete($this->_table, $where);
                Mage::log("{$row['pid']}: Mage::getSingleton({$row['class']})->{$row['method']}(".json_encode($data).")\n".$row['error_log'], Zend_Log::ERR, self::ERROR_LOG);
                continue;
            }

            // Run job!
            try {
                $model = Mage::getSingleton($row['class']);
                $method = $row['method'];
                $model->$method(new Varien_Object($data));
                $this->_db->delete($this->_table, $where);
                Mage::log("{$row['pid']}: Mage::getSingleton({$row['class']})->{$row['method']}(".json_encode($data).")", Zend_Log::INFO, self::SUCCESS_LOG);
            } catch (Exception $e) {
                // Increment retries and log error information
                $error =
                    date('c')." ERROR: ".get_class($e).": '{$e->getMessage()}' in {$e->getFile()}:{$e->getLine()}\n".
                    "Stack trace:\n".
                    $e->getTraceAsString();
                $bind = array(
                    'pid' => new Zend_Db_Expr('NULL'),
                    'error_log' => new Zend_Db_Expr('SUBSTR(CONCAT(error_log,'.$this->_db->quote($error).',"\n\n"),1,20000)')
                );
                $this->_db->update($this->_table, $bind, $where);
            }
        }

        return $batchSize;
    }
}
