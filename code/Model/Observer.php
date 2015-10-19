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
            if ($store->getIsActive())
                $this->helper->saveConfigurationToAlgolia($store->getId());
    }

    /**
     * Call algoliasearch.xml To load js / css / phtml
     */
    public function useAlgoliaSearchPopup(Varien_Event_Observer $observer)
    {
        if ($this->config->isEnabledFrontEnd())
        {
            if ($this->config->isPopupEnabled() || $this->config->isInstantEnabled())
            {
                $observer->getLayout()->getUpdate()->addHandle('algolia_search_handle');
            }
        }

        return $this;
    }

    public function saveProduct(Varien_Event_Observer $observer)
    {
        $product = $observer->getDataObject();
        $product = Mage::getModel('catalog/product')->load($product->getId());

        Algolia_Algoliasearch_Model_Indexer_Algolia::$product_categories[$product->getId()] = $product->getCategoryIds();
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
                if ( ! $store->getIsActive())
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
                if ( ! $store->getIsActive())
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
                if ( ! $store->getIsActive())
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

    public function rebuildAfterFSIProductImport($event) {
        $ids = $event->getEntityId();
        $products = Mage::getModel('catalog/product')->getCollection()
            ->addIdFilter($ids);
        $cif = Mage::getResourceSingleton('catalogsearch/indexer_fulltext');
        $updateIds = array();
        $removeIds = array();
        $visibleInSearchIds = Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds();
        foreach ($products as $product)
        {
            if (!$product->isComposite())
            {
                $parentIds = $cif->getRelationsByChild($product->getId());
                if ( ! empty($parentIds))
                    $ids = array_merge($ids, $parentIds);
            }
        }
        $allProducts = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('visibility')
            ->addIdFilter($ids);
        foreach ($allProducts as $product) {
            if (
                ($product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
                ||
                (! in_array($product->getData('visibility'), $visibleInSearchIds))
            )
            {
                $removeIds[] = $product->getId();
            }
            else
            {
                $updateIds[] = $product->getId();
            }
        }
        $engine = new Algolia_Algoliasearch_Model_Resource_Engine();
        $engine->rebuildProductIndex(null, $updateIds);
        $engine->removeProducts(null, $removeIds);
        # As there's no way of no whether these changed reindex the categories
        # they're in to keep numbers up to date
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_read');
        $select = $connection->select()
            ->from($resource->getTableName('catalog/category_product'), 'category_id')
            ->where('product_id IN (?)', $ids);
        $categoryIds = $connection->fetchCol($select);
        if (!empty($categoryIds))
            $engine->rebuildCategoryIndex(null, $categoryIds);

        return $this;
    }
}
