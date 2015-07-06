<?php

/**
 * Algolia custom sort order field
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_Sorts extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $selectFields = array();

    /**
     * Creates and populates a select block to represent each column in the configuration property.
     *
     * @param $columnId String The name of the column defined in addColumn
     * @return Algolia_Algoliasearch_Block_System_Config_Form_Field_Select
     * @throws Exception
     */
    protected function getRenderer($columnId) {
        if (!array_key_exists($columnId, $this->selectFields) || !$this->selectFields[$columnId])
        {
            $aOptions = array();

            $selectField = Mage::app()->getLayout()->createBlock('algoliasearch/system_config_form_field_select')->setIsRenderToJsTemplate(true);

            $config = Mage::helper('algoliasearch/config');

            switch($columnId) {
                case 'attribute': // Populate the attribute column with a list of searchable attributes
                    $attributes = $config->getProductAdditionalAttributes();

                    foreach ($attributes as $attribute) {
                        $aOptions[$attribute['attribute']] = $attribute['attribute'];
                    }

                    $selectField->setExtraParams('style="width:160px;"');
                    break;
                case 'sort':
                    $aOptions = array(
                        'asc'   => 'Ascending',
                        'desc'  => 'Descending',
                    );

                    $selectField->setExtraParams('style="width:100px;"');
                    break;
                default:
                    throw new Exception('Unknown attribute id ' . $columnId);
            }

            $selectField->setOptions($aOptions);
            $this->selectFields[$columnId] = $selectField;
        }
        return $this->selectFields[$columnId];
    }

    public function __construct()
    {
        $this->addColumn('attribute', array(
            'label' => Mage::helper('adminhtml')->__('Attribute'),
            'renderer'=> $this->getRenderer('attribute'),
        ));

        $this->addColumn('sort', array(
            'label' => Mage::helper('adminhtml')->__('Sort'),
            'renderer'=> $this->getRenderer('sort'),
        ));

        $this->addColumn('label', array(
            'label' => Mage::helper('adminhtml')->__('Label'),
            'style' => 'width: 200px;'
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Attribute');
        parent::__construct();
    }

    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->getRenderer('attribute')->calcOptionHash(
                $row->getAttribute()),
            'selected="selected"'
        );

        $row->setData(
            'option_extra_attr_' . $this->getRenderer('sort')->calcOptionHash(
                $row->getSort()),
            'selected="selected"'
        );
    }
}
