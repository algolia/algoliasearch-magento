<?php
/**
 * Source model for algolia conversion analytics mode
 */
class Algolia_Algoliasearch_Model_System_ConversionAnalyticsMode
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'disabled',     'label' => Mage::helper('algoliasearch')->__('[Disabled]')),
            array('value' => 'add_to_cart',  'label' => Mage::helper('algoliasearch')->__('Track "Add to cart" action as conversion')),
            array('value' => 'place_order',  'label' => Mage::helper('algoliasearch')->__('Track "Place Order" action as conversion')),
        );
    }
}
