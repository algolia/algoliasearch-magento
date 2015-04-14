<?php

require 'algoliasearch.php';

class Algolia_Algoliasearch_Helper_Data extends Mage_Core_Helper_Abstract
{
    const BATCH_SIZE           = 100;
    const COLLECTION_PAGE_SIZE = 100;

    const XML_PATH_MINIMAL_QUERY_LENGTH             = 'algoliasearch/ui/minimal_query_length';
    const XML_PATH_SEARCH_DELAY                     = 'algoliasearch/ui/search_delay';

    const XML_PATH_IS_ALGOLIA_SEARCH_ENABLED        = 'algoliasearch/credentials/is_enabled';
    const XML_PATH_IS_POPUP_ENABLED                 = 'algoliasearch/credentials/is_popup_enabled';
    const XML_PATH_APPLICATION_ID                   = 'algoliasearch/credentials/application_id';
    const XML_PATH_API_KEY                          = 'algoliasearch/credentials/api_key';
    const XML_PATH_SEARCH_ONLY_API_KEY              = 'algoliasearch/credentials/search_only_api_key';
    const XML_PATH_INDEX_PREFIX                     = 'algoliasearch/credentials/index_prefix';

    const XML_PATH_PRODUCT_ATTRIBUTES               = 'algoliasearch/products/product_additional_attributes';
    const XML_PATH_PRODUCT_CUSTOM_RANKING           = 'algoliasearch/products/custom_ranking_product_attributes';
    const XML_PATH_RESULTS_LIMIT                    = 'algoliasearch/products/results_limit';

    const XML_PATH_CATEGORY_ATTRIBUTES              = 'algoliasearch/categories/category_additional_attributes2';
    const XML_PATH_INDEX_PRODUCT_COUNT              = 'algoliasearch/categories/index_product_count';
    const XML_PATH_CATEGORY_CUSTOM_RANKING          = 'algoliasearch/categories/custom_ranking_category_attributes';

    const XML_PATH_REMOVE_IF_NO_RESULT              = 'algoliasearch/relevance/remove_words_if_no_result';

    const XML_PATH_NUMBER_OF_PRODUCT_SUGGESTIONS    = 'algoliasearch/ui/number_product_suggestions';
    const XML_PATH_NUMBER_OF_CATEGORY_SUGGESTIONS   = 'algoliasearch/ui/number_category_suggestions';
    const XML_PATH_USE_RESULT_CACHE                 = 'algoliasearch/ui/use_result_cache';
    const XML_PATH_SAVE_LAST_QUERY                  = 'algoliasearch/ui/save_last_query';

    private static $_categoryNames;
    private static $_activeCategories;
    private static $_rootCategoryId = -1;
    private static $_categoryAttributes;
    private static $_productAttributes;

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
    static private $_predefinedCategoryAttributesToRetrieve = array('name', 'url', 'image_url');

    /**
     * Predefined special attributes
     *
     * @var array
     */
    static private $_predefinedSpecialAttributes = array('_tags');

    /**
     * Data prefix to retrieve Algolia search specific data for the entity.
     *
     * @var string
     */
    private $_dataPrefix = 'algolia_';

