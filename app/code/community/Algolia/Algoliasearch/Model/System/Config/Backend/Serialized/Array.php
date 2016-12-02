<?php

class Algolia_Algoliasearch_Model_System_Config_Backend_Serialized_Array extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array
{
    protected function _afterLoad()
    {
        if (Mage::getEdition() !== 'Community' || version_compare(Mage::getVersion(), '1.9.3.0', '<') === true) {
            parent::_afterLoad();

            return;
        }

        if (!is_array($this->getValue())) {
            $value = $this->getValue();
            $this->setValue(empty($value) ? false : unserialize($value));
        }
    }
}
