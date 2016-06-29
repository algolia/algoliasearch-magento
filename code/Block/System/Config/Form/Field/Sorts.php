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
                    'label'   => 'Attribute',
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
                    'width'     => 160,
                ],
                'sort' => [
                    'label'   => 'Sort',
                    'options' => [
                        'asc'  => 'Ascending',
                        'desc' => 'Descending',
                    ],
                    'rowMethod' => 'getSort',
                ],
                'label' => [
                    'label' => 'Label',
                    'style' => 'width: 200px;',
                ],
            ],
            'buttonLabel' => 'Add Sorting Attribute',
            'addAfter'    => false,
        ];

        parent::__construct();
    }
}
