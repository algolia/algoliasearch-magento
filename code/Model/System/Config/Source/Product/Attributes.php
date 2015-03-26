<?php
/**
 * Source model for category attributes
 */
class Algolia_Algoliasearch_Model_System_Config_Source_Product_Attributes
{
    public function toOptionArray()
    {
        static $options = NULL;
        if (is_null($options)) {
            $options = array();
            foreach (Mage::helper('algoliasearch')->getAllProductAttributes() as $attributeCode => $frontendLabel) {
                $options[] = array('value' => $attributeCode, 'label' => $attributeCode.': '.$frontendLabel);
            }
        }
        return $options;
    }
}
