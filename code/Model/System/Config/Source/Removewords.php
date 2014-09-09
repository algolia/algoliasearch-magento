<?php
/**
 * Source model for algolia remove words if no result
 */

class Algolia_Algoliasearch_Model_System_Config_Source_Removewords
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'None', 'label'=>Mage::helper('algoliasearch')->__('None')),
            array('value'=>'LastWords', 'label'=>Mage::helper('algoliasearch')->__('LastWords')),
            array('value'=>'FirstWords', 'label'=>Mage::helper('algoliasearch')->__('FirstWords')),
        );
    }
}
