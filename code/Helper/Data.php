<?php

if (class_exists('AlgoliaSearch\Client', false) == false)
{
    require_once Mage::getBaseDir('lib').'/AlgoliaSearch/Version.php';
    require_once Mage::getBaseDir('lib').'/AlgoliaSearch/AlgoliaException.php';
    require_once Mage::getBaseDir('lib').'/AlgoliaSearch/ClientContext.php';
    require_once Mage::getBaseDir('lib').'/AlgoliaSearch/Client.php';
    require_once Mage::getBaseDir('lib').'/AlgoliaSearch/Index.php';
    require_once Mage::getBaseDir('lib').'/AlgoliaSearch/PlacesIndex.php';
    require_once Mage::getBaseDir('lib').'/AlgoliaSearch/IndexBrowser.php';
}

class Algolia_Algoliasearch_Helper_Data extends Mage_Core_Helper_Abstract
{
    const COLLECTION_PAGE_SIZE = 100;

    protected $algolia_helper;

    protected $page_helper;
    protected $category_helper;
    protected $product_helper;

    /** @var Algolia_Algoliasearch_Helper_Logger */
    protected $logger;
    protected $config;

    public function __construct()
    {
        \AlgoliaSearch\Version::$custom_value = " Magento (1.5.5)";

        $this->algolia_helper               = Mage::helper('algoliasearch/algoliahelper');

        $this->page_helper                  = Mage::helper('algoliasearch/entity_pagehelper');
        $this->category_helper              = Mage::helper('algoliasearch/entity_categoryhelper');
        $this->product_helper               = Mage::helper('algoliasearch/entity_producthelper');
        $this->suggestion_helper            = Mage::helper('algoliasearch/entity_suggestionhelper');
        $this->additionalsections_helper    = Mage::helper('algoliasearch/entity_additionalsectionshelper');

        $this->config                       = Mage::helper('algoliasearch/config');

        $this->logger                       = Mage::helper('algoliasearch/logger');
    }

    public function deleteProductsStoreIndices($storeId = null)
    {
        if ($storeId !== null)
        {
            if ($this->config->isEnabledBackEnd($storeId) === false)
            {
                $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($storeId));
                return;
            }
        }

