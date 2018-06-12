<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_ProductAttributes extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
{
    public function __construct()
    {
        $this->settings = array(
            'columns' => array(
                'attribute' => array(
                    'label'   => 'Attribute',
                    'options' => function () {
                        $options = array();

                        /** @var Algolia_Algoliasearch_Helper_Entity_Producthelper $product_helper */
                        $product_helper = Mage::helper('algoliasearch/entity_producthelper');
                        $attributes = $product_helper->getAllAttributes();
                        foreach ($attributes as $key => $label) {
                            $options[$key] = $key ?: $label;
                        }

                        $options['custom_attribute'] = '[use custom attribute]';

                        return $options;
                    },
                    'rowMethod' => 'getAttribute',
                    'width' => 150,
                ),
            ),
            'buttonLabel' => 'Add Attribute',
            'addAfter'    => false,
        );

        parent::__construct();
    }
}
