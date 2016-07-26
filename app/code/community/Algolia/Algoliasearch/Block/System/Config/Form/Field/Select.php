<?php

class Algolia_Algoliasearch_Block_System_Config_Form_Field_Select extends Mage_Adminhtml_Block_Html_Select
{
    protected function _toHtml()
    {
        $this->setName($this->getInputName());
        $this->setClass('select');

        return trim(preg_replace('/\s+/', ' ', parent::_toHtml()));
    }
}