        $this->algolia_helper->deleteIndex($this->product_helper->getIndexName($storeId));
    }

    public function deleteCategoriesStoreIndices($storeId = null)
    {
        if ($storeId !== null)
        {
            if ($this->config->isEnabledBackEnd($storeId) === false)
            {
                $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($storeId));
                return;
            }
        }

        $this->algolia_helper->deleteIndex($this->category_helper->getIndexName($storeId));
    }

    public function saveConfigurationToAlgolia($storeId)
    {
        $this->algolia_helper->resetCredentialsFromConfig();

        if (! ($this->config->getApplicationID() && $this->config->getAPIKey()))
            return;

        if ($this->config->isEnabledBackEnd($storeId) === false)
        {
            $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($storeId));
            return;
        }

        $this->algolia_helper->setSettings($this->category_helper->getIndexName($storeId), $this->category_helper->getIndexSettings($storeId));
        $this->algolia_helper->setSettings($this->page_helper->getIndexName($storeId), $this->page_helper->getIndexSettings($storeId));
        $this->algolia_helper->setSettings($this->suggestion_helper->getIndexName($storeId), $this->suggestion_helper->getIndexSettings($storeId));

        foreach ($this->config->getAutocompleteSections() as $section)
        {
            if ($section['name'] === 'products' || $section['name'] === 'categories' || $section['name'] === 'pages' || $section['name'] === 'suggestions')
                continue;

            $this->algolia_helper->setSettings($this->additionalsections_helper->getIndexName($storeId).'_'.$section['name'], $this->additionalsections_helper->getIndexSettings($storeId));
        }

        $this->product_helper->setSettings($storeId);
    }

    public function getSearchResult($query, $storeId)
    {
        $resultsLimit = $this->config->getResultsLimit($storeId);

        $index_name = $this->product_helper->getIndexName($storeId);

        $number_of_results = 1000;

        if ($this->config->isInstantEnabled())
            $number_of_results = min($this->config->getNumberOfProductResults($storeId), 1000);

        $answer = $this->algolia_helper->query($index_name, $query, array(
            'hitsPerPage' => $number_of_results, // retrieve all the hits (hard limit is 1000)
            'attributesToRetrieve' => 'objectID',
            'attributesToHighlight' => '',
            'attributesToSnippet' => '',
            'numericFilters' => 'visibility_search=1',
            'removeWordsIfNoResults'=> $this->config->getRemoveWordsIfNoResult($storeId),
            'analyticsTags' => 'backend-search'
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
            if ($this->config->isEnabledBackEnd($store_id) === false)
            {
                $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($store_id));
                continue;
            }

            $index_name = $this->product_helper->getIndexName($store_id);

            $this->algolia_helper->deleteObjects($ids, $index_name);
        }
    }

    public function removeCategories($ids, $store_id = null)
    {
        $store_ids = Algolia_Algoliasearch_Helper_Entity_Helper::getStores($store_id);

        foreach ($store_ids as $store_id)
        {
            if ($this->config->isEnabledBackEnd($store_id) === false)
            {
                $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($store_id));
                continue;
            }

            $index_name = $this->category_helper->getIndexName($store_id);

            $this->algolia_helper->deleteObjects($ids, $index_name);
        }
    }

    public function rebuildStoreAdditionalSectionsIndex($storeId)
    {
        if ($this->config->isEnabledBackEnd($storeId) === false)
        {
            $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($storeId));
            return;
        }

        $additionnal_sections = $this->config->getAutocompleteSections();

        foreach ($additionnal_sections as $section)
        {
            if ($section['name'] === 'products' || $section['name'] === 'categories' || $section['name'] === 'pages' || $section['name'] === 'suggestions')
                continue;

            $index_name = $this->additionalsections_helper->getIndexName($storeId).'_'.$section['name'];

            $attribute_values = $this->additionalsections_helper->getAttributeValues($storeId, $section);

            foreach (array_chunk($attribute_values, 100) as $chunk)
                $this->algolia_helper->addObjects($chunk, $index_name.'_tmp');

            $this->algolia_helper->moveIndex($index_name.'_tmp', $index_name);

            $this->algolia_helper->setSettings($index_name, $this->additionalsections_helper->getIndexSettings($storeId));
        }
    }

    public function rebuildStorePageIndex($storeId)
    {
        if ($this->config->isEnabledBackEnd($storeId) === false)
        {
            $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($storeId));
            return;
        }

        $emulationInfo = $this->startEmulation($storeId);

        $index_name = $this->page_helper->getIndexName($storeId);

        $pages = $this->page_helper->getPages($storeId);

        foreach (array_chunk($pages, 100) as $chunk)
            $this->algolia_helper->addObjects($chunk, $index_name.'_tmp');

        $this->algolia_helper->moveIndex($index_name.'_tmp', $index_name);

        $this->algolia_helper->setSettings($index_name, $this->page_helper->getIndexSettings($storeId));

        $this->stopEmulation($emulationInfo);
    }

    public function rebuildStoreCategoryIndex($storeId, $categoryIds = null)
    {
        if ($this->config->isEnabledBackEnd($storeId) === false)
        {
            $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($storeId));
            return;
        }

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
                    $this->rebuildStoreCategoryIndexPage($storeId, $collection, $page, $this->config->getNumberOfElementByPage(), $emulationInfo);

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

    public function rebuildStoreSuggestionIndex($storeId)
    {
        if ($this->config->isEnabledBackEnd($storeId) === false)
        {
            $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($storeId));
            return;
        }

        $collection = $this->suggestion_helper->getSuggestionCollectionQuery($storeId);

        $size = $collection->getSize();

        if ($size > 0)
        {
            $pages = ceil($size / $this->config->getNumberOfElementByPage());
            $collection->clear();
            $page = 1;

            while ($page <= $pages)
            {
                $this->rebuildStoreSuggestionIndexPage($storeId, $collection, $page, $this->config->getNumberOfElementByPage());

                $page++;
            }

            unset($indexData);
        }
    }

    public function moveStoreSuggestionIndex($storeId)
    {
        if ($this->config->isEnabledBackEnd($storeId) === false)
        {
            $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($storeId));
            return;
        }

        $this->algolia_helper->moveIndex($this->suggestion_helper->getIndexName($storeId) . '_tmp', $this->suggestion_helper->getIndexName($storeId));
    }

    public function rebuildStoreProductIndex($storeId, $productIds)
    {
        if ($this->config->isEnabledBackEnd($storeId) === false)
        {
            $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($storeId));
            return;
        }

        $emulationInfo = $this->startEmulation($storeId);

        try
        {
            $collection = $this->product_helper->getProductCollectionQuery($storeId, $productIds);

            $size = $collection->getSize();

            $this->logger->log('Store '.$this->logger->getStoreName($storeId). ' collection size : '.$size);

            if ($size > 0)
            {
                $pages = ceil($size / $this->config->getNumberOfElementByPage());
                $collection->clear();
                $page = 1;

                while ($page <= $pages)
                {
                    $this->rebuildStoreProductIndexPage($storeId, $collection, $page, $this->config->getNumberOfElementByPage(), $emulationInfo);

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

    public function rebuildStoreSuggestionIndexPage($storeId, $collectionDefault, $page, $pageSize)
    {
        if ($this->config->isEnabledBackEnd($storeId) === false)
        {
            $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($storeId));
            return;
        }

        $collection = clone $collectionDefault;
        $collection->setCurPage($page)->setPageSize($pageSize);
        $collection->load();

        $index_name = $this->suggestion_helper->getIndexName($storeId) . '_tmp';

        $indexData = array();

        /** @var $suggestion Mage_Catalog_Model_Category */
        foreach ($collection as $suggestion)
        {
            $suggestion->setStoreId($storeId);

            $suggestion_obj = $this->suggestion_helper->getObject($suggestion);

            if (strlen($suggestion_obj['query']) >= 3)
                array_push($indexData, $suggestion_obj);
        }

        if (count($indexData) > 0)
            $this->algolia_helper->addObjects($indexData, $index_name);

        unset($indexData);

        $collection->walk('clearInstance');
        $collection->clear();

        unset($collection);
    }

    public function rebuildStoreCategoryIndexPage($storeId, $collectionDefault, $page, $pageSize, $emulationInfo = null)
    {
        if ($this->config->isEnabledBackEnd($storeId) === false)
        {
            $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($storeId));
            return;
        }

        $emulationInfoPage = null;

        if ($emulationInfo === null)
            $emulationInfoPage = $this->startEmulation($storeId);

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

        if ($emulationInfo === null)
            $this->stopEmulation($emulationInfoPage);
    }

    protected function getProductsRecords($storeId, $collection)
    {
        $indexData = array();

        $this->logger->start('CREATE RECORDS '.$this->logger->getStoreName($storeId));
        $this->logger->log(count($collection). ' product records to create');
        /** @var $product Mage_Catalog_Model_Product */
        foreach ($collection as $product)
        {
            $product->setStoreId($storeId);

            $json = $this->product_helper->getObject($product);

            array_push($indexData, $json);
        }
        $this->logger->stop('CREATE RECORDS '.$this->logger->getStoreName($storeId));

        return $indexData;
    }

    public function rebuildStoreProductIndexPage($storeId, $collectionDefault, $page, $pageSize, $emulationInfo = null)
    {
        if ($this->config->isEnabledBackEnd($storeId) === false)
        {
            $this->logger->log('INDEXING IS DISABLED FOR '. $this->logger->getStoreName($storeId));
            return;
        }

        $this->logger->start('rebuildStoreProductIndexPage '.$this->logger->getStoreName($storeId).' page ' . $page . ' pageSize '. $pageSize);
        $emulationInfoPage = null;

        if ($emulationInfo === null)
            $emulationInfoPage = $this->startEmulation($storeId);

        $index_prefix = Mage::getConfig()->getTablePrefix();

        $additionalAttributes = $this->config->getProductAdditionalAttributes($storeId);

        $collection = clone $collectionDefault;
        $collection->setCurPage($page)->setPageSize($pageSize);
        $collection->addCategoryIds();
        $collection->addUrlRewrite();

        if ($this->product_helper->isAttributeEnabled($additionalAttributes, 'stock_qty'))
            $collection->joinField('stock_qty', $index_prefix.'cataloginventory_stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');

        if ($this->product_helper->isAttributeEnabled($additionalAttributes, 'ordered_qty'))
            $collection->getSelect()->columns('(SELECT SUM(qty_ordered) FROM '.$index_prefix.'sales_flat_order_item WHERE '.$index_prefix.'sales_flat_order_item.product_id = e.entity_id) as ordered_qty');

        if ($this->product_helper->isAttributeEnabled($additionalAttributes, 'total_ordered'))
            $collection->getSelect()->columns('(SELECT SUM(row_total) FROM '.$index_prefix.'sales_flat_order_item WHERE '.$index_prefix.'sales_flat_order_item.product_id = e.entity_id) as total_ordered');

        if ($this->product_helper->isAttributeEnabled($additionalAttributes, 'rating_summary'))
            $collection->joinField('rating_summary', $index_prefix.'review_entity_summary', 'rating_summary', 'entity_pk_value=entity_id', '{{table}}.store_id='.$storeId, 'left');

        $this->logger->start('LOADING '.$this->logger->getStoreName($storeId). ' collection page '. $page . ', pageSize '.$pageSize);

        $collection->load();

        $this->logger->log('Loaded '.count($collection).' products');
        $this->logger->stop('LOADING '.$this->logger->getStoreName($storeId). ' collection page '. $page . ', pageSize '.$pageSize);

        $index_name = $this->product_helper->getIndexName($storeId);

        $indexData = $this->getProductsRecords($storeId, $collection);

        $this->logger->start('SEND TO ALGOLIA');
        if (count($indexData) > 0)
            $this->algolia_helper->addObjects($indexData, $index_name);
        $this->logger->stop('SEND TO ALGOLIA');

        unset($indexData);

        $collection->walk('clearInstance');
        $collection->clear();

        unset($collection);

        if ($emulationInfo === null)
            $this->stopEmulation($emulationInfoPage);

        $this->logger->stop('rebuildStoreProductIndexPage '.$this->logger->getStoreName($storeId).' page ' . $page . ' pageSize '. $pageSize);
    }

    public function startEmulation($storeId)
    {
        $this->logger->start('START EMULATION');
        $appEmulation = Mage::getSingleton('core/app_emulation');

        $info = $appEmulation->startEnvironmentEmulation($storeId);

        $info->setInitialStoreId(Mage::app()->getStore()->getId());
        $info->setEmulatedStoreId($storeId);
        $info->setUseProductFlat(Mage::getStoreConfigFlag(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, $storeId));
        $info->setUseCategoryFlat(Mage::getStoreConfigFlag(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, $storeId));
        Mage::app()->setCurrentStore($storeId);
        Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, FALSE);
        Mage::app()->getStore($storeId)->setConfig(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, FALSE);

        $this->logger->stop('START EMULATION');
        return $info;
    }

    public function stopEmulation($info)
    {
        $this->logger->start('STOP EMULATION');
        $appEmulation = Mage::getSingleton('core/app_emulation');

        Mage::app()->setCurrentStore($info->getInitialStoreId());
        Mage::app()->getStore($info->getEmulatedStoreId())->setConfig(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, $info->getUseProductFlat());
        Mage::app()->getStore($info->getEmulatedStoreId())->setConfig(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, $info->getUseCategoryFlat());

        $appEmulation->stopEnvironmentEmulation($info);
        $this->logger->stop('STOP EMULATION');
    }
}
