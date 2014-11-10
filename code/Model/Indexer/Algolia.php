<?php

/**
 * Algolia search indexer
 */
class Algolia_Algoliasearch_Model_Indexer_Algolia extends Mage_Index_Model_Indexer_Abstract
{
    /**
     * Data key for matching result to be saved in
     */
    const EVENT_MATCH_RESULT_KEY = 'algoliasearch_match_result';

    /**
     * List of searchable attributes
     *
     * @var null|array
     */
    protected $_searchableAttributes = NULL;

    /**
     * List of predefined product attributes. Changing attributes from the list will force reindexing the product.
     *
     * @var array
     */
    static protected $_predefinedProductAttributes = array(
        'name',
        'description',
        'url_key',
        'image',
        'thumbnail',
    );

    /**
     * List of predefined category attributes. Changing attributes from the list will force reindexing the category.
     *
     * @var array
     */
    static protected $_predefinedCategoryAttributes = array(
        'name',
        'url',
        'image_url',
    );

    /**
     * Indexer must be match entities
     *
     * @var array
     */
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

    /**
     * Related Configuration Settings for match (reindex)
     *
     * @var array
     */
    protected $_relatedConfigSettingsReindex = array(
        Mage_CatalogSearch_Model_Fulltext::XML_PATH_CATALOG_SEARCH_TYPE,
        Algolia_Algoliasearch_Helper_Data::XML_PATH_CATEGORY_ATTRIBUTES,
        Algolia_Algoliasearch_Helper_Data::XML_PATH_INDEX_PREFIX,
        Algolia_Algoliasearch_Helper_Data::XML_PATH_INDEX_PRODUCT_COUNT,
        Algolia_Algoliasearch_Helper_Data::XML_PATH_USE_ORDERED_QTY_AS_POPULARITY,
    );

    /**
     * Related Configuration Settings for match (update settings)
     *
     * @var array
     */
    protected $_relatedConfigSettingsUpdate = array(
        Algolia_Algoliasearch_Helper_Data::XML_PATH_CUSTOM_RANKING_ATTRIBUTES,
        Algolia_Algoliasearch_Helper_Data::XML_PATH_CUSTOM_INDEX_SETTINGS,
    );

    /**
     * @return Mage_CatalogSearch_Model_Resource_Indexer_Fulltext|Mage_Core_Model_Mysql4_Abstract
     */
    protected function _getResource()
    {
        return Mage::getResourceSingleton('catalogsearch/indexer_fulltext');
    }

    /**
     * Retrieve Algolia Search instance
     *
     * @return Algolia_Algoliasearch_Model_Algolia
     */
    protected function _getIndexer()
    {
        return Mage::getSingleton('algoliasearch/algolia');
    }

    /**
     * Get Indexer name
     *
     * @return string
     */
    public function getName()
    {
        return Mage::helper('algoliasearch')->__('Algolia Search Index');
    }

