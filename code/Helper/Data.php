<?php

require 'algoliasearch.php';

class Algolia_Algoliasearch_Helper_Data extends Mage_Core_Helper_Abstract
{
    const BATCH_SIZE           = 100;
    const COLLECTION_PAGE_SIZE = 100;

    const XML_PATH_MINIMAL_QUERY_LENGTH = 'algoliasearch/ui/minimal_query_length';
    const XML_PATH_SEARCH_DELAY         = 'algoliasearch/ui/search_delay';
    const XML_PATH_NUMBER_SUGGESTIONS   = 'algoliasearch/ui/number_suggestions';

    const XML_PATH_IS_ALGOLIA_SEARCH_ENABLED = 'algoliasearch/settings/is_enabled';
    const XML_PATH_IS_POPUP_ENABLED          = 'algoliasearch/settings/is_popup_enabled';
    const XML_PATH_APPLICATION_ID            = 'algoliasearch/settings/application_id';
    const XML_PATH_API_KEY                   = 'algoliasearch/settings/api_key';
    const XML_PATH_SEARCH_ONLY_API_KEY       = 'algoliasearch/settings/search_only_api_key';
    const XML_PATH_INDEX_PREFIX              = 'algoliasearch/settings/index_prefix';
    const XML_PATH_CATEGORY_ATTRIBUTES       = 'algoliasearch/settings/category_additional_attributes';

    private static $_categoryNames;
    private static $_activeCategories;
    private static $_rootCategoryId = -1;
    private static $_categoryAttributes;

    /**
     * Predefined Magento product attributes that are used to prepare data for indexing
     *
     * @var array
     */
    static private $_predefinedProductAttributes = array('name', 'url_key', 'description', 'image', 'thumbnail');

    /**
     * Predefined product attributes that will be retrieved from the index
     *
     * @var array
     */
    static private $_predefinedProductAttributesToRetrieve = array('name', 'url', 'thumbnail_url', 'categories');

    /**
     * Predefined category attributes that will be retrieved from the index
     *
     * @var array
     */
    static private $_predefinedCategoryAttributesToRetrieve = array('name', 'url', 'image_url', 'product_count');

    /**
     * Predefined special attributes
     *
     * @var array
     */
    static private $_predefinedSpecialAttributes = array('_tags');

    public function getTopSearchTemplate()
    {
        return 'algoliasearch/topsearch.phtml';
    }

    /**
     * @param string $name
     * @return \AlgoliaSearch\Index
     */
    public function getIndex($name)
    {
        return $this->getClient()->initIndex($name);
    }

    public function listIndexes()
    {
        return $this->getClient()->listIndexes();
    }

    public function deleteStoreIndex($storeId = NULL)
    {
        return $this->getClient()->deleteIndex($this->getIndexName($storeId));
    }

    public function query($index, $q, $params)
    {
        return $this->getClient()->initIndex($index)->search($q, $params);
    }

    public function getStoreIndex($storeId = NULL)
    {
        return $this->getIndex($this->getIndexName($storeId));
    }

    public function getIndexName($storeId = NULL)
    {
        return (string)$this->getIndexPrefix($storeId) . Mage::app()->getStore($storeId)->getCode();
    }

    public function setIndexSettings($storeId = NULL)
    {
        $index = $this->getStoreIndex($storeId);
        $index->setSettings($this->getIndexSettings($storeId));
        return $index;
    }

    public function getIndexSettings($storeId)
    {
        $searchableAttributes = Mage::getResourceModel('algoliasearch/fulltext')->getSearchableAttributes();
        $attributesToIndex = array('name', 'path', 'categories', 'unordered(description)');
        foreach ($searchableAttributes as $attribute) {
            array_push($attributesToIndex, $attribute->getAttributeCode());
        }
        foreach ($this->getCategoryAdditionalAttributes($storeId) as $attributeCode) {
            array_push($attributesToIndex, $attributeCode);
        }
        $indexSettings = array(
            'attributesToIndex'    => $attributesToIndex,
            'customRanking'        => array('desc(product_count)'),
            'minWordSizefor1Typo'  => 5,
            'minWordSizefor2Typos' => 10,
        );
        return $indexSettings;
    }

    private function getClient()
    {
        return new \AlgoliaSearch\Client($this->getApplicationID(), $this->getAPIKey());
    }

