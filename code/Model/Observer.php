<?php

/**
 * Algolia search observer model
 */
class Algolia_Algoliasearch_Model_Observer
{
    protected $config;
    protected $product_helper;
    protected $helper;

    public function __construct()
    {
        $this->config               = Mage::helper('algoliasearch/config');
        $this->product_helper       = Mage::helper('algoliasearch/entity_producthelper');
        $this->category_helper      = Mage::helper('algoliasearch/entity_categoryhelper');
        $this->suggestion_helper    = Mage::helper('algoliasearch/entity_suggestionhelper');

        $this->helper               = Mage::helper('algoliasearch');
    }

    /**
     * On config save
     */
    public function configSaved(Varien_Event_Observer $observer)
    {
        $this->saveSettings();
    }

    public function saveSettings()
    {
        foreach (Mage::app()->getStores() as $store) /** @var $store Mage_Core_Model_Store */
            if ($store->getIsActive() && $this->config->isEnabled($store->getId()))
                $this->helper->saveConfigurationToAlgolia($store->getId());
    }

    /**
     * Call algoliasearch.xml To load js / css / phtml
     */
    public function useAlgoliaSearchPopup(Varien_Event_Observer $observer)
    {
        if ($this->config->isEnabled() && ($this->config->isPopupEnabled() || $this->config->isInstantEnabled())) {
            $observer->getLayout()->getUpdate()->addHandle('algolia_search_handle');
        }
        return $this;
    }

    public function saveProduct(Varien_Event_Observer $observer)
    {
        $product = $observer->getDataObject();
        $product = Mage::getModel('catalog/product')->load($product->getId());

        Algolia_Algoliasearch_Model_Indexer_Algolia::$product_categories[$product->getId()] = $product->getCategoryIds();
    }

    private function updateStock($product_id)
    {
        foreach (Mage::app()->getStores() as $storeId => $store)
        {
            if ( !$store->getIsActive() || !$this->config->isEnabled($store->getId()) )
                continue;

            try
            {
                $this->helper->rebuildStoreProductIndex($storeId, array($product_id));
            }
            catch(\Exception $e)
            {
                Mage::log($e->getMessage());
                Mage::log($e->getTraceAsString());
            }
        }
    }

    public function catalogInventorySave(Varien_Event_Observer $observer)
    {
        $product = $observer->getItem();

        $this->updateStock($product->getProductId());
    }

    public function quoteInventory(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();

        foreach ($quote->getAllItems() as $product)
            $this->updateStock($product->getProductId());
    }

    public function refundOrderInventory(Varien_Event_Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();

        foreach ($creditmemo->getAllItems() as $product)
            $this->updateStock($product->getProductId());
    }

    public function deleteProductsStoreIndices(Varien_Object $event)
    {
        $storeId = $event->getStoreId();

        $this->helper->deleteProductsStoreIndices($storeId);
    }

    public function deleteCategoriesStoreIndices(Varien_Object $event)
    {
        $storeId = $event->getStoreId();

        $this->helper->deleteCategoriesStoreIndices($storeId);
    }

    public function removeProducts(Varien_Object $event)
    {
        $storeId = $event->getStoreId();
        $product_ids = $event->getProductIds();

        $this->helper->removeProducts($product_ids, $storeId);
    }

    public function removeCategories(Varien_Object $event)
    {
        $storeId = $event->getStoreId();
        $category_ids = $event->getCategoryIds();

        $this->helper->removeCategories($category_ids, $storeId);
    }

    public function rebuildAdditionalSectionsIndex(Varien_Object $event)
    {
        $storeId = $event->getStoreId();

        $this->helper->rebuildStoreAdditionalSectionsIndex($storeId);
    }

    public function rebuildPageIndex(Varien_Object $event)
    {
        $storeId = $event->getStoreId();

        $this->helper->rebuildStorePageIndex($storeId);
    }

    public function rebuildSuggestionIndex(Varien_Object $event)
    {
        $storeId = $event->getStoreId();

        $page = $event->getPage();
        $pageSize = $event->getPageSize();

        if (is_null($storeId) && ! empty($categoryIds))
        {
            foreach (Mage::app()->getStores() as $storeId => $store)
            {
                if ( !$store->getIsActive() || !$this->config->isEnabled($store->getId()) )
                    continue;

                $this->helper->rebuildStoreSuggestionIndex($storeId, $categoryIds);
            }
        }
        else
        {
            if (! empty($page) && ! empty($pageSize))
                $this->helper->rebuildStoreSuggestionIndexPage($storeId, $this->suggestion_helper->getSuggestionCollectionQuery($storeId), $page, $pageSize);
            else
                $this->helper->rebuildStoreSuggestionIndex($storeId);
        }

        return $this;
    }

    public function moveStoreSuggestionIndex(Varien_Object $event)
    {
        $storeId = $event->getStoreId();

        $this->helper->moveStoreSuggestionIndex($storeId);
    }

    public function rebuildCategoryIndex(Varien_Object $event)
    {
        $storeId = $event->getStoreId();
        $categoryIds = $event->getCategoryIds();

        $page = $event->getPage();
        $pageSize = $event->getPageSize();

        if (is_null($storeId) && ! empty($categoryIds))
        {
            foreach (Mage::app()->getStores() as $storeId => $store)
            {
                if ( !$store->getIsActive() || !$this->config->isEnabled($store->getId()) )
                    continue;

                $this->helper->rebuildStoreCategoryIndex($storeId, $categoryIds);
            }
        }
        else
        {
            if (! empty($page) && ! empty($pageSize))
                $this->helper->rebuildStoreCategoryIndexPage($storeId, $this->category_helper->getProductCollectionQuery($storeId, $categoryIds), $page, $pageSize);
            else
                $this->helper->rebuildStoreCategoryIndex($storeId, $categoryIds);
        }

        return $this;
    }


    public function rebuildProductIndex(Varien_Object $event)
    {
        $storeId = $event->getStoreId();
        $productIds = $event->getProductIds();

        $page = $event->getPage();
        $pageSize = $event->getPageSize();

        if (is_null($storeId) && ! empty($productIds))
        {
            foreach (Mage::app()->getStores() as $storeId => $store)
            {
                if ( !$store->getIsActive() || !$this->config->isEnabled($store->getId()) )
                    continue;

                $this->helper->rebuildStoreProductIndex($storeId, $productIds);
            }
        }
        else
        {
            if (! empty($page) && ! empty($pageSize))
                $this->helper->rebuildStoreProductIndexPage($storeId, $this->product_helper->getProductCollectionQuery($storeId, $productIds), $page, $pageSize);
            else
                $this->helper->rebuildStoreProductIndex($storeId, $productIds);
        }

        return $this;
    }
}
