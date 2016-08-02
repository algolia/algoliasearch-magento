<?php
/**
 * Source model for algolia remove words if no result.
 */
class Algolia_Algoliasearch_Model_System_Removewords
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'none',          'label' => Mage::helper('algoliasearch')->__('None')),
            array('value' => 'allOptional',   'label' => Mage::helper('algoliasearch')->__('AllOptional')),
            array('value' => 'lastWords',     'label' => Mage::helper('algoliasearch')->__('LastWords')),
            array('value' => 'firstWords',    'label' => Mage::helper('algoliasearch')->__('FirstWords')),
        );
    }
}
