<?php

/**
 * Source model for algolia remove words if no result.
 */

class Algolia_Algoliasearch_Model_System_IndexVisibility
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'all',           'label' => Mage::helper('algoliasearch')->__('All visible products')),
            array('value' => 'only_search',   'label' => Mage::helper('algoliasearch')->__('Only products visible in Search')),
            array('value' => 'only_catalog',  'label' => Mage::helper('algoliasearch')->__('Only products visible in Catalog')),
        );
    }
}
