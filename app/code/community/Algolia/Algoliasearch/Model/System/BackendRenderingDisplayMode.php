<?php

class Algolia_Algoliasearch_Model_System_BackendRenderingDisplayMode
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'all',           'label' => Mage::helper('algoliasearch')->__('All categories')),
            array('value' => 'only_products', 'label' => Mage::helper('algoliasearch')->__('Categories without static blocks')),
        );
    }
}