    /**
     * Return array of all category attributes that can be indexed (all except internal attributes and default attributes for indexing)
     *
     * @return array
     */
    public function getAllCategoryAttributes()
    {
        if (is_null(self::$_categoryAttributes)) {
            self::$_categoryAttributes = array();
            $config = Mage::getSingleton('eav/config'); /** @var $config Mage_Eav_Model_Config */
            $allAttributes = $config->getEntityAttributeCodes('catalog_category');
            $excludedAttributes = array(
                'all_children', 'available_sort_by', 'children', 'children_count', 'custom_apply_to_products',
                'custom_design', 'custom_design_from', 'custom_design_to', 'custom_layout_update', 'custom_use_parent_settings',
                'default_sort_by', 'display_mode', 'filter_price_range', 'global_position', 'image', 'include_in_menu', 'is_active',
                'is_always_include_in_menu', 'is_anchor', 'landing_page', 'level', 'lower_cms_block', 'name',
                'page_layout', 'path', 'path_in_store', 'position', 'small_image', 'thumbnail', 'url_key', 'url_path',
                'visible_in_menu');
            $categoryAttributes = array_diff($allAttributes, $excludedAttributes);
            foreach ($categoryAttributes as $attributeCode) {
                self::$_categoryAttributes[$attributeCode] = $config->getAttribute('catalog_category', $attributeCode)->getFrontendLabel();
            }
        }
        return self::$_categoryAttributes;
    }

    /************/
    /* Indexing */
    /************/

    /**
     * Retrieve object id for the product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    public function getProductObjectId(Mage_Catalog_Model_Product $product)
    {
        return 'product_' . $product->getId();
    }

    /**
     * Retrieve object id for the category
     *
     * @param Mage_Catalog_Model_Category $category
     * @return string
     */
    public function getCategoryObjectId(Mage_Catalog_Model_Category $category)
    {
        return 'category_' . $category->getId();
    }

    /**
     * Prepare product JSON
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array                      $defaultData
     * @return array
     */
    public function getProductJSON(Mage_Catalog_Model_Product $product, $defaultData = array())
    {
        $categories = array();
        foreach ($this->getProductActiveCategories($product) as $categoryId) {
            if ($categoryName = $this->getCategoryName($categoryId, $product->getStoreId())) {
                array_push($categories, $categoryName);
            }
        }
        $imageUrl = NULL;
        $thumbnailUrl = NULL;
        try {
            $thumbnailUrl = $product->getThumbnailUrl();
        } catch (Exception $e) { /* no thumbnail, no default: not fatal */ }
        try {
            $imageUrl = $product->getImageUrl();
        } catch (Exception $e) { /* no image, no default: not fatal */ }
        $customData = array(
            'objectID'      => $this->getProductObjectId($product),
            'name'          => $product->getName(),
            'price'         => $product->getPrice(),
            'url'           => $product->getProductUrl(),
            '_tags'         => array('product'),
        );
        $description = $product->getDescription();
        if ( ! empty($description)) {
            $customData['description'] = $description;
        }
        if ( ! empty($categories)) {
            $customData['categories'] = $categories;
        }
        if ( ! empty($thumbnailUrl)) {
            $customData['thumbnail_url'] = $thumbnailUrl;
        }
        if ( ! empty($imageUrl)) {
            $customData['image_url'] = $imageUrl;
        }
        foreach ($defaultData as $key => $value) {
            $customData[$key] = $value;
        }
        return $customData;
    }

    /**
     * Prepare category JSON
     *
     * @param Mage_Catalog_Model_Category $category
     * @return array
     */
    public function getCategoryJSON(Mage_Catalog_Model_Category $category)
    {
        $storeId = $category->getStoreId();
        $category->getUrlInstance()->setStore($storeId);
        $path = '';
        foreach ($category->getPathIds() as $categoryId) {
            if ($path != '') {
                $path .= ' / ';
            }
            $path .= $this->getCategoryName($categoryId, $storeId);
        }
        $imageUrl = NULL;
        try {
            $imageUrl = $category->getImageUrl();
        } catch (Exception $e) { /* no image, no default: not fatal */
        }
        $data = array(
            'objectID'      => $this->getCategoryObjectId($category),
            'name'          => $category->getName(),
            'path'          => $path,
            'level'         => $category->getLevel(),
            'url'           => $category->getUrl(),
            '_tags'         => array('category'),
            'product_count' => $category->getProductCount(),
        );
        if ( ! empty($imageUrl)) {
            $data['image_url'] = $imageUrl;
        }
        foreach ($this->getCategoryAdditionalAttributes($storeId) as $attributeCode) {
            $value = Mage::getResourceSingleton('algoliasearch/fulltext')->getAttributeValue($attributeCode, $category->getData($attributeCode), $storeId, Mage_Catalog_Model_Category::ENTITY);
            if ($value) {
                $data[$attributeCode] = $value;
            }
        }

        return $data;
    }

