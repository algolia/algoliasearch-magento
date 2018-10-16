<?php

class Algolia_Algoliasearch_Model_Resource_Job_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('algoliasearch/job');
    }
}