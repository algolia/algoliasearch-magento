<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_Facets extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $selectFields = [];

    protected function getRenderer($columnId)
    {
        if (!array_key_exists($columnId, $this->selectFields) || !$this->selectFields[$columnId]) {
            $aOptions = [];

            $selectField = Mage::app()->getLayout()->createBlock('algoliasearch/system_config_form_field_select')->setIsRenderToJsTemplate(true);

            /** @var Algolia_Algoliasearch_Helper_Config $config */
            $config = Mage::helper('algoliasearch/config');

            switch ($columnId) {
                case 'attribute': // Populate the attribute column with a list of searchable attributes
                    $attributes = $config->getProductAdditionalAttributes();

                    foreach ($attributes as $attribute) {
                        $aOptions[$attribute['attribute']] = $attribute['attribute'];
                    }

                    $selectField->setExtraParams('style="width:160px;"');

                    break;
                case 'type':
                    $aOptions = [
                        'conjunctive' => 'Conjunctive',
                        'disjunctive' => 'Disjunctive',
                        'slider' => 'Slider',
                        'priceRanges' => 'Price Ranges',
                    ];

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
        $this->addColumn('attribute', [
            'label' => Mage::helper('adminhtml')->__('Attribute'),
            'renderer' => $this->getRenderer('attribute'),
        ]);

        $this->addColumn('type', [
            'label' => Mage::helper('adminhtml')->__('Facet type'),
            'renderer' => $this->getRenderer('type'),
        ]);

        $this->addColumn('label', [
            'label' => Mage::helper('adminhtml')->__('Label'),
            'style' => 'width: 100px;',
        ]);

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
            'option_extra_attr_' . $this->getRenderer('type')->calcOptionHash(
                $row->getType()),
            'selected="selected"'
        );
    }
}
