<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_CustomRankingCategoryAttributes extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
{
    public function __construct()
    {
        $this->settings = array(
            'columns' => array(
                'attribute' => array(
                    'label'   => 'Attribute',
                    'options' => function () {
                        $options = array();

                        /** @var Algolia_Algoliasearch_Helper_Config $config */
                        $config = Mage::helper('algoliasearch/config');

                        $attributes = $config->getCategoryAdditionalAttributes();
                        foreach ($attributes as $attribute) {
                            $options[$attribute['attribute']] = $attribute['attribute'];
                        }

                        $options['custom_attribute'] = '[use custom attribute]';

                        return $options;
                    },
                    'rowMethod' => 'getAttribute',
                    'width' => 150,
                ),
                'custom_attribute' => array(
                    'label' => 'Custom attribute',
                    'style' => 'width: 120px;',
                ),
                'order' => array(
                    'label'   => 'Asc / Desc',
                    'options' => array(
                        'desc' => 'Descending',
                        'asc'  => 'Ascending',
                    ),
                    'rowMethod' => 'getOrder',
                ),
            ),
            'buttonLabel' => 'Add Ranking Criterion',
            'addAfter'    => false,
        );

        parent::__construct();
    }
}
