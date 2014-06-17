<?php

/**
 * Algolia search observer model
 */
class Algolia_Algoliasearch_Model_Observer
{
    /**
     * @return Algolia_Algoliasearch_Model_Queue
     */
    protected function getQueue()
    {
        return Mage::getSingleton('algoliasearch/queue');
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return Algolia_Algoliasearch_Model_Observer
     */
    public function saveProduct(Varien_Event_Observer $observer)
    {
        $this->getQueue()->add('algoliasearch/observer', 'reindex', array('product_id' => $observer->getProduct()->getId()));
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return Algolia_Algoliasearch_Model_Observer
     */
    public function saveCategory(Varien_Event_Observer $observer)
    {
        $this->getQueue()->add('algoliasearch/observer', 'reindex', array('category_id' => $observer->getCategory()->getId()));
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return Algolia_Algoliasearch_Model_Observer
     */
    public function useAlgoliaSearch(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfigFlag(Algolia_Algoliasearch_Helper_Data::XML_PATH_IS_ALGOLIA_SEARCH_ENABLED)) {
            $observer->getLayout()->getUpdate()->addHandle('algolia_search_handle');
        }
        return $this;
    }

    /**
     * Reindex product or category
     *
     * @param Varien_Object $event
     * @return Algolia_Algoliasearch_Model_Observer
     * @throws Exception
     */
    public function reindex(Varien_Object $event)
    {
        // Reindex product
        if ($productId = $event->getProductId()) {
            $product = Mage::getModel('catalog/product')->load($productId);
            if ( ! $product->getId()) {
                throw new Exception('Could not load product by id: '.$productId);
            }
            foreach ($product->getStoreIds() as $storeId) {
                if (Mage::app()->getStore($storeId)->isAdmin()) {
                    continue;
                }
                $index = Mage::helper('algoliasearch')->getStoreIndex($storeId);
                $storeProduct = Mage::getModel('catalog/product')->setStoreId($storeId)->load($product->getId()); /** @var $storeProduct Mage_Catalog_Model_Product */
                $index->addObject(Mage::helper('algoliasearch')->getProductJSON($storeProduct));
            }
        }

        // Reindex category
        if ($categoryId = $event->getCategoryId()) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            if ( ! $category->getId()) {
                throw new Exception('Could not load category by id: '.$categoryId);
            }
            foreach ($category->getStoreIds() as $storeId) {
                if (Mage::app()->getStore($storeId)->isAdmin()) {
                    continue;
                }
                $index = Mage::helper('algoliasearch')->getStoreIndex($storeId);
                $storeCategory = Mage::getModel('catalog/category')->setStoreId($storeId)->load($category->getId()); /** @var $storeCategory Mage_Catalog_Model_Category */
                $index->addObject(Mage::helper('algoliasearch')->getCategoryJSON($storeCategory));
            }
        }
    }
}
