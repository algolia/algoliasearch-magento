<?php
/**
 * Category attributes config field backend model
 */
class Algolia_Algoliasearch_Model_System_Config_Backend_Product_Attributes extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        if ($this->isValueChanged()) {
            // Require reindex
            Mage::getSingleton('index/indexer')
                ->getProcessByCode('algolia_search_indexer')
                ->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        }
    }
}