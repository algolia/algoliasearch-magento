<?php

require_once 'AlgoliaSearch/Version.php';
require_once 'AlgoliaSearch/AlgoliaException.php';
require_once 'AlgoliaSearch/ClientContext.php';
require_once 'AlgoliaSearch/Client.php';
require_once 'AlgoliaSearch/Index.php';

class Algolia_Algoliasearch_Helper_Data extends Mage_Core_Helper_Abstract
{
    const BATCH_SIZE           = 100;
    const COLLECTION_PAGE_SIZE = 100;

    private static $_rootCategoryId = -1;


    static private $_predefinedProductAttributes = array('name', 'url_key', 'description', 'image', 'thumbnail');

    private $algolia_helper;

    private $page_helper;
    private $category_helper;
    private $product_helper;

    private $config;

    public function __construct()
    {
        \AlgoliaSearch\Version::$custom_value = " Magento (1.1.4)";

        $this->algolia_helper   = new Algolia_Algoliasearch_Helper_Algoliahelper();

        $this->page_helper      = new Algolia_Algoliasearch_Helper_Entity_Pagehelper();
        $this->category_helper  = new Algolia_Algoliasearch_Helper_Entity_Categoryhelper();
        $this->product_helper   = new Algolia_Algoliasearch_Helper_Entity_Producthelper();

        $this->config           = new Algolia_Algoliasearch_Helper_Config();
    }

    public function deleteStoreIndices($storeId = null)
    {
        $this->algolia_helper->deleteIndex($this->product_helper->getIndexName($storeId));
        $this->algolia_helper->deleteIndex($this->category_helper->getIndexName($storeId));
        $this->algolia_helper->deleteIndex($this->page_helper->getIndexName($storeId));
    }

    public function saveConfigurationToAlgolia($storeId = null)
    {
        $this->algolia_helper->setSettings($this->product_helper->getIndexName($storeId), $this->product_helper->getIndexSettings($storeId));
        $this->algolia_helper->setSettings($this->category_helper->getIndexName($storeId), $this->category_helper->getIndexSettings($storeId));
        $this->algolia_helper->setSettings($this->page_helper->getIndexName($storeId), $this->page_helper->getIndexSettings($storeId));
    }

    public function getSearchResult($query, $storeId)
    {
        $resultsLimit = $this->config->getResultsLimit($storeId);

        $answer = $this->algolia_helper->query($this->product_helper->getIndexName($storeId), $query, array(
            'hitsPerPage' => max(5, min($resultsLimit, 1000)), // retrieve all the hits (hard limit is 1000)
            'attributesToRetrieve' => 'objectID',
            'attributesToHighlight' => '',
            'attributesToSnippet' => '',
            'removeWordsIfNoResult'=> $this->config->getRemoveWordsIfNoResult($storeId)
        ));

        $data = array();

        foreach ($answer['hits'] as $i => $hit)
        {
            $productId = $hit['objectID'];

            if ($productId)
                $data[$productId] = $resultsLimit - $i;
        }

        return $data;
    }

    private function getStores($store_id)
    {
        $store_ids = array();

        if ($store_id == null)
        {
            foreach (Mage::app()->getStores() as $store)
                if ($store->getIsActive())
                    $store_ids[] = $store->getId();
        }
        else
            $store_ids = array($store_id);

        return $store_ids;
    }

    public function removeProducts($ids, $store_id = null)
    {
        $store_ids = $this->getStores($store_id);

        foreach ($store_ids as $store_id)
        {
            $index = $this->getIndex($this->getIndexName($store_id).'_products');

            $index->deleteObjects($ids);
        }
    }

    public function removeCategories($ids, $store_id = null)
    {
        $store_ids = $this->getStores($store_id);

        foreach ($store_ids as $store_id)
        {
            $index = $this->getIndex($this->getIndexName($store_id).'_categories');

            $index->deleteObjects($ids);
        }
    }


    public function rebuiltStorePageIndex($storeId)
    {
        $index_name = $this->page_helper->getIndexName($storeId);

        $pages = $this->page_helper->getPages($storeId);

        foreach (array_chunk($pages, 100) as $chunk)
            $this->algolia_helper->addObjects($chunk, $index_name.'_tmp');

        $this->algolia_helper->moveIndex($index_name.'_tmp', $index_name);

        $this->algolia_helper->setSettings($index_name, $this->page_helper->getIndexSettings($storeId));
    }