    public function __construct()
    {
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

    public function getStoreProductIndexName($storeId = NULL)
    {
        return $this->getIndexName($storeId).'_products';
    }

    public function getStoreCategoryIndexName($storeId = NULL)
    {
        return $this->getIndexName($storeId).'_categories';
    }

    public function getStoreProductIndex($storeId = NULL)
    {
        return $this->getIndex($this->getIndexName($storeId).'_products');
    }

    public function getStoreCategoryIndex($storeId = NULL)
    {
        return $this->getIndex($this->getIndexName($storeId).'_categories');
    }

    public function getIndexName($storeId = NULL)
    {
        return (string)$this->getIndexPrefix($storeId) . Mage::app()->getStore($storeId)->getCode();
    }

    public function mergeSettings($index_name, $settings)
    {
        $onlineSettings = array();

        try
        {
            $onlineSettings = $this->getIndex($index_name)->getSettings();
        }
        catch(\Exception $e)
        {
        }

        $removes = array('slaves');

        foreach ($removes as $remove)
            if (isset($onlineSettings[$remove]))
                unset($onlineSettings[$remove]);

        foreach ($settings as $key => $value)
            $onlineSettings[$key] = $value;

        return $onlineSettings;
    }

    public function setIndexSettings($storeId = NULL)
    {
        $index = $this->getStoreProductIndex($storeId);
        $index->setSettings($this->getProductIndexSettings($storeId));

        $index = $this->getStoreCategoryIndex($storeId);
        $index->setSettings($this->getCategoryIndexSettings($storeId));
    }

    public function getProductIndexSettings($storeId)
    {
        $attributesToIndex = array();

        foreach ($this->getProductAdditionalAttributes($storeId) as $attribute)
        {
            if ($attribute['searchable'] == '1')
            {
                if ($attribute['order'] == 'ordered')
                    $attributesToIndex[] = $attribute['attribute'];
                else
                    $attributesToIndex[] = 'unordered('.$attribute['attribute'].')';
            }
        }

        $customRankings = $this->getProductCustomRanking($storeId);

        $customRankingsArr = array();

        foreach ($customRankings as $ranking)
            $customRankingsArr[] =  $ranking['order'] . '(' . $ranking['attribute'] . ')';

        $indexSettings = array(
            'attributesToIndex'    => array_values(array_unique($attributesToIndex)),
            'customRanking'        => $customRankingsArr
        );

        // Additional index settings from event observer
        $transport = new Varien_Object($indexSettings);
        Mage::dispatchEvent('algolia_index_settings_prepare', array('store_id' => $storeId, 'index_settings' => $transport));
        $indexSettings = $transport->getData();

        $this->mergeSettings($this->getStoreProductIndexName($storeId), $indexSettings);

        return $indexSettings;
    }

    public function getCategoryIndexSettings($storeId)
    {
        $attributesToIndex = array();

        foreach ($this->getCategoryAdditionalAttributes($storeId) as $attribute)
        {
            if ($attribute['searchable'] == '1')
            {
                if ($attribute['order'] == 'ordered')
                    $attributesToIndex[] = $attribute['attribute'];
                else
                    $attributesToIndex[] = 'unordered('.$attribute['attribute'].')';
            }
        }

        $customRankings = $this->getCategoryCustomRanking($storeId);

        $customRankingsArr = array();

        foreach ($customRankings as $ranking)
            $customRankingsArr[] =  $ranking['order'] . '(' . $ranking['attribute'] . ')';

        // Default index settings
        $indexSettings = array(
            'attributesToIndex'    => array_values(array_unique($attributesToIndex)),
            'customRanking'        => $customRankingsArr
        );

        // Additional index settings from event observer
        $transport = new Varien_Object($indexSettings);
        Mage::dispatchEvent('algolia_index_settings_prepare', array('store_id' => $storeId, 'index_settings' => $transport));
        $indexSettings = $transport->getData();

        $this->mergeSettings($this->getStoreCategoryIndexName($storeId), $indexSettings);

        return $indexSettings;
    }

    private function getClient()
    {
        return new \AlgoliaSearch\Client($this->getApplicationID(), $this->getAPIKey());
    }

    /**
     * Get array of [product_id => [relevance]
     *
     * @param $queryText
     * @param $storeId
     * @return array
     * @throws Exception
     */
    public function getSearchResult($queryText, $storeId)
    {
        Varien_Profiler::start('Algolia-FullText-getSearchResult');
        try {
            $resultsLimit = $this->getResultsLimit($storeId);
            $answer = $this->query($this->getStoreProductIndexName($storeId), $queryText, array(
                'hitsPerPage' => max(5, min($resultsLimit, 1000)), // retrieve all the hits (hard limit is 1000)
                'attributesToRetrieve' => 'objectID',
                'attributesToHighlight' => '',
                'attributesToSnippet' => '',
                'removeWordsIfNoResult'=> $this->getRemoveWordsIfNoResult($storeId),
            ));

        } catch (Exception $e) {
            Varien_Profiler::stop('Algolia-FullText-getSearchResult');
            throw $e;
        }

        $data = array();
        foreach ($answer['hits'] as $i => $hit) {
            $objectIdParts = explode('_', $hit['objectID'], 2);
            $productId = ! empty($objectIdParts[1]) && ctype_digit($objectIdParts[1]) ? (int)$objectIdParts[1] : NULL;
            if ($productId) {
                $data[$productId] = $resultsLimit - $i;
            }
        }
        Varien_Profiler::stop('Algolia-FullText-getSearchResult');

        return $data;
    }

    /**
     * Return array of all product attributes that can be indexed (all except internal attributes and default attributes for indexing)
     *
     * @return array
     */
    public function getAllProductAttributes()
    {
        if (is_null(self::$_productAttributes))
        {
            self::$_productAttributes = array();

            /** @var $config Mage_Eav_Model_Config */
            $config = Mage::getSingleton('eav/config');

            $allAttributes = $config->getEntityAttributeCodes('catalog_product');

            $productAttributes = array_merge(array('name', 'path', 'categories', 'description', 'ordered_qty', 'stock_qty'), $allAttributes);

            $excludedAttributes = array(
                'all_children', 'available_sort_by', 'children', 'children_count', 'custom_apply_to_products',
                'custom_design', 'custom_design_from', 'custom_design_to', 'custom_layout_update', 'custom_use_parent_settings',
                'default_sort_by', 'display_mode', 'filter_price_range', 'global_position', 'image', 'include_in_menu', 'is_active',
                'is_always_include_in_menu', 'is_anchor', 'landing_page', 'level', 'lower_cms_block',
                'page_layout', 'path_in_store', 'position', 'small_image', 'thumbnail', 'url_key', 'url_path',
                'visible_in_menu');

            $productAttributes = array_diff($productAttributes, $excludedAttributes);

            foreach ($productAttributes as $attributeCode)
                self::$_productAttributes[$attributeCode] = $config->getAttribute('catalog_category', $attributeCode)->getFrontendLabel();
        }

        return self::$_productAttributes;
    }

    /**
     * Return array of all category attributes that can be indexed (all except internal attributes and default attributes for indexing)
     *
     * @return array
     */
    public function getAllCategoryAttributes()
    {
        if (is_null(self::$_categoryAttributes))
        {
            self::$_categoryAttributes = array();

            /** @var $config Mage_Eav_Model_Config */
            $config = Mage::getSingleton('eav/config');

            $allAttributes = $config->getEntityAttributeCodes('catalog_category');

            $categoryAttributes = array_merge($allAttributes, array('product_count'));

            $excludedAttributes = array(
                'all_children', 'available_sort_by', 'children', 'children_count', 'custom_apply_to_products',
                'custom_design', 'custom_design_from', 'custom_design_to', 'custom_layout_update', 'custom_use_parent_settings',
                'default_sort_by', 'display_mode', 'filter_price_range', 'global_position', 'image', 'include_in_menu', 'is_active',
                'is_always_include_in_menu', 'is_anchor', 'landing_page', 'level', 'lower_cms_block',
                'page_layout', 'path_in_store', 'position', 'small_image', 'thumbnail', 'url_key', 'url_path',
                'visible_in_menu');

            $categoryAttributes = array_diff($categoryAttributes, $excludedAttributes);

            foreach ($categoryAttributes as $attributeCode)
                self::$_categoryAttributes[$attributeCode] = $config->getAttribute('catalog_category', $attributeCode)->getFrontendLabel();
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

    private function try_cast($value)
    {
        if (is_numeric($value) && intval($value) == intval(floatval($value)))
            return intval($value);

        if (is_numeric($value))
            return floatval($value);

        return $value;
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
        $transport = new Varien_Object($defaultData);
        Mage::dispatchEvent('algolia_product_index_before', array('product' => $product, 'custom_data' => $transport));
        $defaultData = $transport->getData();

        $defaultData = is_array($defaultData) ? $defaultData : explode("|",$defaultData);

        $categories = array();
        foreach ($this->getProductActiveCategories($product, $product->getStoreId()) as $categoryId) {
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
        );

        $report = Mage::getResourceModel('reports/product_sold_collection')
            ->addOrderedQty()
            ->setStoreId($product->getStoreId())
            ->addStoreFilter($product->getStoreId())
            ->addFieldToFilter('entity_id', $product->getId())
            ->getFirstItem();

        $customData['ordered_qty'] = intval($report->getOrderedQty());
        $customData['stock_qty'] = (int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();

        if ($product->getTypeId() == 'configurable')
        {
            $sub_products   = $product->getTypeInstance(true)->getUsedProducts(null, $product);
            $ordered_qty    = 0;
            $stock_qty      = 0;

            foreach ($sub_products as $sub_product)
            {
                $stock_qty += (int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($sub_product)->getQty();

                $report = Mage::getResourceModel('reports/product_sold_collection')
                    ->addOrderedQty()
                    ->setStoreId($sub_product->getStoreId())
                    ->addStoreFilter($sub_product->getStoreId())
                    ->addFieldToFilter('entity_id', $sub_product->getId())
                    ->getFirstItem();

                $ordered_qty += intval($report->getOrderedQty());
            }

            $customData['ordered_qty']  = $ordered_qty;
            $customData['stock_qty']    = $stock_qty;
        }

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

        $storeId = $product->getStoreId();

        foreach ($this->getProductAdditionalAttributes($storeId) as $attribute)
        {
            $value = $product->hasData($this->_dataPrefix.$attribute['attribute'])
                ? $product->getData($this->_dataPrefix.$attribute['attribute'])
                : $product->getData($attribute['attribute']);

            $value = Mage::getResourceSingleton('algoliasearch/fulltext')->getAttributeValue($attribute['attribute'], $value, $storeId, Mage_Catalog_Model_Product::ENTITY);
            if ($value) {
                $customData[$attribute['attribute']] = $value;
            }
        }

        $customData = array_merge($customData, $defaultData);


        if (isset($customData['price'])) {
            $customData['price'] = floatval($customData['price']);
        }

        $customData['type_id'] = $product->getTypeId();

        foreach ($customData as &$data)
        {
            $data = $this->try_cast($data);

            if (is_array($data) === false)
                $data = explode('|', $data);

            if (count($data) == 1)
            {
                $data = $data[0];
                $data = $this->try_cast($data);
            }
            else
                foreach($data as &$element)
                    $element = $this->try_cast($element);
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
        $transport = new Varien_Object();
        Mage::dispatchEvent('algolia_category_index_before', array('category' => $category, 'custom_data' => $transport));
        $customData = $transport->getData();

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
            'popularity'    => 1,
            'product_count' => $category->getProductCount()
        );



        if ( ! empty($imageUrl)) {
            $data['image_url'] = $imageUrl;
        }
        foreach ($this->getCategoryAdditionalAttributes($storeId) as $attribute) {
            $value = $category->hasData($this->_dataPrefix.$attribute['attribute'])
                ? $category->getData($this->_dataPrefix.$attribute['attribute'])
                : $category->getData($attribute['attribute']);

            $value = Mage::getResourceSingleton('algoliasearch/fulltext')->getAttributeValue($attribute['attribute'], $value, $storeId, Mage_Catalog_Model_Category::ENTITY);

            if (isset($data[$attribute['attribute']]))
                $value = $data[$attribute['attribute']];

            if ($value)
                $data[$attribute['attribute']] = $value;
        }

        $data = array_merge($data, $customData);

        foreach ($data as &$data0)
            $data0 = $this->try_cast($data0);

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
        $emulationInfo = $this->startEmulation($storeId);

        try {
            $storeRootCategoryPath = sprintf('%d/%d', $this->getRootCategoryId(), Mage::app()->getStore($storeId)->getRootCategoryId());

            $indexer = $this->getStoreCategoryIndex($storeId);

            $categories = Mage::getResourceModel('catalog/category_collection'); /** @var $categories Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection */

            $unserializedCategorysAttrs = unserialize(Mage::getStoreConfig(self::XML_PATH_CATEGORY_ATTRIBUTES, $storeId));

            $additionalAttr = array();

            foreach ($unserializedCategorysAttrs as $attr)
                $additionalAttr[] = $attr['attribute'];

            $categories
                ->addPathFilter($storeRootCategoryPath)
                ->addNameToResult()
                ->addUrlRewriteToResult()
                ->addIsActiveFilter()
                ->setStoreId($storeId)
                ->addAttributeToSelect(array_merge(array('name'), $additionalAttr))
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
                while ($page <= $pages)
                {
                    $collection = clone $categories;
                    $collection->setCurPage($page)->setPageSize($pageSize);
                    $collection->load();
                    foreach ($collection as $category)
                    {
                        /** @var $category Mage_Catalog_Model_Category */
                        if ( ! $this->isCategoryActive($category->getId(), $storeId))
                            continue;

                        $category->setStoreId($storeId);

                        $this->addCategoryProductCount($category);

                        $category_obj = $this->getCategoryJSON($category);

                        if ($category_obj['product_count'] > 0)
                            array_push($indexData, $category_obj);

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
            $this->stopEmulation($emulationInfo);
            throw $e;
        }

        $this->stopEmulation($emulationInfo);
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
    public function rebuildStoreProductIndex($storeId, $productIds, $defaultData = NULL)
    {
        $emulationInfo = $this->startEmulation($storeId);

        try {
            $indexer = $this->getStoreProductIndex($storeId);
            $products = Mage::getResourceModel('catalog/product_collection'); /** @var $products Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */

            $additionalAttr = array();

            $unserializedProductsAttrs = unserialize(Mage::getStoreConfig(self::XML_PATH_PRODUCT_ATTRIBUTES, $storeId));

            foreach ($unserializedProductsAttrs as $attr)
                $additionalAttr[] = $attr['attribute'];

            $products
                ->setStoreId($storeId)
                ->addStoreFilter($storeId)
                ->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds())
                ->addFinalPrice()
                ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                ->addAttributeToSelect(array_merge(self::$_predefinedProductAttributes, $additionalAttr))
                ->addAttributeToFilter('entity_id', array('in' => $productIds));

            Mage::dispatchEvent('algolia_rebuild_store_product_index_collection_load_before', array('store' => $storeId, 'collection' => $products));
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
                        $product->setStoreId($storeId);
                        $default = isset($defaultData[$product->getId()]) ? (array) $defaultData[$product->getId()] : array();

                        $json = $this->getProductJSON($product, $default);

                        array_push($indexData, $json);
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
            $this->stopEmulation($emulationInfo);
            throw $e;
        }

        $this->stopEmulation($emulationInfo);
    }

    /**
     * Start store emulation. Disable product and category flat catalog.
     *
     * @param mixed $storeId
     * @return Varien_Object
     */
    public function startEmulation($storeId)
    {
        $info = new Varien_Object;
        $info->setInitialStoreId(Mage::app()->getStore()->getId());
        $info->setEmulatedStoreId($storeId);
        $info->setUseProductFlat(Mage::getStoreConfigFlag(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, $storeId));
        $info->setUseCategoryFlat(Mage::getStoreConfigFlag(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, $storeId));
        Mage::app()->setCurrentStore($storeId);
        Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, FALSE);
        Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, FALSE);
        return $info;
    }

    /**
     * Stop store emulation. Restore product and category flat catalog configuration.
     *
     * @param Varien_Object $info
     * @return void
     */
    public function stopEmulation($info)
    {
        Mage::app()->setCurrentStore($info->getInitialStoreId());
        Mage::app()->getStore($info->getEmulatedStoreId())->setConfig(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, $info->getUseProductFlat());
        Mage::app()->getStore($info->getEmulatedStoreId())->setConfig(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, $info->getUseCategoryFlat());
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
        $attrs = unserialize(Mage::getStoreConfig(self::XML_PATH_CATEGORY_ATTRIBUTES, $storeId));

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getProductAdditionalAttributes($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::XML_PATH_PRODUCT_ATTRIBUTES, $storeId));

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getCategoryCustomRanking($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::XML_PATH_CATEGORY_CUSTOM_RANKING, $storeId));

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getProductCustomRanking($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::XML_PATH_PRODUCT_CUSTOM_RANKING, $storeId));

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getNbSuggestions($storeId = NULL)
    {
        return (int) Mage::getStoreConfig(self::XML_PATH_NUMBER_SUGGESTIONS, $storeId);
    }

    public function getSaveLastQuery($storeId = NULL)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_SAVE_LAST_QUERY, $storeId);
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

    public function getRemoveWordsIfNoResult($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_REMOVE_IF_NO_RESULT, $storeId);
    }

    public function getCustomRankings($storeId = NULL) {
        return unserialize(Mage::getStoreConfig(self::XML_PATH_CUSTOM_RANKING_ATTRIBUTES, $storeId));
    }

    public function getNumberOfProductSuggestions($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_NUMBER_OF_PRODUCT_SUGGESTIONS, $storeId);
    }

    public function getNumberOfCategorySuggestions($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_NUMBER_OF_CATEGORY_SUGGESTIONS, $storeId);
    }

    public function getResultsLimit($storeId = NULL)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_RESULTS_LIMIT, $storeId);
    }

    public function useResultCache($storeId = NULL)
    {
        return (bool) Mage::getStoreConfigFlag(self::XML_PATH_USE_RESULT_CACHE, $storeId);
    }

}
