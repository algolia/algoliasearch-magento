<?php

class Algolia_Algoliasearch_Block_Adminhtml_IndexingQueue_Status extends Mage_Adminhtml_Block_Template
{
    const CRON_QUEUE_FREQUENCY = 330;

    const QUEUE_NOT_PROCESSED_LIMIT = 3600;

    const QUEUE_FAST_LIMIT = 220;

    /** @var Algolia_Algoliasearch_Helper_Config */
    protected $config;

    /** @var Mage_Index_Model_Process */
    protected $queueRunnerIndexer;

    /**
     * Algolia_Algoliasearch_Model_Indexer_Algoliaqueuerunner
     */
    protected function _construct()
    {
        parent::_construct();
        $this->config = Mage::helper('algoliasearch/config');
        $this->queueRunnerIndexer = Mage::getModel('index/indexer')
            ->getProcessByCode(Algolia_Algoliasearch_Model_Indexer_Algoliaqueuerunner::INDEXER_ID);

        print_r($this->queueRunnerIndexer->getData());
    }

    /**
     * @return mixed
     */
    public function isQueueActive()
    {
        return $this->configHelper->isQueueActive();
    }

    /**
     * @return string
     */
    public function getQueueRunnerStatus()
    {
        $status = 'Unknown';

        /** @var Mage_Index_Model_Process $process */
        $process = Mage::getModel('index/process');
        $statuses = $process->getStatusesOptions();

        if ($this->queueRunnerIndexer->getStatus()
            && isset($statuses[$this->queueRunnerIndexer->getStatus()])) {
            $status = $statuses[$this->queueRunnerIndexer->getStatus()];
        }

        return $status;
    }

    public function getLastQueueUpdate()
    {
        return $this->queueRunnerIndexer->getEndedAt();
    }

    /**
     * Check if the average processing time  of the queue is fast
     *
     * @return bool
     */
    private function isQueueFast()
    {
        $averageProcessingTime = $this->queue->getAverageProcessingTime();

        return !is_null($averageProcessingTime) && $averageProcessingTime < self::QUEUE_FAST_LIMIT;
    }

    /**
     * Prepare html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->isQueueActive()) {
            return parent::_toHtml();
        }

        return '';
    }
}
