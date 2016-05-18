<?php

/**
 * Algolia custom sort order field.
 */
abstract class Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $settings;
    protected $selectFields = [];

    private $config;

    public function __construct()
    {
        if (!isset($this->settings)) {
            parent::__construct();
            return;
        }

        foreach ($this->settings['columns'] as $columnName => $column) {
            $this->addColumn($columnName, [
                'label'    => Mage::helper('adminhtml')->__($column['label']),
                'renderer' => $this->getRenderer($column['renderer']),
            ]);
        }

        $this->_addAfter = $this->settings['addAfter'];
        $this->_addButtonLabel = Mage::helper('adminhtml')->__($this->settings['buttonLabel']);

        parent::__construct();
    }

    protected function _prepareArrayRow(Varien_Object $row)
    {
        foreach ($this->settings['columns'] as $column) {
            $row->setData(
                'option_extra_attr_'.$this->getRenderer($column['renderer'])->calcOptionHash(
                    $row->{$column['rowMethod']}()),
                'selected="selected"'
            );
        }
    }

    /**
     * Creates and populates a select block to represent each column in the configuration property.
     *
     * @param $columnId String The name of the column defined in addColumn
     * @return Algolia_Algoliasearch_Block_System_Config_Form_Field_Select
     * @throws Exception
     */
    protected function getRenderer($columnId)
    {
        if (array_key_exists($columnId, $this->selectFields) && $this->selectFields[$columnId]) {
            return $this->selectFields[$columnId];
        }

        /** @var Algolia_Algoliasearch_Block_System_Config_Form_Field_Select $selectField */
        $selectField = Mage::app()->getLayout()->createBlock('algoliasearch/system_config_form_field_select');
        $selectField->setIsRenderToJsTemplate(true);

        $options = [];

        switch ($columnId) {

            case 'pages':
                /** @var Mage_Cms_Model_Resource_Page_Collection $magento_pages */
                $magento_pages = Mage::getModel('cms/page')->getCollection()->addFieldToFilter('is_active', 1);

                $ids = $magento_pages->toOptionArray();

                foreach ($ids as $id) {
                    $options[$id['value']] = $id['value'];
                }

                $selectField->setExtraParams('style="width:160px;"');

                break;

            case 'category_attribute':
                /** @var Algolia_Algoliasearch_Helper_Entity_Categoryhelper $category_helper */
                $category_helper = Mage::helper('algoliasearch/entity_categoryhelper');
                $searchableAttributes = $category_helper->getAllAttributes();

                foreach ($searchableAttributes as $key => $label) {
                    $options[$key] = $key ? $key : $label;
                }

                $selectField->setExtraParams('style="width:160px;"');
                break;

            case 'product_attribute':
                /** @var Algolia_Algoliasearch_Helper_Entity_Producthelper $product_helper */
                $product_helper = Mage::helper('algoliasearch/entity_producthelper');
                $searchableAttributes = $product_helper->getAllAttributes();

                foreach ($searchableAttributes as $key => $label) {
                    $options[$key] = $key ? $key : $label;
                }

                $selectField->setExtraParams('style="width:160px;"');
                break;

            case 'searchable':
                $options = [
                    '1' => 'Yes',
                    '0' => 'No',
                ];

                $selectField->setExtraParams('style="width:100px;"');
                break;

            case 'retrievable':
                $options = [
                    '1' => 'Yes',
                    '0' => 'No',
                ];

                $selectField->setExtraParams('style="width:100px;"');
                break;

            case 'order':
                $options = [
                    'ordered'   => 'Ordered',
                    'unordered' => 'Unordered',
                ];

                $selectField->setExtraParams('style="width:100px;"');
                break;

            case 'product_custom_ranking_attribute':
                $attributes = $this->getConfig()->getProductAdditionalAttributes();

                foreach ($attributes as $attribute) {
                    $options[$attribute['attribute']] = $attribute['attribute'];
                }
                break;

            case 'category_custom_ranking_attribute':
                $searchableAttributes = $this->getConfig()->getCategoryAdditionalAttributes();

                foreach ($searchableAttributes as $attribute) {
                    $options[$attribute['attribute']] = $attribute['attribute'];
                }
                break;

            case 'custom_ranking_order':
                $options = [
                    'desc' => 'Descending',
                    'asc'  => 'Ascending',
                ];

                $selectField->setExtraParams('style="width:100px;"');
                break;

            case 'name':
                $sections = [
                    ['name' => 'pages', 'label' => 'Pages'],
                ];

                $attributes = $this->getConfig()->getFacets();
                foreach ($attributes as $attribute) {
                    if ($attribute['attribute'] == 'price') {
                        continue;
                    }

                    if ($attribute['attribute'] == 'category' || $attribute['attribute'] == 'categories') {
                        continue;
                    }

                    $sections[] = ['name' => $attribute['attribute'], 'label' => $attribute['label'] ? $attribute['label'] : $attribute['attribute']];
                }

                foreach ($sections as $section) {
                    $options[$section['name']] = $section['label'];
                }

                $selectField->setExtraParams('style="width:130px;"');
                break;

            case 'sort_and_facet_attribute':
                $attributes = $this->getConfig()->getProductAdditionalAttributes();

                foreach ($attributes as $attribute) {
                    $options[$attribute['attribute']] = $attribute['attribute'];
                }

                $selectField->setExtraParams('style="width:160px;"');
                break;

            case 'sort':
                $options = [
                    'asc'  => 'Ascending',
                    'desc' => 'Descending',
                ];

                $selectField->setExtraParams('style="width:100px;"');
                break;

            case 'facet_type':
                $options = [
                    'conjunctive' => 'Conjunctive',
                    'disjunctive' => 'Disjunctive',
                    'slider'      => 'Slider',
                    'priceRanges' => 'Price Ranges',
                ];

                $selectField->setExtraParams('style="width:100px;"');
                break;

            default:
                throw new Exception('Unknown attribute id '.$columnId);
        }

        $selectField->setOptions($options);

        $this->selectFields[$columnId] = $selectField;

        return $this->selectFields[$columnId];
    }

    /** @return Algolia_Algoliasearch_Helper_Config */
    private function getConfig()
    {
        if (!isset($this->config)) {
            $this->config = Mage::helper('algoliasearch/config');
        }

        return $this->config;
    }
}
