<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_CustomRankingCategoryAttributes extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
{
    public function __construct()
    {
        $this->settings = [
            'columns' => [
                'category_custom_ranking_attribute' => [
                    'label' => 'Attribute',
                    'options' => function () {
                        $options = [];

                        /** @var Algolia_Algoliasearch_Helper_Config $config */
                        $config = Mage::helper('algoliasearch/config');
                        $attributes = $config->getCategoryAdditionalAttributes();

                        foreach ($attributes as $attribute) {
                            $options[$attribute['category_attribute']] = $attribute['category_attribute'];
                        }

                        return $options;
                    },
                    'rowMethod' => 'getAttribute',
                ],
                'custom_ranking_order' => [
                    'label' => 'Asc / Desc',
                    'options' => [
                        'desc' => 'Descending',
                        'asc' => 'Ascending',
                    ],
                    'rowMethod' => 'getOrder',
                ],
            ],
            'buttonLabel' => 'Add Ranking Criterion',
            'addAfter' => false,
        ];

        parent::__construct();
    }
}
