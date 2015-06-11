<?php

class Algolia_Algoliasearch_Model_Queue
{
    const SUCCESS_LOG = 'algoliasearch_queue_log.txt';
    const ERROR_LOG   = 'algoliasearch_queue_errors.log';

    private $table;
    private $db;

    private $config;

    public function __construct()
    {
        $this->table = Mage::getSingleton('core/resource')->getTableName('algoliasearch/queue');
        $this->db = Mage::getSingleton('core/resource')->getConnection('core_write');

        $this->config = new Algolia_Algoliasearch_Helper_Config();
    }

    public function add($class, $method, $data, $retries = NULL)
    {
        // Insert a row for the new job
        $this->db->insert($this->table, array(
            'class' => $class,
            'method' => $method,
            'data' => serialize($data),
            'max_retries' => max(array(1,(int)($retries ? $retries : $this->config->getQueueMaxRetries()))),
            'pid' => NULL,
        ));
    }

    public function runCron()
    {
        if ( ! $this->config->isQueueActive())
            return;

        $this->run($this->config->getNumberOfJobToRun());
    }

    public function run($limit = null)
    {
        // Cleanup crashed jobs
        $pids = $this->db->fetchCol("SELECT pid FROM {$this->table} WHERE pid IS NOT NULL GROUP BY pid");
        foreach ($pids as $pid) {
            // Old pid is no longer running, release it's reserved tasks
            if (is_numeric($pid) && ! file_exists("/proc/{$pid}/status")) {
                Mage::log("A crashed job queue process was detected for pid {$pid}", Zend_Log::NOTICE, self::ERROR_LOG);
                $this->db->update($this->table,array('pid' => new Zend_Db_Expr('NULL')),array('pid = ?' => $pid));
            }
        }

        // Reserve all new jobs since last run
        $pid = getmypid();
        $limit = ($limit ? "LIMIT $limit":'');
        $batchSize = $this->db->query("UPDATE {$this->db->quoteIdentifier($this->table,true)} SET pid = {$pid} WHERE pid IS NULL ORDER BY job_id $limit")->rowCount();

        // Run all reserved jobs
        $result = $this->db->query($this->db->select()->from($this->table, '*')->where('pid = ?',$pid)->order(array('job_id')));
        while ($row = $result->fetch()) {
            $where = $this->db->quoteInto('job_id = ?', $row['job_id']);
            $data = (substr($row['data'],0,1) == '{') ? json_decode($row['data'], TRUE) : $data = unserialize($row['data']);

            // Check retries
            if ($row['retries'] >= $row['max_retries']) {
                $this->db->delete($this->table, $where);
                Mage::log("{$row['pid']}: Mage::getSingleton({$row['class']})->{$row['method']}(".json_encode($data).")\n".$row['error_log'], Zend_Log::ERR, self::ERROR_LOG);
                continue;
            }

            // Run job!
            try {
                $model = Mage::getSingleton($row['class']);
                $method = $row['method'];
                $model->$method(new Varien_Object($data));
                $this->db->delete($this->table, $where);
                Mage::log("{$row['pid']}: Mage::getSingleton({$row['class']})->{$row['method']}(".json_encode($data).")", Zend_Log::INFO, self::SUCCESS_LOG);
            } catch(Exception $e) {
                // Increment retries and log error information
                $error =
                    date('c')." ERROR: ".get_class($e).": '{$e->getMessage()}' in {$e->getFile()}:{$e->getLine()}\n".
                    "Stack trace:\n".
                    $e->getTraceAsString();
                $bind = array(
                    'pid' => new Zend_Db_Expr('NULL'),
                    'retries' => new Zend_Db_Expr('retries + 1'),
                    'error_log' => new Zend_Db_Expr('SUBSTR(CONCAT(error_log,'.$this->db->quote($error).',"\n\n"),1,20000)')
                );
                $this->db->update($this->table, $bind, $where);
            }
        }

        return $batchSize;
    }
}
