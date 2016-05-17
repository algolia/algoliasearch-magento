<?php
/**
 * Source model for algolia remove words if no result.
 */
class Algolia_Algoliasearch_Model_System_Removewords
{
    public function toOptionArray()
    {
        return [
            ['value' => 'none',          'label' => Mage::helper('algoliasearch')->__('None')],
            ['value' => 'allOptional',   'label' => Mage::helper('algoliasearch')->__('AllOptional')],
            ['value' => 'lastWords',     'label' => Mage::helper('algoliasearch')->__('LastWords')],
            ['value' => 'firstWords',    'label' => Mage::helper('algoliasearch')->__('FirstWords')],
        ];
    }
}
