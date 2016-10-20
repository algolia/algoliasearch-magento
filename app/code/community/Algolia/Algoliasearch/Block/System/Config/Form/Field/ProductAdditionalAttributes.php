<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_ProductAdditionalAttributes extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
{
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

                        $searchableAttributes = $product_helper->getAllAttributes();
                        foreach ($searchableAttributes as $key => $label) {
                            $options[$key] = $key ?: $label;
                        }

                        return $options;
                    },
                    'rowMethod' => 'getAttribute',
                    'width'     => 160,
                ),
                'searchable' => array(
                    'label'   => 'Searchable',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'rowMethod' => 'getSearchable',
                ),
                'retrievable' => array(
                    'label'   => 'Retrievable',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'rowMethod' => 'getRetrievable',
                ),
                'order' => array(
                    'label'   => 'Ordered',
                    'options' => array(
                        'unordered' => 'Unordered',
                        'ordered'   => 'Ordered',
                    ),
                    'rowMethod' => 'getOrder',
                ),
                'index_no_value' => array(
                    'label'   => 'Index empty value',
                    'options' => array(
                        '1'     => 'Yes',
                        '0'     => 'No',
                    ),
                    'rowMethod' => 'getIndexNoValue',
                ),
            ),
            'buttonLabel' => 'Add Attribute',
            'addAfter'    => false,
        );

        parent::__construct();
    }
}
