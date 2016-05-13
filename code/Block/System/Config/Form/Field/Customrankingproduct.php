<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_Customrankingproduct extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $selectFields = [];

    /**
     * Creates and populates a select block to represent each column in the configuration property.
     *
     * @param $columnId String The name of the column defined in addColumn
     *
     * @return Algolia_Algoliasearch_Block_System_Config_Form_Field_Select
     *
     * @throws Exception
     */
    protected function getRenderer($columnId)
    {
        if (!array_key_exists($columnId, $this->selectFields) || !$this->selectFields[$columnId]) {
            /** @var Algolia_Algoliasearch_Helper_Config $config */
            $config = Mage::helper('algoliasearch/config');

            $aOptions = [];

            switch ($columnId) {
                case 'attribute': // Populate the attribute column with a list of searchable attributes
                    $attributes = $config->getProductAdditionalAttributes();

                    foreach ($attributes as $attribute) {
                        $aOptions[$attribute['attribute']] = $attribute['attribute'];
                    }

                    break;
                case 'order':
                    $aOptions = [
                        'desc' => 'Descending',
                        'asc' => 'Ascending',
                    ];
                    break;
                default:
                    throw new Exception('Unknown attribute id ' . $columnId);
            }

            $selectField = Mage::app()->getLayout()->createBlock('algoliasearch/system_config_form_field_select')->setIsRenderToJsTemplate(true);
            $selectField->setOptions($aOptions);
            $selectField->setExtraParams('style="width:160px;"');
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
        $this->addColumn('order', [
            'label' => Mage::helper('adminhtml')->__('Ordered'),
            'renderer' => $this->getRenderer('order'),
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Ranking Criterion');
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
            'option_extra_attr_' . $this->getRenderer('order')->calcOptionHash(
                $row->getOrder()),
            'selected="selected"'
        );
    }
}
