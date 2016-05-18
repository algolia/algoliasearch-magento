<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_Facets extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
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
                'type' => [
                    'label' => 'Facet type',
                    'renderer' => 'facet_type',
                    'rowMethod' => 'getType',
                ],
            ],
            'buttonLabel' => 'Add Facet',
            'addAfter' => false,
        ];

        parent::__construct();

        $this->addColumn('label', [
            'label' => Mage::helper('adminhtml')->__('Label'),
            'style' => 'width: 100px;',
        ]);
    }
}
