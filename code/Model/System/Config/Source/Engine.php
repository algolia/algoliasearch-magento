<?php
/**
 * Source model for algolia search engine
 */
class Algolia_Algoliasearch_Model_System_Config_Source_Engine
{
    public function toOptionArray()
    {
        static $options = NULL;
        if (is_null($options)) {
            $options = array();
            // Retrieve options from Magento Enterprise edition
            if (Mage::helper('core')->isModuleEnabled('Enterprise_Search')) {
                $options = Mage::getSingleton('enterprise_search/adminhtml_system_config_source_engine')->toOptionArray();
            } else {
                $options[] = array('value' => 'catalogsearch/fulltext_engine', 'label' => Mage::helper('catalogsearch')->__('MySql Fulltext'));
            }
            $options[] = array('value' => 'algoliasearch/engine', 'label' => Mage::helper('algoliasearch')->__('Algolia'));
        }
        return $options;
    }
}
