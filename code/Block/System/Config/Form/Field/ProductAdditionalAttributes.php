<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_ProductAdditionalAttributes extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
{
    public function __construct()
    {
        $this->settings = [
            'columns' => [
                'attribute' => [
                    'label'   => 'Attribute',
                    'options' => function () {
                        $options = [];

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
                ],
                'searchable' => [
                    'label'   => 'Searchable',
                    'options' => [
                        '1' => 'Yes',
                        '0' => 'No',
                    ],
                    'rowMethod' => 'getSearchable',
                ],
                'retrievable' => [
                    'label'   => 'Retrievable',
                    'options' => [
                        '1' => 'Yes',
                        '0' => 'No',
                    ],
                    'rowMethod' => 'getRetrievable',
                ],
                'order' => [
                    'label'   => 'Ordered',
                    'options' => [
                        'ordered'   => 'Ordered',
                        'unordered' => 'Unordered',
                    ],
                    'rowMethod' => 'getOrder',
                ],
            ],
            'buttonLabel' => 'Add Attribute',
            'addAfter'    => false,
        ];

        parent::__construct();
    }
}
