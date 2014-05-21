<?php

require 'algoliasearch.php';

class Algolia_Algoliasearch_Helper_Data extends Mage_Core_Helper_Abstract
{
    const BATCH_SIZE = 100;
    const COLLECTION_PAGE_SIZE = 100;

    const INDEX_MAGENTO_PRODUCTS = 'magento_products';
    const INDEX_MAGENTO_CATEGORIES = 'magento_categories';

    private static $_categoryNames;

    public function getTopSearchTemplate()
    {
        return 'algoliasearch/topsearch.phtml';
    }

    public function getIndex($name)
    {
        return $this->getClient()->initIndex($name);
    }

    public function listIndexes()
    {
        return $this->getClient()->listIndexes();
    }

    public function query($index, $q, $params)
    {
        return $this->getClient()->initIndex($index)->search($q, $params);
    }

    public function getProductJSON(Mage_Catalog_Model_Product $product)
    {
        $categories = array();
        foreach ($product->getCategoryIds() as $categoryId) {
            array_push($categories, $this->getCategoryName($categoryId, $product->getStoreId()));
        }
        $imageUrl = null;
        $thumbnailUrl = null;
        try {
            $thumbnailUrl = $product->getThumbnailUrl();
        } catch (Exception $e) { /* no thumbnail, no default: not fatal */ }
        try {
            $imageUrl = $product->getImageUrl();
        } catch (Exception $e) { /* no image, no default: not fatal */ }
        return array(
            'objectID' => $product->getStore()->getCode() . '_product_' . $product->getId(),
            'name' => $product->getName(),
            'categories' => $categories,
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'url' => $product->getProductUrl(),
            '_tags' => array('product'),
            'thumbnail_url' => $thumbnailUrl,
            'image_url' => $imageUrl
        );
    }

    public function getCategoryJSON(Mage_Catalog_Model_Category $category)
    {
        $path = '';
        foreach ($category->getPathIds() as $categoryId) {
            if ($path != '') {
                $path .= ' / ';
            }
            $path .= $this->getCategoryName($categoryId, $category->getStoreId());
        }
        $imageUrl = null;
        try {
            $imageUrl = $category->getImageUrl();
        } catch (Exception $e) { /* no image, no default: not fatal */ }
        return array(
            'objectID' => $category->getStore()->getCode() . '_category_' . $category->getId(),
            'name' => $category->getName(),
            'path' => $path,
            'level' => $category->getLevel(),
            'url' => $category->getUrl(),
            '_tags' => array('category'),
            'product_count' => $category->getProductCount(),
            'image_url' => $imageUrl
        );
    }

    /**
     * Reindex store categories
     *
     * @param Mage_Core_Model_Store|int $storeId
     * @return void
     * @throws Exception
     */
    public function reindexStoreCategories($storeId)
    {
        if ($storeId instanceof Mage_Core_Model_Store) {
            $storeId = $storeId->getId();
        }

        $oldIsFlatEnabled = Mage::getStoreConfigFlag(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, $storeId);
        Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, FALSE);
        $oldStore = Mage::app()->getStore()->getId();
        Mage::app()->setCurrentStore($storeId);

