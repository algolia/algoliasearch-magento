<?php

/**
 * Algolia custom sort order field.
 */
abstract class Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $settings;
    protected $selectFields = [];

    public function __construct()
    {
        foreach ($this->settings['columns'] as $columnName => $columnSettings) {
            $this->addColumn($columnName, [
                'label'    => Mage::helper('adminhtml')->__($columnSettings['label']),
                'renderer' => $this->getRenderer($columnName, $columnSettings),
            ]);
        }

        $this->_addAfter = $this->settings['addAfter'];
        $this->_addButtonLabel = Mage::helper('adminhtml')->__($this->settings['buttonLabel']);

        parent::__construct();
    }

    protected function _prepareArrayRow(Varien_Object $row)
    {
        foreach ($this->settings['columns'] as $columnName => $columnSettings) {
            $row->setData(
                'option_extra_attr_'.$this->getRenderer($columnName, $columnSettings)->calcOptionHash(
                    $row->{$columnSettings['rowMethod']}()),
                'selected="selected"'
            );
        }
    }

    /**
     * Creates and populates a select block to represent each column in the configuration property.
     *
     * @param $columnId         string  The name of the column defined in addColumn
     * @param $columnSettings   array   Settings for select box
     * @return Algolia_Algoliasearch_Block_System_Config_Form_Field_Select
     * @throws Exception
     */
    protected function getRenderer($columnId, array $columnSettings)
    {
        if (array_key_exists($columnId, $this->selectFields) && $this->selectFields[$columnId]) {
            return $this->selectFields[$columnId];
        }

        $options = $columnSettings['options'];
        if (!is_array($options) && is_callable($options)) {
            $options = $options();
        }

        $width = 100;
        if (isset($columnSettings['width'])) {
            $width = $columnSettings['width'];
        }

        /** @var Algolia_Algoliasearch_Block_System_Config_Form_Field_Select $selectField */
        $selectField = Mage::app()->getLayout()->createBlock('algoliasearch/system_config_form_field_select');

        $selectField->setIsRenderToJsTemplate(true);
        $selectField->setOptions($options);
        $selectField->setExtraParams('style="width:'.$width.'px;"');

        $this->selectFields[$columnId] = $selectField;

        return $this->selectFields[$columnId];
    }
}