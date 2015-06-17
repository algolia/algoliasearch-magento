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
        $this->config           = Mage::helper('algoliasearch/config');
        $this->product_helper   = Mage::helper('algoliasearch/entity_producthelper');
        $this->category_helper  = Mage::helper('algoliasearch/entity_categoryhelper');

        $this->helper           = Mage::helper('algoliasearch');
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
        if ($this->config->isPopupEnabled() || $this->config->isInstantEnabled()) {
            $observer->getLayout()->getUpdate()->addHandle('algolia_search_handle');
        }
        return $this;
    }

    public function deleteStoreIndices(Varien_Object $event)
    {
        $storeId = $event->getStoreId();

        $this->helper->deleteStoreIndices($storeId);
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

    public function rebuildPageIndex(Varien_Object $event)
    {
        $storeId = $event->getStoreId();

        $this->helper->rebuiltStorePageIndex($storeId);
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

    /**
     * Inject jquery before prototype
     */
    public function prepareLayoutBefore(Varien_Event_Observer $observer)
    {
        /* @var $block Mage_Page_Block_Html_Head */
        $block = $observer->getEvent()->getBlock();

        if ("head" == $block->getNameInLayout() && Mage::getDesign()->getArea() != 'adminhtml') {
            $block->addJs('../skin/frontend/base/default/algoliasearch/jquery.min.js');
            $block->addJs('../skin/frontend/base/default/algoliasearch/jquery-ui.js');
            $block->addJs('../skin/frontend/base/default/algoliasearch/typeahead.min.js');
            $block->addJs('../skin/frontend/base/default/algoliasearch/jquery.noconflict.js');
            $block->addCss('algoliasearch/jquery-ui.min.css');
        }

        return $this;
    }

    /**
     * Catch request if it is a category page
     */
    public function controllerFrontInitBefore(Varien_Event_Observer $observer)
    {
        if ($this->config->replaceCategories() == false)
            return;
        if ($this->config->isInstantEnabled() == false)
            return;

        if (Mage::app()->getRequest()->getControllerName() == 'category' && Mage::app()->getRequest()->getParam('category') == null)
        {
            $category = Mage::registry('current_category');

            $category->getUrlInstance()->setStore(Mage::app()->getStore()->getStoreId());

            $path = '';

            foreach ($category->getPathIds() as $treeCategoryId) {
                if ($path != '') {
                    $path .= ' /// ';
                }

                $path .= $this->product_helper->getCategoryName($treeCategoryId, Mage::app()->getStore()->getStoreId());
            }

            $indexName = $this->product_helper->getIndexName(Mage::app()->getStore()->getStoreId());

            $url = Mage::app()->getRequest()->getOriginalPathInfo().'?category=1#q=&page=0&refinements=%5B%7B%22categories%22%3A%22'.$path.'%22%7D%5D&numerics_refinements=%7B%7D&index_name=%22'.$indexName.'%22';

            header('Location: '.$url);

            die();
        }
    }
}
