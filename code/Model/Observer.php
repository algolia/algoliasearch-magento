<?php

/**
 * Algolia search observer model
 */
class Algolia_Algoliasearch_Model_Observer
{
    /**
     * Retrieve queue model instance
     *
     * @return Algolia_Algoliasearch_Model_Queue
     */
    public function getQueue()
    {
        return Mage::getSingleton('algoliasearch/queue');
    }

    /**
     * Retrieve store index
     *
     * @param mixed $storeId
     * @return \AlgoliaSearch\Index
     */
    public function getStoreIndex($storeId)
    {
        return Mage::helper('algoliasearch')->getStoreIndex($storeId);
    }

    /**
     * Delete store index
     *
     * @param mixed $storeId
     * @return Algolia_Algoliasearch_Model_Observer
     */
    public function deleteStoreIndex($storeId)
    {
        Mage::helper('algoliasearch')->deleteStoreIndex($storeId);
        return $this;
    }

    /**
     * @return Algolia_Algoliasearch_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('algoliasearch');
    }

    public function configSaved(Varien_Event_Observer $observer)
    {
        foreach (Mage::app()->getStores() as $store) /** @var $store Mage_Core_Model_Store */
            if ($store->getIsActive())
                Mage::helper('algoliasearch')->setIndexSettings($store->getId());
    }

    /**
     * Check whether algolia search popup is allowed
     *
     * @param Varien_Event_Observer $observer
     * @return Algolia_Algoliasearch_Model_Observer
     */
    public function useAlgoliaSearchPopup(Varien_Event_Observer $observer)
    {
        if (Mage::helper('algoliasearch')->isPopupEnabled() || Mage::helper('algoliasearch')->isInstantEnabled()) {
            $observer->getLayout()->getUpdate()->addHandle('algolia_search_handle');
        }
        return $this;
    }

    /**
     * Delete index for the specified entities
     *
     * @param Varien_Object $event
     * @return Algolia_Algoliasearch_Model_Observer
     */
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

    /**
     * Rebuild index for the specified categories
     *
     * @param Varien_Object $event
     * @return Algolia_Algoliasearch_Model_Observer
     */
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

    /**
     * @param int $storeId
     * @param null|int|array $categoryIds
     * @return Algolia_Algoliasearch_Model_Observer
     */
    public function rebuildStoreCategoryIndex($storeId, $categoryIds = NULL)
    {
        $this->getHelper()->rebuildStoreCategoryIndex($storeId, $categoryIds);
        return $this;
    }

    /**
     * Rebuild index for the specified products
     *
     * @param Varien_Object $event
     * @return Algolia_Algoliasearch_Model_Observer
     */
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

    /**
     * Call default rebuild index data to prepare initial data.
     *
     * @param int $storeId
     * @param null|int|array $productIds
     * @return Algolia_Algoliasearch_Model_Observer
     */
    public function rebuildStoreProductIndex($storeId, $productIds = NULL)
    {
        Mage::getResourceModel('algoliasearch/fulltext')->rebuildIndex($storeId, $productIds);
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
        if (Mage::helper('algoliasearch')->replaceCategories() == false)
            return;
        if (Mage::helper('algoliasearch')->isInstantEnabled() == false)
            return;

        if (Mage::app()->getRequest()->getControllerName() == 'category' && Mage::app()->getRequest()->getParam('category') == null)
        {
            //$category = Mage::getModel('catalog/category')->load($categoryId);
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
