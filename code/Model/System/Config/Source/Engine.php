<?php
/**
 * Source model for algolia search engine
 */

/*
 * The class Enterprise_Search_Model_Adminhtml_System_Config_Source_Engine must be extended to access it's options array
 * if it exists.
 */
if(!Mage::helper("core")->isModuleEnabled("Enterprise_Search")) {
    class Enterprise_Search_Model_Adminhtml_System_Config_Source_Engine {
        function toOptionArray() {
            return array();
        }
    }
}


class Algolia_Algoliasearch_Model_System_Config_Source_Engine extends Enterprise_Search_Model_Adminhtml_System_Config_Source_Engine
{
    public function toOptionArray()
    {
        static $options = NULL;
        if (is_null($options)) {
            $options = array();
            // Retrieve options from Magento Enterprise edition
            if (Mage::helper("core")->isModuleEnabled("Enterprise_Search")) {
                $options = parent::toOptionArray();
            } else {
                $options[] = array("value" => "catalogsearch/fulltext_engine", "label" => Mage::helper("catalogsearch")->__("MySql Fulltext"));
            }
            $options[] = array("value" => "algoliasearch/engine", "label" => Mage::helper("algoliasearch")->__("Algolia"));
        }
        return $options;
    }
}
