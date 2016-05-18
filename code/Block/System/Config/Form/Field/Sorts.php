<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_Sorts extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
{
    public function __construct()
    {
        $this->settings = [
            'columns' => [
                'attribute' => [
                    'label' => 'Attribute',
                    'renderer' => 'sort_and_facet_attribute',
                    'rowMethod' => 'getAttribute',
                ],
                'sort' => [
                    'label' => 'Sort',
                    'renderer' => 'sort',
                    'rowMethod' => 'getSort',
                ],
            ],
            'buttonLabel' => 'Add Sorting Attribute',
            'addAfter' => false,
        ];

        parent::__construct();

        $this->addColumn('label', [
            'label' => Mage::helper('adminhtml')->__('Label'),
            'style' => 'width: 200px;',
        ]);
    }
}
