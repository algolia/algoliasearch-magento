<?php

/**
 * Algolia search observer model.
 */
class Algolia_Algoliasearch_Model_Observer
{
    /** @var Algolia_Algoliasearch_Helper_Config */
    protected $config;

    /** @var Algolia_Algoliasearch_Helper_Entity_Producthelper */
    protected $product_helper;

    /** @var Algolia_Algoliasearch_Helper_Entity_Categoryhelper **/
    protected $category_helper;

    /** @var Algolia_Algoliasearch_Helper_Entity_Suggestionhelper */
    protected $suggestion_helper;

    /** @var Algolia_Algoliasearch_Helper_Data */
    protected $helper;

    public function __construct()
    {
        $this->config = Mage::helper('algoliasearch/config');
        $this->product_helper = Mage::helper('algoliasearch/entity_producthelper');
        $this->category_helper = Mage::helper('algoliasearch/entity_categoryhelper');
        $this->suggestion_helper = Mage::helper('algoliasearch/entity_suggestionhelper');
        $this->helper = Mage::helper('algoliasearch');
    }

    /**
     * On configuration save
     *
     * @param Varien_Event_Observer $observer
     */
    public function configSaved(Varien_Event_Observer $observer)
    {
        $this->saveSettings();
    }

    public function saveSettings($isFullProductReindex = false)
    {
        if (is_object($isFullProductReindex) && get_class($isFullProductReindex) === 'Varien_Object') {
            $eventData = $isFullProductReindex->getData();
            $isFullProductReindex = $eventData['isFullProductReindex'];
        }

        foreach (Mage::app()->getStores() as $store) {/* @var $store Mage_Core_Model_Store */
            if ($store->getIsActive()) {
                $saveToTmpIndicesToo = ($isFullProductReindex && $this->config->isQueueActive($store->getId()));
                $this->helper->saveConfigurationToAlgolia($store->getId(), $saveToTmpIndicesToo);
            }
        }
    }

    public function addBundleToAdmin(Varien_Event_Observer $observer)
    {
        $req = Mage::app()->getRequest();

        if (strpos($req->getPathInfo(), 'system_config/edit/section/algoliasearch') !== false) {
            $observer->getData('layout')->getUpdate()->addHandle('algolia_bundle_handle');
        }
    }

    /**
     * Call algoliasearch.xml to load JS / CSS / PHTMLs
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function useAlgoliaSearchPopup(Varien_Event_Observer $observer)
    {
        if (!$this->config->isEnabledFrontEnd()) {
            return $this;
        }

        if (!$this->config->getApplicationID() || !$this->config->getAPIKey()) {
            return $this;
        }

        $this->loadAlgoliasearchHandle($observer);

        $this->loadSearchFormHandle($observer);

        $this->loadInstantSearchHandle($observer);

        $this->loadAutocompleteHandle($observer);

        $this->loadPreventBackendRenderingHandle($observer);

        $this->loadAnalyticsHandle($observer);

        return $this;
    }

    public function saveProduct(Varien_Event_Observer $observer)
    {
        if ($this->checkIfIndexerIsManual('algolia_search_indexer')) {
            return;
        }

        $product = $observer->getDataObject();
        $product = Mage::getModel('catalog/product')->load($product->getId());

        Algolia_Algoliasearch_Model_Indexer_Algolia::$product_categories[$product->getId()] = $product->getCategoryIds();
    }

    public function savePage(Varien_Event_Observer $observer)
    {
        if (!$this->config->getApplicationID()
            || !$this->config->getAPIKey()
            || $this->checkIfIndexerIsManual('algolia_search_indexer_pages')) {
            return;
        }

        /** @var Mage_Cms_Model_Page $page */
        $page = $observer->getDataObject();
        $page = Mage::getModel('cms/page')->load($page->getId());

        $storeIds = $page->getStoreId();

        /** @var Algolia_Algoliasearch_Model_Resource_Engine $engine */
        $engine = Mage::getResourceModel('algoliasearch/engine');

        $engine->rebuildPages($storeIds, $page->getId());
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
        $pageIds = $event->getPageIds();