    /**
     * Get Indexer description
     *
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('algoliasearch')->__('Rebuild product and category search index');
    }

    /**
     * Check if event can be matched by process
     * Overwrote for check is flat catalog product is enabled and specific save
     * attribute, store, store_group
     *
     * @param Mage_Index_Model_Event $event
     * @return bool
     */
    public function matchEvent(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if (isset($data[self::EVENT_MATCH_RESULT_KEY])) {
            return $data[self::EVENT_MATCH_RESULT_KEY];
        }

        $entity = $event->getEntity();
        if ($entity == Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY) {
            /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
            $attribute = $event->getDataObject();
            if (!$attribute) {
                $result = FALSE;
            } elseif ($event->getType() == Mage_Index_Model_Event::TYPE_SAVE) {
                $result = $attribute->dataHasChangedFor('is_searchable');
            } elseif ($event->getType() == Mage_Index_Model_Event::TYPE_DELETE) {
                $result = $attribute->getIsSearchable();
            } else {
                $result = FALSE;
            }
        } else if ($entity == Mage_Core_Model_Store::ENTITY) {
            if ($event->getType() == Mage_Index_Model_Event::TYPE_DELETE) {
                $result = TRUE;
            } else {
                /* @var $store Mage_Core_Model_Store */
                $store = $event->getDataObject();
                if ($store && $store->isObjectNew()) {
                    $result = TRUE;
                } else {
                    $result = FALSE;
                }
            }
        } else if ($entity == Mage_Core_Model_Store_Group::ENTITY) {
            /* @var $storeGroup Mage_Core_Model_Store_Group */
            $storeGroup = $event->getDataObject();
            if ($storeGroup && $storeGroup->dataHasChangedFor('website_id')) {
                $result = TRUE;
            } else {
                $result = FALSE;
            }
        } else if ($entity == Mage_Core_Model_Config_Data::ENTITY) {
            $data = $event->getDataObject();
            if ($data
              && ( in_array($data->getPath(), $this->_relatedConfigSettingsReindex)
                || in_array($data->getPath(), $this->_relatedConfigSettingsUpdate))
            ) {
                $result = $data->isValueChanged();
            } else {
                $result = FALSE;
            }
        } else {
            $result = parent::matchEvent($event);
        }

        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, $result);

