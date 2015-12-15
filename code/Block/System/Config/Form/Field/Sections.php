<?php

/**
 * Algolia custom sort order field
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_Sections extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $selectFields = array();

    protected function getRenderer($columnId) {
        if (!array_key_exists($columnId, $this->selectFields) || !$this->selectFields[$columnId])
        {
            $aOptions = array();

            $selectField = Mage::app()->getLayout()->createBlock('algoliasearch/system_config_form_field_select')->setIsRenderToJsTemplate(true);

            $config = Mage::helper('algoliasearch/config');

            switch($columnId) {
                case 'name': // Populate the attribute column with a list of searchable attributes

                    $sections = array(
                        array('name' => 'pages', 'label' => 'Pages'),
                    );

                    $attributes = $config->getFacets();

                    foreach ($attributes as $attribute) {
                        if ($attribute['attribute'] == 'price')
                            continue;

                        if ($attribute['attribute'] == 'category' || $attribute['attribute'] == 'categories')
                            continue;

                        $sections[] = array('name' => $attribute['attribute'], 'label' => $attribute['label'] ? $attribute['label'] : $attribute['attribute']);
                    }

                    foreach ($sections as $section)
                        $aOptions[$section['name']] = $section['label'];

                    $selectField->setExtraParams('style="width:130px;"');

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
        $this->addColumn('name', array(
            'label' => Mage::helper('adminhtml')->__('Section'),
            'renderer' => $this->getRenderer('name'),
        ));

        $this->addColumn('label', array(
            'label' => Mage::helper('adminhtml')->__('Label'),
            'style' => 'width: 100px;'
        ));

        $this->addColumn('hitsPerPage', array(
            'label' => Mage::helper('adminhtml')->__('Hits per page'),
            'style' => 'width: 100px;',
            'class' => 'required-entry input-text validate-number'
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Attribute');
        parent::__construct();
    }

    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->getRenderer('name')->calcOptionHash(
                $row->getName()),
            'selected="selected"'
        );
    }
}
