<?php

class Algolia_Algoliasearch_Model_System_Config_Backend_Serialized_Array extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array
{
    protected function _afterLoad()
    {
        /** @var Algolia_Algoliasearch_Helper_Data $helper */
        $helper = Mage::helper('algoliasearch');
        if ($helper->isX3Version()) {
            if (!is_array($this->getValue())) {
                $value = $this->getValue();
                $this->setValue(empty($value) ? false : unserialize($value));
            }

            return;
        }

        parent::_afterLoad();
    }
}
