<?php

/**
 * Algolia custom sort order field
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_Customsortorder extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $attribute;
    protected $order;


    protected function getRenderer($id) {
        if (!$this->$id) {
            $aOptions = array();
            switch($id) {
                case 'attribute':
                    $searchableAttributes = Mage::getResourceModel('algoliasearch/fulltext')->getSearchableAttributes();
                    foreach ($searchableAttributes as $attribute){
                        $aOptions[$attribute->getAttributecode()] = $attribute->getFrontendLabel();
                    }
                    break;
                case 'order':
                    $aOptions = array(
                        'desc' => 'Descending',
                        'asc' => 'Ascending',
                    );
                    break;
                default:
                    throw new Exception('Unknown attribute id ' . $id);
            }

            $this->$id = Mage::app()->getLayout()->createBlock('algoliasearch/system_config_form_field_select')->setIsRenderToJsTemplate(true);
            $this->$id->setOptions($aOptions);
            $this->$id->setExtraParams('style="width:160px;"');
        }
        return $this->$id;
    }


    public function __construct()
    {
        $this->addColumn('attribute', array(
            'label' => Mage::helper('adminhtml')->__('Attribute'),
            'renderer'=> $this->getRenderer('attribute'),
        ));
        $this->addColumn('order', array(
            'label' => Mage::helper('adminhtml')->__('Sort Order'),
            'renderer'=> $this->getRenderer('order'),
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Custom Sort Order');
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
