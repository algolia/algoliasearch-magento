<?php

class Algolia_Algoliasearch_Model_Job extends Mage_Core_Model_Abstract
{
    const CACHE_TAG = 'algoliasearch_queue_job';

    protected $_cacheTag = 'algoliasearch_queue_job';
    protected $_eventPrefix = 'algoliasearch_queue_job';
    protected $_eventObject = 'queue_job';

    /**
     * Initialize resources
     */
    protected function _construct()
    {
        $this->_init('algoliasearch/job');
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        $status = Algolia_Algoliasearch_Model_Source_JobStatuses::STATUS_PROCESSING;

        if (is_null($this->getPid())) {
            $status = Algolia_Algoliasearch_Model_Source_JobStatuses::STATUS_NEW;
        }

        if ((int) $this->getRetries() >= $this->getMaxRetries()) {
            $status = Algolia_Algoliasearch_Model_Source_JobStatuses::STATUS_ERROR;
        }

        return $status;
    }

    /**
     * @return string
     */
    public function getStatusLabel()
    {
        $status = $this->getStatus();
        $labels = Mage::getModel('algoliasearch/source_jobStatuses')->getStatuses();

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    /**
     * @param Exception $e
     *
     * @return Algolia_Algoliasearch_Model_Job
     */
    public function saveError(Exception $e)
    {
        $this->setErrorLog($e->getMessage());
        $this->save($this);

        return $this;
    }
}
