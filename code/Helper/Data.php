<?php

if (class_exists('AlgoliaSearch\Client', false) == false)
{
    require_once 'AlgoliaSearch/Version.php';
    require_once 'AlgoliaSearch/AlgoliaException.php';
    require_once 'AlgoliaSearch/ClientContext.php';
    require_once 'AlgoliaSearch/Client.php';
    require_once 'AlgoliaSearch/Index.php';
}

class Algolia_Algoliasearch_Helper_Data extends Mage_Core_Helper_Abstract
{
    const COLLECTION_PAGE_SIZE = 100;

    private $algolia_helper;

    private $page_helper;
    private $category_helper;
    private $product_helper;

    private $config;

    public function __construct()
    {
        \AlgoliaSearch\Version::$custom_value = " Magento (1.2.0)";

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

    public function removeProducts($ids, $store_id = null)
    {
        $store_ids = Algolia_Algoliasearch_Helper_Entity_Helper::getStores($store_id);

        foreach ($store_ids as $store_id)
        {
            $index_name = $this->product_helper->getIndexName($store_id);

            $this->algolia_helper->deleteObjects($ids, $index_name);
        }
    }

    public function removeCategories($ids, $store_id = null)
    {
        $store_ids = Algolia_Algoliasearch_Helper_Entity_Helper::getStores($store_id);

        foreach ($store_ids as $store_id)
        {
            $index_name = $this->category_helper->getIndexName($store_id);

            $this->algolia_helper->deleteObjects($ids, $index_name);
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
            $collection = $this->category_helper->getCategoryCollectionQuery($storeId, $categoryIds);

            $size = $collection->getSize();

            if ($size > 0)
            {
                $pages = ceil($size / $this->config->getNumberOfElementByPage());
                $collection->clear();
                $page = 1;

                while ($page <= $pages)
                {
                    $this->rebuildStoreCategoryIndexPage($storeId, $collection, $page, $this->config->getNumberOfElementByPage());

                    $page++;
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

    public function rebuildStoreProductIndex($storeId, $productIds)
    {
        $emulationInfo = $this->startEmulation($storeId);

        try
        {
            $collection = $this->product_helper->getProductCollectionQuery($storeId, $productIds);

            $size = $collection->getSize();

            if ($size > 0)
            {
                $pages = ceil($size / $this->config->getNumberOfElementByPage());
                $collection->clear();
                $page = 1;

                while ($page <= $pages)
                {
                    $this->rebuildStoreProductIndexPage($storeId, $collection, $page, $this->config->getNumberOfElementByPage());

                    $page++;
                }
            }
        }
        catch (Exception $e)
        {
            $this->stopEmulation($emulationInfo);
            throw $e;
        }

        $this->stopEmulation($emulationInfo);
    }


    public function rebuildStoreCategoryIndexPage($storeId, $collectionDefault, $page, $pageSize)
    {
        $collection = clone $collectionDefault;
        $collection->setCurPage($page)->setPageSize($pageSize);
        $collection->load();

        $index_name = $this->category_helper->getIndexName($storeId);

        $indexData = array();

        /** @var $category Mage_Catalog_Model_Category */
        foreach ($collection as $category)
        {
            if ( ! $this->category_helper->isCategoryActive($category->getId(), $storeId))
                continue;

            $category->setStoreId($storeId);

            $category_obj = $this->category_helper->getObject($category);

            if ($category_obj['product_count'] > 0)
                array_push($indexData, $category_obj);
        }

        if (count($indexData) > 0)
            $this->algolia_helper->addObjects($indexData, $index_name);

        unset($indexData);

        $collection->walk('clearInstance');
        $collection->clear();

        unset($collection);
    }

    public function rebuildStoreProductIndexPage($storeId, $collectionDefault, $page, $pageSize)
    {
        set_time_limit(0);

        $collection = clone $collectionDefault;
        $collection->setCurPage($page)->setPageSize($pageSize);
        $collection->load();
        $collection->addCategoryIds();
        $collection->addUrlRewrite();

        $index_name = $this->product_helper->getIndexName($storeId);

        $indexData = array();

        /** @var $product Mage_Catalog_Model_Product */
        foreach ($collection as $product)
        {
            $product->setStoreId($storeId);

            $json = $this->product_helper->getObject($product);

            array_push($indexData, $json);
        }

        if (count($indexData) > 0)
            $this->algolia_helper->addObjects($indexData, $index_name);

        unset($indexData);

        $collection->walk('clearInstance');
        $collection->clear();

        unset($collection);
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
}
