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
                'sort_and_facet_attribute' => [
                    'label' => 'Attribute',
                    'options' => function () {
                        $options = [];

                        /** @var Algolia_Algoliasearch_Helper_Config $config */
                        $config = Mage::helper('algoliasearch/config');

                        $attributes = $config->getProductAdditionalAttributes();
                        foreach ($attributes as $attribute) {
                            $options[$attribute['attribute']] = $attribute['attribute'];
                        }

                        return $options;
                    },
                    'rowMethod' => 'getAttribute',
                    'width' => 160,
                ],
                'facet_type' => [
                    'label' => 'Facet type',
                    'options' => [
                        'conjunctive' => 'Conjunctive',
                        'disjunctive' => 'Disjunctive',
                        'slider' => 'Slider',
                        'priceRanges' => 'Price Ranges',
                    ],
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
