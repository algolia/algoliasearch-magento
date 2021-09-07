<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_Facets extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
{
    const SEARCHABLE = '1';
    const NOT_SEARCHABLE = '2';
    const FILTER_ONLY = '3';

    protected $_isQueryRulesDisabled;

    public function __construct()
    {
        $this->settings = array(
            'columns' => array(
                'attribute' => array(
                    'label'   => 'Attribute',
                    'options' => function () {
                        $options = array();

                        /** @var Algolia_Algoliasearch_Helper_Entity_Producthelper $product_helper */
                        $product_helper = Mage::helper('algoliasearch/entity_producthelper');

                        $attributes = $product_helper->getAllAttributes();
                        foreach ($attributes as $key => $label) {
                            $options[$key] = $key ?: $label;
                        }

                        return $options;
                    },
                    'rowMethod' => 'getAttribute',
                    'width'     => 160,
                ),
                'type' => array(
                    'label'   => 'Facet type',
                    'options' => array(
                        'conjunctive' => 'Conjunctive',
                        'disjunctive' => 'Disjunctive',
                        'slider'      => 'Slider',
                        'priceRanges' => 'Price Ranges',
                    ),
                    'rowMethod' => 'getType',
                ),
                'label' => array(
                    'label' => 'Label',
                    'style' => 'width: 100px;',
                ),
                'searchable' => array(
                    'label' => 'Modifier',
                    'options' => array(
                        self::SEARCHABLE => 'Searchable',
                        self::NOT_SEARCHABLE => 'Not Searchable',
                        self::FILTER_ONLY => 'Filter Only',
                    ),
                    'rowMethod' => 'getSearchable',
                ),
                'create_rule' => array(
                    'label'  => 'Create Query rule?',
                    'options' => array(
                        '2' => 'No',
                        '1' => 'Yes'
                    ),
                    'rowMethod' => 'getCreateRule',
                    'disabled' => $this->isQueryRulesDisabled()
                ),
            ),
            'buttonLabel' => 'Add Facet',
            'addAfter'    => false,
        );

        parent::__construct();
    }

    /**
     * @return bool
     */
    public function isQueryRulesDisabled()
    {
        if (is_null($this->_isQueryRulesDisabled)) {
            $this->_isQueryRulesDisabled = $this->_disableQueryRules();
        }

        return $this->_isQueryRulesDisabled;
    }

    /**
     * @return bool
     */
    protected function _disableQueryRules()
    {
        $proxyHelper = Mage::helper('algoliasearch/proxyHelper');
        $info = $proxyHelper->getClientConfigurationData();

        return !isset($info['query_rules']) || $info['query_rules'] == 0;
    }

    protected function _decorateRowHtml($element, $html)
    {
        if (!$this->isQueryRulesDisabled()) {
            return parent::_decorateRowHtml($element, $html);
        }

        $additionalRow = '<tr class="algoliasearch-messages"><td></td><td><div class="algoliasearch-config-info icon-stars">';
        $additionalRow .= $this->__('To get access to this Algolia feature, please consider <a href="%s" target="_blank">upgrading to a higher plan.</a>',
            'https://www.algolia.com/pricing/');
        $additionalRow .= '</div></td></tr>';

        return '<tr id="row_' . $element->getHtmlId() . '">' . $html . '</tr>' . $additionalRow;
    }
}
