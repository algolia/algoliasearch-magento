<?php

class Algolia_Algoliasearch_Model_System_Config_Source_Dropdown_RetryValues
{
    public function toOptionArray()
    {
        return array(
            array('value' => '1','label' => '1'),
            array('value' => '2','label' => '2'),
            array('value' => '3','label' => '3'),
            array('value' => '5','label' => '5'),
            array('value' => '10','label' => '10'),
            array('value' => '20','label' => '20'),
            array('value' => '50','label' => '50'),
            array('value' => '100','label' => '100'),
            array('value' => '9999999','label' => 'unlimited')
        );
    }
}
