<?php

/**
 * Algolia search observer model
 */
class Algolia_Algoliasearch_Model_Observer
{
    private $config;

    public function __construct()
    {
        $this->config = new Algolia_Algoliasearch_Helper_Config();
    }

    public function getQueue()
    {
        return Mage::getSingleton('algoliasearch/queue');
    }

    public function getStoreIndex($storeId)
    {
        return Mage::helper('algoliasearch')->getStoreIndex($storeId);
    }

    public function deleteStoreIndex($storeId)
    {
        Mage::helper('algoliasearch')->deleteStoreIndex($storeId);
        return $this;
    }

    public function getHelper()
    {
        return Mage::helper('algoliasearch');
    }

    public function configSaved(Varien_Event_Observer $observer)
    {
        foreach (Mage::app()->getStores() as $store) /** @var $store Mage_Core_Model_Store */
            if ($store->getIsActive())
                Mage::helper('algoliasearch')->saveConfigurationToAlgolia($store->getId());
    }

    public function useAlgoliaSearchPopup(Varien_Event_Observer $observer)
    {
        if ($this->config->isPopupEnabled() || $this->config->isInstantEnabled()) {
            $observer->getLayout()->getUpdate()->addHandle('algolia_search_handle'); // Call algoliasearch.xml
        }
        return $this;
    }

    public function cleanIndex(Varien_Object $event)
    {
        $storeId = $event->getStoreId();
        $entityId = $event->getEntityId();
        $entity = $event->getEntity();

        if (is_null($storeId) && is_null($entityId)) {
            foreach (Mage::app()->getStores() as $store) { /** @var $store Mage_Core_Model_Store */
                if ( ! $store->getIsActive()) { continue; }
                $this->deleteStoreIndex($store->getId());
            }
        } elseif (is_numeric($storeId) && is_null($entityId)) {
            $this->deleteStoreIndex($storeId);
        } elseif ( ! empty($entityId)) {
            $entityIds = (array) $entityId;
            if (is_numeric($storeId)) {
                $objectIds = array();
                foreach ($entityIds as $id) {
                    $objectIds[] = $entity.'_'.$id;
                }
                $this->getHelper()->getStoreIndex($storeId)->deleteObjects($objectIds);
            } elseif (is_null($storeId)) {
                foreach (Mage::app()->getStores() as $store) { /** @var $store Mage_Core_Model_Store */
                    if ( ! $store->getIsActive()) { continue; }
                    $objectIds = array();
                    foreach ($entityIds as $id) {
                        $objectIds[] = $entity.'_'.$id;
                    }
                    $this->getHelper()->getStoreIndex($store->getId())->deleteObjects($objectIds);
                }
            }
        }
        Mage::getSingleton('algoliasearch/algolia')->resetSearchResults();

        return $this;
    }

    public function rebuildCategoryIndex(Varien_Object $event)
    {
        $storeId = $event->getStoreId();
        $categoryIds = $event->getCategoryIds();

        if (is_null($storeId) && ! empty($categoryIds)) {
            foreach (Mage::app()->getStores() as $storeId => $store) {
                if ( ! $store->getIsActive()) continue;
                $this->rebuildStoreCategoryIndex($storeId, $categoryIds);
            }
        } else {
            $this->rebuildStoreCategoryIndex($storeId, $categoryIds);
        }

        return $this;
    }

    public function rebuildStoreCategoryIndex($storeId, $categoryIds = NULL)
    {
        $this->getHelper()->rebuildStoreCategoryIndex($storeId, $categoryIds);
        return $this;
    }

    public function rebuildProductIndex(Varien_Object $event)
    {
        $storeId = $event->getStoreId();
        $productIds = $event->getProductIds();

        if (is_null($storeId) && ! empty($productIds)) {
            foreach (Mage::app()->getStores() as $storeId => $store) {
                if ( ! $store->getIsActive()) continue;
                $this->rebuildStoreProductIndex($storeId, $productIds);
            }
        } else {
            $this->rebuildStoreProductIndex($storeId, $productIds);
        }

        return $this;
    }

    public function rebuildStoreProductIndex($storeId, $productIds = NULL)
    {
        Mage::helper('algoliasearch')->rebuildStoreProductIndex($storeId, $productIds);
        return $this;
    }

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

                $path .= Mage::helper('algoliasearch')->getCategoryName($treeCategoryId, Mage::app()->getStore()->getStoreId());
            }

            $indexName = Mage::helper('algoliasearch')->getIndexName(Mage::app()->getStore()->getStoreId()).'_products';

            $url = Mage::app()->getRequest()->getOriginalPathInfo().'?category=1#q=&page=0&refinements=%5B%7B%22categories%22%3A%22'.$path.'%22%7D%5D&numerics_refinements=%7B%7D&index_name=%22'.$indexName.'%22';

            header('Location: '.$url);

            die();
        }
    }
}
