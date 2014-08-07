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

    /**
     * Check whether algolia search popup is allowed
     *
     * @param Varien_Event_Observer $observer
     * @return Algolia_Algoliasearch_Model_Observer
     */
    public function useAlgoliaSearchPopup(Varien_Event_Observer $observer)
    {
        if (Mage::helper('algoliasearch')->isPopupEnabled()) {
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
                $this->deleteStoreIndex($store->getId());
            }
        } elseif (is_numeric($storeId) && is_null($entityId)) {
            $this->deleteStoreIndex($storeId);
        } elseif ( ! empty($entityId)) {
            $entityIds = (array) $entityId;
            if (is_numeric($storeId)) {
                $objectIds = array();
                $store = Mage::app()->getStore($storeId);
                foreach ($entityIds as $id) {
                    $objectIds[] = $store->getCode().'_'.$entity.'_'.$id;
                }
                $this->getHelper()->getStoreIndex($storeId)->deleteObjects($objectIds);
            } elseif (is_null($storeId)) {
                foreach (Mage::app()->getStores() as $store) { /** @var $store Mage_Core_Model_Store */
                    $objectIds = array();
                    foreach ($entityIds as $id) {
                        $objectIds[] = $store->getCode().'_'.$entity.'_'.$id;
                    }
                    $this->getHelper()->getStoreIndex($store->getId())->deleteObjects($objectIds);
                }
            }
        }

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
}