        try {
            $this->_reindexStoreCategories($storeId);
        } catch (Exception $e) {
            Mage::app()->setCurrentStore($oldStore);
            Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, $oldIsFlatEnabled);
            throw $e;
        }

        Mage::app()->setCurrentStore($oldStore);
        Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, $oldIsFlatEnabled);
    }

    /**
     * Reindex store categories
     *
     * @param Mage_Core_Model_Store|int $storeId
     * @return void
     */
    protected function _reindexStoreCategories($storeId)
    {
        if ($storeId instanceof Mage_Core_Model_Store) {
            $storeId = $storeId->getId();
        }

        // Init categories index
        $indexer = $this->getStoreIndex($storeId);
        $indexer->setSettings(array(
            'attributesToIndex' => array('name', 'path'),
            'customRanking' => array('desc(product_count)')
        ));

        // Categories indexing
        $categories = Mage::getResourceModel('catalog/category_collection'); /** @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection */
        $categories
            ->setProductStoreId($storeId)
            ->addNameToResult()
            ->addUrlRewriteToResult()
            ->addIsActiveFilter()
            ->setLoadProductCount(TRUE)
            ->setStoreId($storeId)
            ->addAttributeToSelect('image');
        $size = $categories->getSize();
        if ($size > 0) {
            $indexData = array();
            $pageSize = self::COLLECTION_PAGE_SIZE;
            $pages = ceil($size/$pageSize);
            $categories->clear();
            $page = 1;
            while ($page <= $pages) {
                $collection = clone $categories;
                $collection->setCurPage($page)->setPageSize($pageSize);
                $collection->load();
                foreach ($collection as $category) { /** @var $category Mage_Catalog_Model_Category */
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

    /**
     * Reindex store products
     *
     * @param Mage_Core_Model_Store|int $storeId
     * @return void
     * @throws Exception
     */
    public function reindexStoreProducts($storeId)
    {
        if ($storeId instanceof Mage_Core_Model_Store) {
            $storeId = $storeId->getId();
        }

        $oldStore = Mage::app()->getStore()->getId();
        Mage::app()->setCurrentStore($storeId);
        $oldUseProductFlat = Mage::getStoreConfigFlag(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, $storeId);
        Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, FALSE);

        try {
            $this->_reindexStoreProducts($storeId);
        } catch (Exception $e) {
            Mage::app()->setCurrentStore($oldStore);
            Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, $oldUseProductFlat);
            throw $e;
        }

        Mage::app()->setCurrentStore($oldStore);
        Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, $oldUseProductFlat);
    }

    /**
     * Reindex store products
     *
     * @param Mage_Core_Model_Store|int $storeId
     * @return void
     */
    protected function _reindexStoreProducts($storeId)
    {
        $indexer = $this->getStoreIndex($storeId);
        $indexer->setSettings(array(
            'attributesToIndex' => array('name', 'categories', 'unordered(description)')
        ));

        // Product indexing
        $products = Mage::getResourceModel('catalog/product_collection'); /** @var $products Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $products
            ->addStoreFilter($storeId)
            ->addAttributeToSelect(array('name', 'url_key', 'description', 'image', 'thumbnail'))
            ->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds())
            ->addFinalPrice();
        $size = $products->getSize();
        if ($size > 0) {
            $indexData = array();
            $pageSize = self::COLLECTION_PAGE_SIZE;
            $pages = ceil($size/$pageSize);
            $products->clear();
            $page = 1;
            while ($page <= $pages) {
                $collection = clone $products;
                $collection->setCurPage($page)->setPageSize($pageSize);
                $collection->load();
                $collection->addCategoryIds();
                $collection->addUrlRewrite();
                foreach ($collection as $product) { /** @var $product Mage_Catalog_Model_Product */
                    array_push($indexData, $this->getProductJSON($product));
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

    public function reindexAll()
    {
        foreach (Mage::app()->getStores() as $store) { /** @var $store Mage_Core_Model_Store */
            $this->reindexStoreCategories($store);
            $this->reindexStoreProducts($store);
        }
    }

    public function getStoreIndex($storeId = NULL)
    {
        return $this->getIndex($this->getIndexName($storeId));
    }

    public function getIndexName($storeId = NULL)
    {
        return (string) $this->getIndexPrefix($storeId) . Mage::app()->getStore($storeId)->getCode();
    }

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
            if ($attribute = Mage::getResourceModel('catalog/category')->getAttribute('name')) {
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read'); /** @var $connection Varien_Db_Adapter_Pdo_Mysql */
                $select = $connection->select()
                    ->from($attribute->getBackendTable(), array(new Zend_Db_Expr("CONCAT(store_id, '-', entity_id)"), 'value'))
                    ->where('entity_type_id = ?', $attribute->getEntityTypeId())
                    ->where('attribute_id = ?', $attribute->getAttributeId());
                self::$_categoryNames = $connection->fetchPairs($select);
            }
        }

        $categoryName = NULL;
        $key = strval($storeId).'-'.strval($categoryId);
        if (isset(self::$_categoryNames[$key])) {
            $categoryName = strval(self::$_categoryNames[$key]);
        } elseif ($storeId != 0) {
            $key = '0-'.strval($categoryId);
            if (isset(self::$_categoryNames[$key])) {
                $categoryName = strval(self::$_categoryNames[$key]);
            }
        }

        return $categoryName;
    }

    private function getClient()
    {
        return new \AlgoliaSearch\Client($this->getApplicationID(), $this->getAPIKey());
    }

    public function getApplicationID()
    {
        return Mage::getStoreConfig('algoliasearch/settings/application_id');
    }

    public function getAPIKey()
    {
        return Mage::getStoreConfig('algoliasearch/settings/api_key');
    }

    public function getSearchOnlyAPIKey()
    {
        return Mage::getStoreConfig('algoliasearch/settings/search_only_api_key');
    }

    public function getIndexPrefix($storeId = NULL)
    {
        return Mage::getStoreConfig('algoliasearch/settings/index_prefix', $storeId);
    }

    public function getNbProductSuggestions()
    {
        return Mage::getStoreConfig('algoliasearch/ui/number_suggestions_product');
    }

    public function getNbCategorySuggestions()
    {
        return Mage::getStoreConfig('algoliasearch/ui/number_suggestions_category');
    }
}
