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
                'attribute' => [
                    'label' => 'Attribute',
                    'renderer' => 'category_custom_ranking_attribute',
                    'rowMethod' => 'getAttribute',
                ],
                'order' => [
                    'label' => 'Asc / Desc',
                    'renderer' => 'custom_ranking_order',
                    'rowMethod' => 'getOrder',
                ],
            ],
            'buttonLabel' => 'Add Ranking Criterion',
            'addAfter' => false,
        ];

        parent::__construct();
    }
}