        $this->helper->rebuildStorePageIndex($storeId, $pageIds);
    }

    public function rebuildSuggestionIndex(Varien_Object $event)
    {
        $storeId = $event->getStoreId();

        $page = $event->getPage();
        $pageSize = $event->getPageSize();

        if (is_null($storeId) && !empty($categoryIds)) {
            foreach (Mage::app()->getStores() as $storeId => $store) {
                if (!$store->getIsActive()) {
                    continue;
                }

                $this->helper->rebuildStoreSuggestionIndex($storeId);
            }
        } else {
            if (!empty($page) && !empty($pageSize)) {
                $this->helper->rebuildStoreSuggestionIndexPage($storeId,
                    $this->suggestion_helper->getSuggestionCollectionQuery($storeId), $page, $pageSize);
            } else {
                $this->helper->rebuildStoreSuggestionIndex($storeId);
            }
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

        if (is_null($storeId) && !empty($categoryIds)) {
            foreach (Mage::app()->getStores() as $storeId => $store) {
                if (!$store->getIsActive()) {
                    continue;
                }

                $this->helper->rebuildStoreCategoryIndex($storeId, $categoryIds);
            }
        } else {
            if (!empty($page) && !empty($pageSize)) {
                $this->helper->rebuildStoreCategoryIndexPage($storeId,
                    $this->category_helper->getCategoryCollectionQuery($storeId, $categoryIds), $page, $pageSize);
            } else {
                $this->helper->rebuildStoreCategoryIndex($storeId, $categoryIds);
            }
        }

        return $this;
    }

    public function rebuildProductIndex(Varien_Object $event)
    {
        $storeId = $event->getStoreId();
        $productIds = $event->getProductIds();

        $page = $event->getPage();
        $pageSize = $event->getPageSize();

        $useTmpIndex = (bool) $event->getUseTmpIndex();

        if (is_null($storeId) && !empty($productIds)) {
            foreach (Mage::app()->getStores() as $storeId => $store) {
                if (!$store->getIsActive()) {
                    continue;
                }

                $this->helper->rebuildStoreProductIndex($storeId, $productIds);
            }
        } else {
            if (!empty($page) && !empty($pageSize)) {
                $collection = $this->product_helper->getProductCollectionQuery($storeId, $productIds, $useTmpIndex);
                $this->helper->rebuildStoreProductIndexPage($storeId, $collection, $page, $pageSize, null, $productIds, $useTmpIndex);
            } else {
                $this->helper->rebuildStoreProductIndex($storeId, $productIds);
            }
        }

        return $this;
    }

    public function moveProductsTmpIndex(Varien_Object $event)
    {
        $storeId = $event->getStoreId();

        $this->helper->moveProductsIndex($storeId);
    }

    private function loadAlgoliasearchHandle(Varien_Event_Observer $observer)
    {
        if (!$this->config->isPopupEnabled() && !$this->config->isInstantEnabled()) {
            return;
        }

        $observer->getData('layout')->getUpdate()->addHandle('algolia_search_handle');
    }

    private function loadSearchFormHandle(Varien_Event_Observer $observer)
    {
        if (!$this->config->isDefaultSelector()) {
            return;
        }

        $observer->getData('layout')->getUpdate()->addHandle('algolia_search_handle_with_topsearch');
    }

    private function loadInstantSearchHandle(Varien_Event_Observer $observer)
    {
        if (!$this->config->isInstantEnabled()) {
            return;
        }

        $category = Mage::registry('current_category');
        if ($this->config->replaceCategories() && $category && $category->getDisplayMode() === 'PAGE') {
            return;
        }

        $observer->getData('layout')->getUpdate()->addHandle('algolia_search_handle_instantsearch');
    }

    private function loadAutocompleteHandle(Varien_Event_Observer $observer)
    {
        if ($this->config->isPopupEnabled()) {
            $observer->getData('layout')->getUpdate()->addHandle('algolia_search_handle_autocomplete');
        }
    }

    private function loadPreventBackendRenderingHandle(Varien_Event_Observer $observer)
    {
        if (!$this->config->preventBackendRendering()) {
            return;
        }

        $category = Mage::registry('current_category');
        $backendRenderingDisplayMode = $this->config->getBackendRenderingDisplayMode();
        if ($category && $backendRenderingDisplayMode === 'only_products' && $category->getDisplayMode() === 'PAGE') {
            return;
        }

        $observer->getData('layout')->getUpdate() ->addHandle('algolia_search_handle_prevent_backend_rendering');
    }

    private function loadAnalyticsHandle(Varien_Event_Observer $observer)
    {
        if (!$this->config->isClickConversionAnalyticsEnabled()) {
            return;
        }

        $observer->getData('layout')->getUpdate()->addHandle('algolia_search_handle_click_conversion_analytics');
    }

    private function checkIfIndexerIsManual($indexerCode)
    {
        $isManual = false;

        /** @var $process Mage_Index_Model_Process */
        $process = Mage::getModel('index/process')->load($indexerCode, 'indexer_code');
        if (!is_null($process) && $process->getMode() == Mage_Index_Model_Process::MODE_MANUAL) {
            $isManual = true;
        }

        return $isManual;
    }
}
