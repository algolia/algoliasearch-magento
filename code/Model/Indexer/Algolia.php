<?php

class Algolia_Algoliasearch_Model_Indexer_Algolia extends Mage_Index_Model_Indexer_Abstract
{
    const EVENT_MATCH_RESULT_KEY = 'algoliasearch_match_result';

    /** @var Algolia_Algoliasearch_Model_Resource_Engine */
    private $engine;
    private $config;

    public function __construct()
    {
        parent::__construct();

        $this->engine = new Algolia_Algoliasearch_Model_Resource_Engine();
        $this->config = Mage::helper('algoliasearch/config');
    }

    protected $_matchedEntities = array(
        Mage_Catalog_Model_Product::ENTITY                 => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION,
            Mage_Index_Model_Event::TYPE_DELETE
        ),
        Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY  => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
        ),
        Mage_Core_Model_Store::ENTITY                      => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE
        ),
        Mage_Core_Model_Store_Group::ENTITY                => array(
            Mage_Index_Model_Event::TYPE_SAVE
        ),
        Mage_Core_Model_Config_Data::ENTITY                => array(
            Mage_Index_Model_Event::TYPE_SAVE
        ),
        Mage_Catalog_Model_Convert_Adapter_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        ),
        Mage_Catalog_Model_Category::ENTITY                => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE
        )
    );

    protected function _getResource()
    {
        return Mage::getResourceSingleton('catalogsearch/indexer_fulltext');
    }

    public function getName()
    {
        return Mage::helper('algoliasearch')->__('Algolia Search');
    }

    public function getDescription()
    {
        return Mage::helper('algoliasearch')->__('Rebuild product, category indices.
        Please enable the queueing system to do it asynchronously (CRON) if you have a lot of products in System > Configuration > Algolia Search > Queue configuration');
    }

    public function matchEvent(Mage_Index_Model_Event $event)
    {
        $result = true;

        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, $result);

        return $result;
    }

    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, TRUE);
        switch ($event->getEntity()) {
            case Mage_Catalog_Model_Product::ENTITY:
                $this->_registerCatalogProductEvent($event);
                break;
            case Mage_Catalog_Model_Category::ENTITY:
                $this->_registerCatalogCategoryEvent($event);
                break;
            case Mage_Catalog_Model_Convert_Adapter_Product::ENTITY:
                $event->addNewData('algoliasearch_reindex_all', TRUE);
                break;
            case Mage_Core_Model_Store_Group::ENTITY:
                $event->addNewData('algoliasearch_reindex_all', TRUE);
                break;
        }
    }

    protected function _registerCatalogProductEvent(Mage_Index_Model_Event $event)
    {
        switch ($event->getType()) {
            case Mage_Index_Model_Event::TYPE_SAVE:
                /** @var $product Mage_Catalog_Model_Product */
                $product = $event->getDataObject();
                $delete = FALSE;

                if ($product->dataHasChangedFor('status') && $product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
                {
                    $delete = TRUE;
                }
                elseif ($product->dataHasChangedFor('visibility') && ! in_array($product->getData('visibility'), Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds()))
                {
                    $delete = TRUE;
                }

                if ($delete)
                {
                    $event->addNewData('catalogsearch_delete_product_id', $product->getId());
                    $event->addNewData('catalogsearch_update_category_id', $product->getCategoryIds());
                }
                else
                {
                    $event->addNewData('catalogsearch_update_product_id', $product->getId());
                }

                break;
            case Mage_Index_Model_Event::TYPE_DELETE:

                /** @var $product Mage_Catalog_Model_Product */
                $product = $event->getDataObject();
                $event->addNewData('catalogsearch_delete_product_id', $product->getId());
                $event->addNewData('catalogsearch_update_category_id', $product->getCategoryIds());

                break;
            case Mage_Index_Model_Event::TYPE_MASS_ACTION:
                /** @var $actionObject Varien_Object */
                $actionObject = $event->getDataObject();

                $reindexData  = array();

                // Check if status changed
                $attrData = $actionObject->getAttributesData();

                if (isset($attrData['status']))
                {
                    $reindexData['catalogsearch_status'] = $attrData['status'];
                }

                // Check changed websites
                if ($actionObject->getWebsiteIds())
                {
                    $reindexData['catalogsearch_website_ids'] = $actionObject->getWebsiteIds();
                    $reindexData['catalogsearch_action_type'] = $actionObject->getActionType();
                }

                $reindexData['catalogsearch_force_reindex'] = TRUE;
                $reindexData['catalogsearch_product_ids'] = $actionObject->getProductIds();

                foreach ($reindexData as $k => $v)
                {
                    $event->addNewData($k, $v);
                }

                break;
        }

        return $this;
    }

    protected function _registerCatalogCategoryEvent(Mage_Index_Model_Event $event)
    {
        switch ($event->getType()) {
            case Mage_Index_Model_Event::TYPE_SAVE:

                /** @var $category Mage_Catalog_Model_Category */
                $category   = $event->getDataObject();
                $productIds = $category->getAffectedProductIds();

                if ($category->dataHasChangedFor('is_active') && ! $category->getData('is_active'))
                {
                    $event->addNewData('catalogsearch_delete_category_id', array_merge(array($category->getId()), $category->getAllChildren(TRUE)));

                    if ($productIds)
                    {
                        $event->addNewData('catalogsearch_update_product_id', $productIds);
                    }

                }
                elseif ($productIds)
                {
                    $event->addNewData('catalogsearch_update_product_id', $productIds);
                    $event->addNewData('catalogsearch_update_category_id', array($category->getId()));
                }
                elseif ($movedCategoryId = $category->getMovedCategoryId())
                {
                    $event->addNewData('catalogsearch_update_category_id', array($movedCategoryId));
                }
                else
                {
                    $event->addNewData('catalogsearch_update_category_id', array($category->getId()));
                }

                break;

            case Mage_Index_Model_Event::TYPE_DELETE:

                /** @var $category Mage_Catalog_Model_Category */
                $category = $event->getDataObject();

                $event->addNewData('catalogsearch_delete_category_id', $category->getId());

                break;
        }

        return $this;
    }

    protected function _isProductComposite($productId)
    {
        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product')->load($productId);
        return $product->isComposite();
    }

    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        if (! $this->config->getApplicationID() || ! $this->config->getAPIKey() || ! $this->config->getSearchOnlyAPIKey())
        {
            Mage::getSingleton('adminhtml/session')->addError('Algolia indexing failed: You need to configure your Algolia credentials in System > Configuration > Algolia Search.');
            return;
        }

        $data = $event->getNewData();

        /*
         * Reindex all products and all categories and update index settings
         */
        if ( ! empty($data['algoliasearch_reindex_all'])) {
            $process = $event->getProcess();
            $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        }

        /*
         * Clear index for the deleted product and update index for the related categories.
         * Categories must be reindexed after the product index is deleted if product count is indexed.
         */
        else if ( ! empty($data['catalogsearch_delete_product_id'])) {
            $productId = $data['catalogsearch_delete_product_id'];

            if ( ! $this->_isProductComposite($productId)) {
                $parentIds = $this->_getResource()->getRelationsByChild($productId);
                if ( ! empty($parentIds)) {
                    $this->engine
                        ->rebuildProductIndex(null, $parentIds);
                }
            }

            $this->engine
                ->removeProducts(null, $productId);
            /*
             * Change indexer status as need to reindex related categories to update product count.
             * It's low priority so no need to automatically reindex all related categories after deleting each product.
             * Do not reindex all if affected categories are given or product count is not indexed.
             */
            if ( ! isset($data['catalogsearch_update_category_id'])) {
                $process = $event->getProcess();
                $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
            }
        }
        /*
         * Clear indexer for the deleted category including all children categories and update index for the related products.
         */
        else if ( ! empty($data['catalogsearch_delete_category_id'])) {
            $categoryIds = $data['catalogsearch_delete_category_id'];
            $this->engine
                ->removeCategories(null, $categoryIds);
            /*
             * Change indexer status as need to reindex related products to update the list of categories.
             * It's low priority so no need to automatically reindex all related products after deleting each category.
             * Do not reindex all if affected products are given or product count is not indexed.
             */
            if ( ! isset($data['catalogsearch_update_product_id'])) {
                $process = $event->getProcess();
                $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
            }
        }
        // Mass action
        else if ( ! empty($data['catalogsearch_product_ids'])) {
            $productIds = $data['catalogsearch_product_ids'];
            if ( ! empty($data['catalogsearch_website_ids'])) {
                $websiteIds = $data['catalogsearch_website_ids'];
                $actionType = $data['catalogsearch_action_type'];
                foreach ($websiteIds as $websiteId) {
                    foreach (Mage::app()->getWebsite($websiteId)->getStoreIds() as $storeId) {
                        if ($actionType == 'remove') {
                            $this->engine
                                ->removeProducts($storeId, $productIds);
                        } else if ($actionType == 'add') {
                            $this->engine
                                ->rebuildProductIndex($storeId, $productIds);
                        }
                    }
                }
            }
            else if (isset($data['catalogsearch_status'])) {
                $status = $data['catalogsearch_status'];
                if ($status == Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
                    $this->engine
                        ->rebuildProductIndex(null, $productIds);
                } else {
                    $this->engine
                        ->removeProducts(null, $productIds);
                }
            }
            else if (isset($data['catalogsearch_force_reindex'])) {
                $this->engine
                    ->rebuildProductIndex(null, $productIds);
            }
        }

        /*
         * Reindex products.
         * The products are enabled and visible for search so no need to reindex related categories after reindexing products.
         */
        if ( ! empty($data['catalogsearch_update_product_id'])) {
            $updateProductIds = $data['catalogsearch_update_product_id'];
            $updateProductIds = is_array($updateProductIds) ? $updateProductIds : array($updateProductIds);
            $productIds = $updateProductIds;
            foreach ($updateProductIds as $updateProductId) {
                if ( ! $this->_isProductComposite($updateProductId)) {
                    $parentIds = $this->_getResource()->getRelationsByChild($updateProductId);
                    if ( ! empty($parentIds)) {
                        $productIds = array_merge($productIds, $parentIds);
                    }
                }
            }

            $this->engine
                ->rebuildProductIndex(null, $productIds);
        }

        /*
         * Reindex categories.
         * Category products are tracked separately. The specified categories are active. See _registerCatalogCategoryEvent().
         */
        if ( ! empty($data['catalogsearch_update_category_id'])) {
            $updateCategoryIds = $data['catalogsearch_update_category_id'];
            $updateCategoryIds = is_array($updateCategoryIds) ? $updateCategoryIds : array($updateCategoryIds);
            $this->engine
                ->rebuildCategoryIndex(null, $updateCategoryIds);
        }
    }

    /**
     * Rebuild all index data
     */
    public function reindexAll()
    {
        if (! $this->config->getApplicationID() || ! $this->config->getAPIKey() || ! $this->config->getSearchOnlyAPIKey())
        {
            Mage::getSingleton('adminhtml/session')->addError('Algolia reindexing failed: You need to configure your Algolia credentials in System > Configuration > Algolia Search.');
            return;
        }

        $this->engine->rebuildProductsAndCategories();

        return $this;
    }
}
