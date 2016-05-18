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
                    'label' => 'Attribute',
                    'renderer' => 'product_attribute',
                    'rowMethod' => 'getAttribute',
                ],
                'searchable' => [
                    'label' => 'Searchable',
                    'renderer' => 'searchable',
                    'rowMethod' => 'getSearchable',
                ],
                'retrievable' => [
                    'label' => 'Retrievable',
                    'renderer' => 'retrievable',
                    'rowMethod' => 'getRetrievable',
                ],
                'order' => [
                    'label' => 'Ordered',
                    'renderer' => 'order',
                    'rowMethod' => 'getOrder',
                ],
            ],
            'buttonLabel' => 'Add Attribute',
            'addAfter' => false,
        ];

        parent::__construct();
    }
}
