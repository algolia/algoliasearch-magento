<?php
class Algolia_Algoliasearch_Model_Observer
{
    public function saveProduct(Varien_Event_Observer $observer)
    {
        $product = $observer->getProduct(); /** @var $product Mage_Catalog_Model_Product */
        foreach ($product->getStoreIds() as $storeId) {
            if (Mage::app()->getStore($storeId)->isAdmin()) {
                continue;
            }
            $index = Mage::helper('algoliasearch')->getStoreIndex($storeId);
            $storeProduct = Mage::getModel('catalog/product')->setStoreId($storeId)->load($product->getId()); /** @var $storeProduct Mage_Catalog_Model_Product */
            $index->addObject(Mage::helper('algoliasearch')->getProductJSON($storeProduct));
        }
    }

    public function saveCategory(Varien_Event_Observer $observer)
    {
        $category = $observer->getCategory(); /** @var $category Mage_Catalog_Model_Category */
        foreach ($category->getStoreIds() as $storeId) {
            if (Mage::app()->getStore($storeId)->isAdmin()) {
                continue;
            }
            $index = Mage::helper('algoliasearch')->getStoreIndex($storeId);
            $storeCategory = Mage::getModel('catalog/category')->setStoreId($storeId)->load($category->getId()); /** @var $storeCategory Mage_Catalog_Model_Category */
            $index->addObject(Mage::helper('algoliasearch')->getCategoryJSON($storeCategory));
        }
    }

    public function useAlgoliaSearch(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfigFlag(Algolia_Algoliasearch_Helper_Data::XML_PATH_IS_ALGOLIA_SEARCH_ENABLED)) {
            $observer->getLayout()->getUpdate()->addHandle('algolia_search_handle');
        }
        return $this;
    }
}
