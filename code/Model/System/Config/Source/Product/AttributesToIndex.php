<?php

/**
 * Algoliasearch attribute to index
 */
class Algolia_Algoliasearch_Model_System_Config_Source_Product_AttributesToIndex
{
    /**
     * Additional product attributes that can be indexed (excluding predefined attributes)
     *
     * @return array
     */
    public function toOptionArray()
    {
        static $options = NULL;
        if ( ! $options) {
            $options = array();
            $attributes = Mage::getResourceModel('catalog/product_attribute_collection');
            $attributes->addIsSearchableFilter();
            $attributes->addFieldToFilter('attribute_code', array('nin' => Mage::helper('algoliasearch')->getPredefinedCategoryAttributes()));
            $attributes->setOrder('frontend_label', Varien_Data_Collection::SORT_ORDER_ASC);
            foreach ($attributes as $attribute) { /** @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
                $options[] = array(
                    'value' => $attribute->getAttributeCode(),
                    'label' => $attribute->getFrontendLabel(),
                );
            }
        }
        return $options;
    }
}
