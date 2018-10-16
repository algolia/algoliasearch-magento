<?php

class Algolia_Algoliasearch_Model_Resource_Job extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('algoliasearch/job', 'job_id');
    }
}