<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_CategoryAdditionalAttributes extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
{
    public function __construct()
    {
        $this->settings = array(
            'columns' => array(
                'attribute' => array(
                    'label'   => 'Attribute',
                    'options' => function () {
                        $options = array();

                        /** @var Algolia_Algoliasearch_Helper_Entity_Categoryhelper $category_helper */
                        $category_helper = Mage::helper('algoliasearch/entity_categoryhelper');

                        $searchableAttributes = $category_helper->getAllAttributes();
                        foreach ($searchableAttributes as $key => $label) {
                            $options[$key] = $key ? $key : $label;
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
            ),
            'buttonLabel' => 'Add Attribute',
            'addAfter'    => false,
        );

        parent::__construct();
    }
}