    public function rebuildStoreCategoryIndex($storeId, $categoryIds = null)
    {
        $emulationInfo = $this->startEmulation($storeId);

        try
        {
            $storeRootCategoryPath = sprintf('%d/%d', $this->getRootCategoryId(), Mage::app()->getStore($storeId)->getRootCategoryId());

            $index_name = $this->category_helper->getIndexName($storeId);

            $categories = Mage::getResourceModel('catalog/category_collection'); /** @var $categories Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection */

            $unserializedCategorysAttrs = $this->config->getCategoryAdditionalAttributes($storeId);

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
                        if ( ! $this->category_helper->isCategoryActive($category->getId(), $storeId))
                            continue;

                        $category->setStoreId($storeId);

                        $category_obj = $this->category_helper->getObject($category);

                        if ($category_obj['product_count'] > 0)
                            array_push($indexData, $category_obj);

                        if (count($indexData) >= self::BATCH_SIZE) {
                            $this->algolia_helper->addObjects($indexData, $index_name);
                            $indexData = array();
                        }
                    }
                    $collection->walk('clearInstance');
                    $collection->clear();
                    unset($collection);
                    $page++;
                }
                if (count($indexData) > 0) {
                    $this->algolia_helper->addObjects($indexData, $index_name);
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

    public function isEnabled($storeId = NULL)
    {
        return $this->config->isEnabled($storeId);
    }

    public function rebuildStoreProductIndex($storeId, $productIds, $defaultData = null)
    {
        if (count($productIds) > 1)
            $this->rebuiltStorePageIndex($storeId);

        $emulationInfo = $this->startEmulation($storeId);

        try
        {
            $index_name = $this->product_helper->getIndexName($storeId);

            /** @var $products Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
            $products = Mage::getResourceModel('catalog/product_collection');

            $additionalAttr = $this->config->getProductAdditionalAttributes($storeId);

            foreach ($additionalAttr as &$attr)
                $attr = $attr['attribute'];

            $products
                ->setStoreId($storeId)
                ->addStoreFilter($storeId)
                ->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds())
                ->addFinalPrice()
                ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                ->addAttributeToSelect(array_merge(self::$_predefinedProductAttributes, $additionalAttr));

            if ($productIds && count($productIds) > 0)
                $products->addAttributeToFilter('entity_id', array('in' => $productIds));

            Mage::dispatchEvent('algolia_rebuild_store_product_index_collection_load_before', array('store' => $storeId, 'collection' => $products));
            $size = $products->getSize();

            if ($size > 0)
            {
                $indexData = array();
                $pageSize = self::COLLECTION_PAGE_SIZE;
                $pages = ceil($size / $pageSize);
                $products->clear();
                $page = 1;

                while ($page <= $pages)
                {
                    $collection = clone $products;
                    $collection->setCurPage($page)->setPageSize($pageSize);
                    $collection->load();
                    $collection->addCategoryIds();
                    $collection->addUrlRewrite();

                    /** @var $product Mage_Catalog_Model_Product */
                    foreach ($collection as $product)
                    {
                        $product->setStoreId($storeId);

                        $default = isset($defaultData[$product->getId()]) ? (array) $defaultData[$product->getId()] : array();

                        $json = $this->product_helper->getObject($product, $default);

                        array_push($indexData, $json);

                        if (count($indexData) >= self::BATCH_SIZE)
                        {
                            $this->algolia_helper->addObjects($indexData, $index_name);
                            $indexData = array();
                        }
                    }

                    $collection->walk('clearInstance');
                    $collection->clear();
                    unset($collection);
                    $page++;
                }

                if (count($indexData) > 0)
                    $this->algolia_helper->addObjects($indexData, $index_name);

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

    public function stopEmulation($info)
    {
        Mage::app()->setCurrentStore($info->getInitialStoreId());
        Mage::app()->getStore($info->getEmulatedStoreId())->setConfig(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, $info->getUseProductFlat());
        Mage::app()->getStore($info->getEmulatedStoreId())->setConfig(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, $info->getUseCategoryFlat());
    }

    /***********/
    /* Proxies */
    /***********/

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
}
