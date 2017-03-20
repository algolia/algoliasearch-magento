<?php

class Algolia_Algoliasearch_Model_System_Config_Backend_ExtraSettings extends Mage_Core_Model_Config_Data
{
    protected function _beforeSave()
    {
        $value = trim($this->getValue());

        if (empty($value)) {
            return parent::_beforeSave();
        }

        $fieldConfig = $this->getFieldConfig();
        $label = (string) $fieldConfig->label;

        json_decode($value);
        $error = json_last_error();

        if ($error) {
            Mage::throwException('JSON provided for "'.$label.'" field is not valid JSON.');
        }

        return parent::_beforeSave();
    }
}
