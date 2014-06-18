<?php
/**
 * Backend model for algolia search engine
 */
class Algolia_Algoliasearch_Model_System_Config_Backend_Engine extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        parent::_afterSave();

        if ($this->isValueChanged()) {
            Mage::getSingleton('index/indexer')->getProcessByCode('catalogsearch_fulltext')
                ->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        }

        return $this;
    }
}