        return $result;
    }

    /**
     * Register indexer required data inside event object
     *
     * @param Mage_Index_Model_Event $event
     */
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
            case Mage_Core_Model_Config_Data::ENTITY:
                $stores = TRUE;
                if ($event->getDataObject()->getScope() == 'stores') {
                    $stores = array($event->getDataObject()->getScopeId());
                } else if ($event->getDataObject()->getScope() == 'websites') {
                    $stores = Mage::app()->getWebsite($event->getDataObject()->getScopeId())->getStoreIds();
                }
                if (in_array($event->getDataObject()->getPath(), $this->_relatedConfigSettingsUpdate)) {
                    $event->addNewData('algoliasearch_update_settings', $stores);
                } else if (in_array($event->getDataObject()->getPath(), $this->_relatedConfigSettingsReindex)) {
                    $event->addNewData('algoliasearch_reindex_all', $stores);
                }
                break;
            case Mage_Core_Model_Store::ENTITY:
            case Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY:
            case Mage_Core_Model_Store_Group::ENTITY:
                $event->addNewData('algoliasearch_reindex_all', TRUE);
                break;
        }
    }

    /**
     * Register data required by catalog product process in event object
     *
     * @param Mage_Index_Model_Event $event
     * @return Algolia_Algoliasearch_Model_Indexer_Algolia
     */
    protected function _registerCatalogProductEvent(Mage_Index_Model_Event $event)
    {
        switch ($event->getType()) {
            case Mage_Index_Model_Event::TYPE_SAVE:
                /** @var $product Mage_Catalog_Model_Product */
                $product = $event->getDataObject();
                // Delete disabled or not visible for search products
                $delete = FALSE;
                if ($product->dataHasChangedFor('status') && $product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
                    $delete = TRUE;
                } elseif ($product->dataHasChangedFor('visibility') && ! in_array($product->getData('visibility'), Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds())) {
                    $delete = TRUE;
                }
                if ($delete) {
                    $event->addNewData('catalogsearch_delete_product_id', $product->getId());
                    if (Mage::helper('algoliasearch')->isIndexProductCount()) {
                        $event->addNewData('catalogsearch_update_category_id', $product->getCategoryIds());
                    }
                } else {
                    $event->addNewData('catalogsearch_update_product_id', $product->getId());
                }
                break;
            case Mage_Index_Model_Event::TYPE_DELETE:
                /** @var $product Mage_Catalog_Model_Product */
                $product = $event->getDataObject();
                $event->addNewData('catalogsearch_delete_product_id', $product->getId());
                if (Mage::helper('algoliasearch')->isIndexProductCount()) {
                    $event->addNewData('catalogsearch_update_category_id', $product->getCategoryIds());
                }
                break;
            case Mage_Index_Model_Event::TYPE_MASS_ACTION:
                /** @var $actionObject Varien_Object */
                $actionObject = $event->getDataObject();

                $reindexData  = array();
                $rebuildIndex = FALSE;

                // Check if status changed
                $attrData = $actionObject->getAttributesData();
                if (isset($attrData['status'])) {
                    $rebuildIndex                        = TRUE;
                    $reindexData['catalogsearch_status'] = $attrData['status'];
                }

                // Check changed websites
                if ($actionObject->getWebsiteIds()) {
                    $rebuildIndex                             = TRUE;
                    $reindexData['catalogsearch_website_ids'] = $actionObject->getWebsiteIds();
                    $reindexData['catalogsearch_action_type'] = $actionObject->getActionType();
                }

                // Check searchable attributes
                $searchableAttributes = array();
                if (is_array($attrData)) {
                    $searchableAttributes = array_intersect($this->_getSearchableAttributes(), array_keys($attrData));
                }
                if (count($searchableAttributes) > 0) {
                    $rebuildIndex                               = TRUE;
                    $reindexData['catalogsearch_force_reindex'] = TRUE;
                }

                // Check predefined product attributes
                $predefinedProductAttributes = array();
                if (is_array($attrData)) {
                    $predefinedProductAttributes = array_intersect(self::$_predefinedProductAttributes, array_keys($attrData));
                }
                if (count($predefinedProductAttributes) > 0) {
                    $rebuildIndex                               = TRUE;
                    $reindexData['catalogsearch_force_reindex'] = TRUE;
                }

                // Register affected products
                if ($rebuildIndex) {
                    $reindexData['catalogsearch_product_ids'] = $actionObject->getProductIds();
                    foreach ($reindexData as $k => $v) {
                        $event->addNewData($k, $v);
                    }
                }
                break;
        }

        return $this;
    }

    /**
     * Register data required by catalog category process in event object
     *
     * @param Mage_Index_Model_Event $event
     * @return Algolia_Algoliasearch_Model_Indexer_Algolia
     */
    protected function _registerCatalogCategoryEvent(Mage_Index_Model_Event $event)
    {
        switch ($event->getType()) {
            case Mage_Index_Model_Event::TYPE_SAVE:
                /** @var $category Mage_Catalog_Model_Category */
                $category   = $event->getDataObject();
                $productIds = $category->getAffectedProductIds();
                if ($category->dataHasChangedFor('is_active') && ! $category->getData('is_active')) {
                    $event->addNewData('catalogsearch_delete_category_id', array_merge(array($category->getId()), $category->getAllChildren(TRUE)));
                    if ($productIds) {
                        $event->addNewData('catalogsearch_update_product_id', $productIds);
                    }
                } elseif ($productIds) {
                    $event->addNewData('catalogsearch_update_product_id', $productIds);
                    $event->addNewData('catalogsearch_update_category_id', array($category->getId()));
                } elseif ($movedCategoryId = $category->getMovedCategoryId()) {
                    $event->addNewData('catalogsearch_update_category_id', array($movedCategoryId));
                } else {
                    $rebuildIndex = FALSE;
                    foreach (array_merge(self::$_predefinedCategoryAttributes, array('is_active')) as $attribute) {
                        if ($category->dataHasChangedFor($attribute)) {
                            $rebuildIndex = TRUE;
                            break;
                        }
                    }
                    if ( ! $rebuildIndex) {
                        foreach (Mage::helper('algoliasearch')->getCategoryAdditionalAttributes($category->getStoreId()) as $attribute) {
                            if ($category->dataHasChangedFor($attribute)) {
                                $rebuildIndex = TRUE;
                                break;
                            }
                        }
                    }
                    if ($rebuildIndex) {
                        $event->addNewData('catalogsearch_update_category_id', array($category->getId()));
                    }
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

    /**
     * Retrieve searchable attributes list
     *
     * @return array
     */
    protected function _getSearchableAttributes()
    {
        if (is_null($this->_searchableAttributes)) {
            /** @var $attributeCollection Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $attributeCollection = Mage::getResourceModel('catalog/product_attribute_collection');
            $attributeCollection->addIsSearchableFilter();
            foreach ($attributeCollection as $attribute) {
                $this->_searchableAttributes[] = $attribute->getAttributeCode();
            }
        }
        return $this->_searchableAttributes;
    }

    /**
     * Check if product is composite
     *
     * @param int $productId
     * @return bool
     */
    protected function _isProductComposite($productId)
    {
        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product')->load($productId);
        return $product->isComposite();
    }

    /**
     * Process event based on event state data
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();

        /*
         * Reindex all products and all categories and update index settings
         */
        if ( ! empty($data['algoliasearch_reindex_all'])) {
            $process = $event->getProcess();
            $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        }
        /*
         * Update index settings but do not reindex any records
         */
        else if ( ! empty($data['algoliasearch_update_settings'])) {
            $this->_getIndexer()->updateIndexSettings($data['algoliasearch_update_settings']);
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
                    $this->_getIndexer()
                        ->rebuildProductIndex(NULL, $parentIds);
                }
            }
            $this->_getIndexer()
                ->cleanProductIndex(NULL, $productId);
            /*
             * Change indexer status as need to reindex related categories to update product count.
             * It's low priority so no need to automatically reindex all related categories after deleting each product.
             * Do not reindex all if affected categories are given or product count is not indexed.
             */
            if ( ! isset($data['catalogsearch_update_category_id']) && Mage::helper('algoliasearch')->isIndexProductCount()) {
                $process = $event->getProcess();
                $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
            }
        }
        /*
         * Clear indexer for the deleted category including all children categories and update index for the related products.
         */
        else if ( ! empty($data['catalogsearch_delete_category_id'])) {
            $categoryIds = $data['catalogsearch_delete_category_id'];
            $this->_getIndexer()
                ->cleanCategoryIndex(NULL, $categoryIds);
            /*
             * Change indexer status as need to reindex related products to update the list of categories.
             * It's low priority so no need to automatically reindex all related products after deleting each category.
             * Do not reindex all if affected products are given or product count is not indexed.
             */
            if ( ! isset($data['catalogsearch_update_product_id']) && Mage::helper('algoliasearch')->isIndexProductCount()) {
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
                            $this->_getIndexer()
                                ->cleanProductIndex($storeId, $productIds);
                        } else if ($actionType == 'add') {
                            $this->_getIndexer()
                                ->rebuildProductIndex($storeId, $productIds);
                        }
                    }
                }
            }
            else if (isset($data['catalogsearch_status'])) {
                $status = $data['catalogsearch_status'];
                if ($status == Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
                    $this->_getIndexer()
                        ->rebuildProductIndex(NULL, $productIds);
                } else {
                    $this->_getIndexer()
                        ->cleanProductIndex(NULL, $productIds);
                }
            }
            else if (isset($data['catalogsearch_force_reindex'])) {
                $this->_getIndexer()
                    ->rebuildProductIndex(NULL, $productIds);
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
            $this->_getIndexer()
                ->rebuildProductIndex(NULL, $productIds);
        }

        /*
         * Reindex categories.
         * Category products are tracked separately. The specified categories are active. See _registerCatalogCategoryEvent().
         */
        if ( ! empty($data['catalogsearch_update_category_id'])) {
            $updateCategoryIds = $data['catalogsearch_update_category_id'];
            $updateCategoryIds = is_array($updateCategoryIds) ? $updateCategoryIds : array($updateCategoryIds);
            $this->_getIndexer()
                ->rebuildCategoryIndex(NULL, $updateCategoryIds);
        }
    }

    /**
     * Rebuild all index data
     */
    public function reindexAll()
    {
        $this->_getIndexer()->rebuildIndex();
    }
}