    /**
     * Adding product count when load collection is incorrect.
     * The method applies the same limitation as on frontend to get correct product count for the category in the specified store.
     * Product collection will not be loaded so this solution is fast.
     *
     * @param Mage_Catalog_Model_Category $category
     * @return Algolia_Algoliasearch_Helper_Data
     */
    public function addCategoryProductCount(Mage_Catalog_Model_Category $category)
    {
        $productCollection = $category->getProductCollection(); /** @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
        $category->setProductCount($productCollection->addMinimalPrice()->count());
        return $this;
    }

    /**
     * Rebuild store category index
     *
     * @param mixed          $storeId
     * @param null|int|array $categoryIds
     * @return void
     * @throws Exception
     */
    public function rebuildStoreCategoryIndex($storeId, $categoryIds = NULL)
    {
        $oldIsFlatEnabled = Mage::getStoreConfigFlag(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, $storeId);
        Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, FALSE);

        try {
            $storeRootCategoryPath = sprintf('%d/%d', $this->getRootCategoryId(), Mage::app()->getStore($storeId)->getRootCategoryId());
            $indexer = $this->getStoreIndex($storeId);
            $categories = Mage::getResourceModel('catalog/category_collection'); /** @var $categories Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection */
            $categories
                ->addPathFilter($storeRootCategoryPath)
                ->addNameToResult()
                ->addUrlRewriteToResult()
                ->addIsActiveFilter()
                ->setStoreId($storeId)
                ->addAttributeToSelect(array_merge(array('name'), $this->getCategoryAdditionalAttributes($storeId)))
                ->addFieldToFilter('level', array('gt' => 1));
            if ($categoryIds) {
                $categories->addFieldToFilter('entity_id', array('in' => $categoryIds));
            }
            $size = $categories->getSize();
            if ($size > 0) {
                $indexData = array();
                $pageSize = self::COLLECTION_PAGE_SIZE;
                $pages = ceil($size / $pageSize);
                $categories->clear();
                $page = 1;
                while ($page <= $pages) {
                    $collection = clone $categories;
                    $collection->setCurPage($page)->setPageSize($pageSize);
                    $collection->load();
                    foreach ($collection as $category) { /** @var $category Mage_Catalog_Model_Category */
                        if ( ! $this->isCategoryActive($category->getId(), $storeId)) {
                            continue;
                        }
                        $category->setStoreId($storeId);
                        $this->addCategoryProductCount($category);
                        array_push($indexData, $this->getCategoryJSON($category));
                        if (count($indexData) >= self::BATCH_SIZE) {
                            $indexer->addObjects($indexData);
                            $indexData = array();
                        }
                    }
                    $collection->walk('clearInstance');
                    $collection->clear();
                    unset($collection);
                    $page++;
                }
                if (count($indexData) > 0) {
                    $indexer->addObjects($indexData);
                }
                unset($indexData);
            }
        }
        catch (Exception $e)
        {
            Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, $oldIsFlatEnabled);
            throw $e;
        }

        Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, $oldIsFlatEnabled);
    }

    /**
     * Rebuild store product index.
     * Fallback to the default fulltext search indexer to prepare default data.
     * After preparing default data, default data will be combined with custom data for Algolia search.
     *
     * @see Mage_CatalogSearch_Model_Resource_Fulltext::_rebuildStoreIndex()
     * @see Mage_CatalogSearch_Model_Resource_Fulltext::_saveProductIndexes()
     *
     * @param mixed          $storeId
     * @param null|int|array $productIds
     * @param null|array     $defaultData
     * @return void
     * @throws Exception
     */
    public function rebuildStoreProductIndex($storeId, $productIds = NULL, $defaultData = NULL)
    {
        $oldStoreId = Mage::app()->getStore()->getId();
        Mage::app()->setCurrentStore($storeId);
        $oldUseProductFlat = Mage::getStoreConfigFlag(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, $storeId);
        Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, FALSE);

        try {
            $indexer = $this->getStoreIndex($storeId);
            $products = Mage::getResourceModel('catalog/product_collection'); /** @var $products Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
            $products
                ->setStoreId($storeId)
                ->addStoreFilter($storeId)
                ->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds())
                ->addFinalPrice()
                ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                ->addAttributeToSelect(self::$_predefinedProductAttributes);
            if ($productIds) {
                $products->addAttributeToFilter('entity_id', array('in' => $productIds));
            }
            $size = $products->getSize();
            if ($size > 0) {
                $indexData = array();
                $pageSize = self::COLLECTION_PAGE_SIZE;
                $pages = ceil($size / $pageSize);
                $products->clear();
                $page = 1;
                while ($page <= $pages) {
                    $collection = clone $products;
                    $collection->setCurPage($page)->setPageSize($pageSize);
                    $collection->load();
                    $collection->addCategoryIds();
                    $collection->addUrlRewrite();
                    foreach ($collection as $product) { /** @var $product Mage_Catalog_Model_Product */
                        $default = isset($defaultData[$product->getId()]) ? $defaultData[$product->getId()] : array();
                        array_push($indexData, $this->getProductJSON($product, $default));
                        if (count($indexData) >= self::BATCH_SIZE) {
                            $indexer->addObjects($indexData);
                            $indexData = array();
                        }
                    }
                    $collection->walk('clearInstance');
                    $collection->clear();
                    unset($collection);
                    $page++;
                }
                if (count($indexData) > 0) {
                    $indexer->addObjects($indexData);
                }
                unset($indexData);
            }
        }
        catch (Exception $e)
        {
            Mage::app()->setCurrentStore($oldStoreId);
            Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, $oldUseProductFlat);
            throw $e;
        }

        Mage::app()->setCurrentStore($oldStoreId);
        Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, $oldUseProductFlat);
    }

    /***********/
    /* Proxies */
    /***********/

    /**
     * Proxy for category names
     *
     * @param Mage_Catalog_Model_Category|int $categoryId
     * @param Mage_Core_Model_Store|int $storeId
     * @return null|string
     */
    public function getCategoryName($categoryId, $storeId = NULL)
    {
        if ($categoryId instanceof Mage_Catalog_Model_Category) {
            $categoryId = $categoryId->getId();
        }
        if ($storeId instanceof Mage_Core_Model_Store) {
            $storeId = $storeId->getId();
        }
        $categoryId = intval($categoryId);
        $storeId = intval($storeId);

        if (is_null(self::$_categoryNames)) {
            self::$_categoryNames = array();
            $resource = Mage::getResourceModel('catalog/category'); /** @var $resource Mage_Catalog_Model_Resource_Category */
            if ($attribute = $resource->getAttribute('name')) {
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read'); /** @var $connection Varien_Db_Adapter_Pdo_Mysql */
                $select = $connection->select()
                    ->from(array('backend' => $attribute->getBackendTable()), array(new Zend_Db_Expr("CONCAT(backend.store_id, '-', backend.entity_id)"), 'backend.value'))
                    ->join(array('category' => $resource->getTable('catalog/category')), 'backend.entity_id = category.entity_id', array())
                    ->where('backend.entity_type_id = ?', $attribute->getEntityTypeId())
                    ->where('backend.attribute_id = ?', $attribute->getAttributeId())
                    ->where('category.level > ?', 1);
                self::$_categoryNames = $connection->fetchPairs($select);
            }
        }

        $categoryName = NULL;
        $key = $storeId.'-'.$categoryId;
        if (isset(self::$_categoryNames[$key])) { // Check whether the category name is present for the specified store
            $categoryName = strval(self::$_categoryNames[$key]);
        } elseif ($storeId != 0) { // Check whether the category name is present for the default store
            $key = '0-'.$categoryId;
            if (isset(self::$_categoryNames[$key])) {
                $categoryName = strval(self::$_categoryNames[$key]);
            }
        }

        return $categoryName;
    }

    /**
     * Retrieve the list of all active categories
     *
     * @return array
     */
    public function getCategories()
    {
        if (is_null(self::$_activeCategories)) {
            self::$_activeCategories = array();
            $resource = Mage::getResourceModel('catalog/category'); /** @var $resource Mage_Catalog_Model_Resource_Category */
            if ($attribute = $resource->getAttribute('is_active')) {
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read'); /** @var $connection Varien_Db_Adapter_Pdo_Mysql */
                $select = $connection->select()
                    ->from(array('backend' => $attribute->getBackendTable()), array('key' => new Zend_Db_Expr("CONCAT(backend.store_id, '-', backend.entity_id)"), 'category.path', 'backend.value'))
                    ->join(array('category' => $resource->getTable('catalog/category')), 'backend.entity_id = category.entity_id', array())
                    ->where('backend.entity_type_id = ?', $attribute->getEntityTypeId())
                    ->where('backend.attribute_id = ?', $attribute->getAttributeId())
                    ->order('backend.store_id')
                    ->order('backend.entity_id');
                self::$_activeCategories = $connection->fetchAssoc($select);
            }
        }
        return self::$_activeCategories;
    }

    /**
     * Retrieve category path.
     * Category path can be found only for active categories.
     *
     * @param int $categoryId
     * @param null|string $storeId
     * @return null|string
     */
    public function getCategoryPath($categoryId, $storeId = NULL)
    {
        $categories = $this->getCategories();
        $storeId = intval($storeId);
        $categoryId = intval($categoryId);
        $path = NULL;
        $key = $storeId.'-'.$categoryId;
        if (isset($categories[$key])) {
            $path = ($categories[$key]['value'] == 1) ? strval($categories[$key]['path']) : NULL;
        } elseif ($storeId !== 0) {
            $key = '0-'.$categoryId;
            if (isset($categories[$key])) {
                $path = ($categories[$key]['value'] == 1) ? strval($categories[$key]['path']) : NULL;
            }
        }
        return $path;
    }

    /**
     * Check whether specified category is active
     *
     * @param int $categoryId
     * @param null|int $storeId
     * @return bool
     */
    public function isCategoryActive($categoryId, $storeId = NULL)
    {
        $storeId = intval($storeId);
        $categoryId = intval($categoryId);
        // Check whether the specified category is active
        if ($path = $this->getCategoryPath($categoryId, $storeId)) {
            // Check whether all parent categories for the current category are active
            $isActive = TRUE;
            $parentCategoryIds = explode('/', $path);
            // Exclude root category
            if (count($parentCategoryIds) <= 2) {
                return FALSE;
            }
            // Remove root category
            array_shift($parentCategoryIds);
            // Remove current category as it is already verified
            array_pop($parentCategoryIds);
            // Start from the first parent
            $parentCategoryIds = array_reverse($parentCategoryIds);
            foreach ($parentCategoryIds as $parentCategoryId) {
                if ( ! ($parentCategoryPath = $this->getCategoryPath($parentCategoryId, $storeId))) {
                    $isActive = FALSE;
                    break;
                }
            }
            if ($isActive) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Retrieve active categories for the product for the specified store
     *
     * @param int|Mage_Catalog_Model_Product $product
     * @param int $storeId
     * @return array
     */
    public function getProductActiveCategories(Mage_Catalog_Model_Product $product, $storeId = NULL)
    {
        $activeCategories = array();
        foreach ($product->getCategoryIds() as $categoryId) {
            if ($this->isCategoryActive($categoryId, $storeId)) {
                $activeCategories[] = $categoryId;
            }
        }
        return $activeCategories;
    }

    /**
     * Retrieve root category id
     *
     * @return int
     */
    public function getRootCategoryId()
    {
        if (-1 === self::$_rootCategoryId) {
            $collection = Mage::getResourceModel('catalog/category_collection');
            $collection->addFieldToFilter('parent_id', 0);
            $collection->getSelect()->limit(1);
            $rootCategory = $collection->getFirstItem();
            self::$_rootCategoryId = $rootCategory->getId();
        }
        return self::$_rootCategoryId;
    }

    /*************************/
    /* Configuration getters */
    /*************************/

    public function getApplicationID($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_APPLICATION_ID, $storeId);
    }

    public function getAPIKey($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_API_KEY, $storeId);
    }

    public function getSearchOnlyAPIKey($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_SEARCH_ONLY_API_KEY, $storeId);
    }

    public function getIndexPrefix($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_INDEX_PREFIX, $storeId);
    }

    public function getCategoryAdditionalAttributes($storeId = NULL)
    {
        return explode(',', Mage::getStoreConfig(self::XML_PATH_CATEGORY_ATTRIBUTES, $storeId));
    }

    public function getNbSuggestions($storeId = NULL)
    {
        return (int) Mage::getStoreConfig(self::XML_PATH_NUMBER_SUGGESTIONS, $storeId);
    }

    public function getMinimalQueryLength($storeId = NULL)
    {
        return (int) Mage::getStoreConfig(self::XML_PATH_MINIMAL_QUERY_LENGTH, $storeId);
    }

    public function getSearchDelay($storeId = NULL)
    {
        return (int) Mage::getStoreConfig(self::XML_PATH_SEARCH_DELAY, $storeId);
    }

    public function isEnabled($storeId = NULL)
    {
        return (bool) Mage::getStoreConfigFlag(self::XML_PATH_IS_ALGOLIA_SEARCH_ENABLED, $storeId);
    }

    public function isPopupEnabled($storeId = NULL)
    {
        return ($this->isEnabled($storeId) && Mage::getStoreConfigFlag(self::XML_PATH_IS_POPUP_ENABLED, $storeId));
    }

    public function getAttributesToRetrieve()
    {
        return array_merge(
            self::$_predefinedProductAttributesToRetrieve,
            self::$_predefinedCategoryAttributesToRetrieve,
            self::$_predefinedSpecialAttributes
        );
    }
}
