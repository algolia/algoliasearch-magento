<?php

/**
 * Algoliasearch attribute to retrieve
 */
class Algolia_Algoliasearch_Model_System_Config_Source_Product_AttributesToRetrieve
{
    /**
     * Additional product attributes that can be retrieved (excluding predefined attributes)
     *
     * @return array
     */
    public function toOptionArray()
    {
        static $options = NULL;
        if ( ! $options) {
            $options = array();
            $attributes = Mage::getResourceModel('catalog/product_attribute_collection');
            $attributes->addVisibleFilter()->addIsSearchableFilter();
            $attributes->addFieldToFilter('attribute_code', array('nin' => Mage::helper('algoliasearch')->getPredefinedProductAttributes()));
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
