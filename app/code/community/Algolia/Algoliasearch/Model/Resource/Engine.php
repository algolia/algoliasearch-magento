<?php

/**
 * Algolia search engine model.
 */
class Algolia_Algoliasearch_Model_Resource_Engine extends Mage_CatalogSearch_Model_Resource_Fulltext_Engine
{
    const ONE_TIME_AMOUNT = 100;

    /** @var Algolia_Algoliasearch_Helper_Logger */
    protected $logger;

    /** @var  Algolia_Algoliasearch_Model_Queue */
    protected $queue;

    /** @var Algolia_Algoliasearch_Helper_Config */
    protected $config;

    /** @var Algolia_Algoliasearch_Helper_Entity_Producthelper */
    protected $product_helper;

    /** @var Algolia_Algoliasearch_Helper_Entity_Categoryhelper */
    protected $category_helper;

    /** @var Algolia_Algoliasearch_Helper_Entity_Pagehelper */
    protected $page_helper;

    /** @var Algolia_Algoliasearch_Helper_Entity_Suggestionhelper */
    protected $suggestion_helper;

    public function _construct()
    {
        parent::_construct();

        $this->queue = Mage::getSingleton('algoliasearch/queue');
        $this->config = Mage::helper('algoliasearch/config');
        $this->logger = Mage::helper('algoliasearch/logger');
        $this->product_helper = Mage::helper('algoliasearch/entity_producthelper');
        $this->category_helper = Mage::helper('algoliasearch/entity_categoryhelper');
        $this->page_helper = Mage::helper('algoliasearch/entity_pagehelper');
        $this->suggestion_helper = Mage::helper('algoliasearch/entity_suggestionhelper');
    }

    public function addToQueue($observer, $method, $data, $data_size)
    {
        if ($this->config->isQueueActive()) {
            $this->queue->add($observer, $method, $data, $data_size);
        } else {
            Mage::getSingleton($observer)->$method(new Varien_Object($data));
        }
    }

    public function removeCategories($storeId = null, $category_ids = null)
    {
        $ids = Algolia_Algoliasearch_Helper_Entity_Helper::getStores($storeId);

        foreach ($ids as $id) {
            if (is_array($category_ids) == false) {
                $category_ids = array($category_ids);
            }

            $by_page = $this->config->getNumberOfElementByPage();

            if (is_array($category_ids) && count($category_ids) > $by_page) {
                foreach (array_chunk($category_ids, $by_page) as $chunk) {
                    $this->addToQueue('algoliasearch/observer', 'removeCategories',
                        array('store_id' => $id, 'category_ids' => $chunk), count($chunk));
                }
            } else {
                $this->addToQueue('algoliasearch/observer', 'removeCategories',
                    array('store_id' => $id, 'category_ids' => $category_ids), count($category_ids));
            }

            return $this;
        }

        return $this;
    }

    public function rebuildCategoryIndex($storeId = null, $categoryIds = null)
    {
        $storeIds = Algolia_Algoliasearch_Helper_Entity_Helper::getStores($storeId);

        foreach ($storeIds as $storeId) {
            $by_page = $this->config->getNumberOfElementByPage();

            if (is_array($categoryIds) && count($categoryIds) > $by_page) {
                foreach (array_chunk($categoryIds, $by_page) as $chunk) {
                    $this->_rebuildCategoryIndex($storeId, $chunk);
                }
            } else {
                $this->_rebuildCategoryIndex($storeId, $categoryIds);
            }
        }

        return $this;
    }

    public function rebuildPages($storeId = null, $pageIds = null)
    {
        $storeIds = Algolia_Algoliasearch_Helper_Entity_Helper::getStores($storeId);

        /** @var Mage_Core_Model_Store $store */
        foreach ($storeIds as $storeId) {
            if ($this->page_helper->shouldIndexPages($storeId) === true) {
                $this->addToQueue('algoliasearch/observer', 'rebuildPageIndex',
                    array('store_id' => $storeId, 'page_ids' => $pageIds), 1);
            }
        }
    }

    public function rebuildAdditionalSections()
    {
        /** @var Mage_Core_Model_Store $store */
        foreach (Mage::app()->getStores() as $store) {
            if ($this->config->isEnabledBackend($store->getId()) === false) {
                if (php_sapi_name() === 'cli') {
                    echo '[ALGOLIA] INDEXING IS DISABLED FOR '.$this->logger->getStoreName($store->getId())."\n";
                }

                /** @var Mage_Adminhtml_Model_Session $session */
                $session = Mage::getSingleton('adminhtml/session');
                $session->addWarning('[ALGOLIA] INDEXING IS DISABLED FOR '.$this->logger->getStoreName($store->getId()));

                $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($store->getId()));

                continue;
            }

            $this->addToQueue('algoliasearch/observer', 'rebuildAdditionalSectionsIndex',
                array('store_id' => $store->getId()), 1);
        }
    }

    public function rebuildSuggestions()
    {
        /** @var Mage_Core_Model_Store $store */
        foreach (Mage::app()->getStores() as $store) {
            if ($this->config->isEnabledBackend($store->getId()) === false) {
                if (php_sapi_name() === 'cli') {
                    echo '[ALGOLIA] INDEXING IS DISABLED FOR '.$this->logger->getStoreName($store->getId())."\n";
                }

                /** @var Mage_Adminhtml_Model_Session $session */
                $session = Mage::getSingleton('adminhtml/session');
                $session->addWarning('[ALGOLIA] INDEXING IS DISABLED FOR '.$this->logger->getStoreName($store->getId()));

                $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($store->getId()));

                continue;
            }

            $size = $this->suggestion_helper->getSuggestionCollectionQuery($store->getId())->getSize();
            $by_page = $this->config->getNumberOfElementByPage();
            $nb_page = ceil($size / $by_page);

            for ($i = 1; $i <= $nb_page; $i++) {
                $data = array('store_id' => $store->getId(), 'page_size' => $by_page, 'page' => $i);
                $this->addToQueue('algoliasearch/observer', 'rebuildSuggestionIndex', $data, 1);
            }

            if ($nb_page > 0) {
                $this->addToQueue('algoliasearch/observer', 'moveStoreSuggestionIndex',
                    array('store_id' => $store->getId()), 1);
            }
        }

        return $this;
    }

    public function rebuildProducts($reindexStoreId = null)
    {
        $this->saveSettings(true);

        /** @var Mage_Core_Model_Store $store */
        foreach (Mage::app()->getStores() as $store) {
            $storeId = $store->getId();

            if ($reindexStoreId !== null && $storeId != $reindexStoreId) {
                continue;
            }

            if ($this->config->isEnabledBackend($storeId) === false) {
                if (php_sapi_name() === 'cli') {
                    echo '[ALGOLIA] INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId)."\n";
                }

                /** @var Mage_Adminhtml_Model_Session $session */
                $session = Mage::getSingleton('adminhtml/session');
                $session->addWarning('[ALGOLIA] INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

                $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

                continue;
            }

            if ($store->getIsActive()) {
                $useTmpIndex = $this->config->isQueueActive($storeId);
                $this->_rebuildProductIndex($storeId, array(), $useTmpIndex);

                if ($this->config->isQueueActive($storeId)) {
                    $this->addToQueue('algoliasearch/observer', 'moveProductsTmpIndex', array('store_id' => $storeId), 1);
                }
            } else {
                $this->addToQueue('algoliasearch/observer', 'deleteProductsStoreIndices',
                    array('store_id' => $storeId), 1);
            }
        }
    }

    public function rebuildCategories()
    {
        /** @var Mage_Core_Model_Store $store */
        foreach (Mage::app()->getStores() as $store) {
            if ($this->config->isEnabledBackend($store->getId()) === false) {
                if (php_sapi_name() === 'cli') {
                    echo '[ALGOLIA] INDEXING IS DISABLED FOR '.$this->logger->getStoreName($store->getId())."\n";
                }

                /** @var Mage_Adminhtml_Model_Session $session */
                $session = Mage::getSingleton('adminhtml/session');
                $session->addWarning('[ALGOLIA] INDEXING IS DISABLED FOR '.$this->logger->getStoreName($store->getId()));

                $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($store->getId()));

                continue;
            }

            if ($store->getIsActive()) {
                $this->addToQueue('algoliasearch/observer', 'rebuildCategoryIndex',
                    array('store_id' => $store->getId(), 'category_ids' => array()), 1);
            } else {
                $this->addToQueue('algoliasearch/observer', 'deleteCategoriesStoreIndices',
                    array('store_id' => $store->getId()), 1);
            }
        }
    }

    public function rebuildProductIndex($storeId = null, $productIds = null)
    {
        $storeIds = Algolia_Algoliasearch_Helper_Entity_Helper::getStores($storeId);
        $by_page = $this->config->getNumberOfElementByPage();

        $productIds = array_values(array_unique($productIds));

        foreach ($storeIds as $storeId) {
            if (is_array($productIds) && count($productIds) > $by_page) {
                foreach (array_chunk($productIds, $by_page) as $chunk) {
                    $this->_rebuildProductIndex($storeId, $chunk);
                }
            } else {
                $this->_rebuildProductIndex($storeId, $productIds);
            }
        }

        return $this;
    }

    protected function _rebuildCategoryIndex($storeId, $categoryIds = null)
    {
        if ($categoryIds == null || count($categoryIds) == 0) {
            $size = $this->category_helper->getCategoryCollectionQuery($storeId, $categoryIds)->getSize();
            $by_page = $this->config->getNumberOfElementByPage();
            $nb_page = ceil($size / $by_page);

            for ($i = 1; $i <= $nb_page; $i++) {
                $data = array(
                    'store_id'     => $storeId,
                    'category_ids' => $categoryIds,
                    'page_size'    => $by_page,
                    'page'         => $i,
                );

                $this->addToQueue('algoliasearch/observer', 'rebuildCategoryIndex', $data, $by_page);
            }
        } else {
            $this->addToQueue('algoliasearch/observer', 'rebuildCategoryIndex',
                array('store_id' => $storeId, 'category_ids' => $categoryIds), count($categoryIds));
        }

        return $this;
    }

    protected function _rebuildProductIndex($storeId, $productIds = null, $useTmpIndex = false)
    {
        if ($productIds == null || count($productIds) == 0) {
            $collection = $this->product_helper->getProductCollectionQuery($storeId, $productIds, $useTmpIndex);
            $size = $collection->getSize();

            if (!empty($productIds)) {
                $size = max(count($productIds), $size);
            }

            $by_page = $this->config->getNumberOfElementByPage();
            $nb_page = ceil($size / $by_page);

            for ($i = 1; $i <= $nb_page; $i++) {
                $data = array(
                    'store_id'      => $storeId,
                    'product_ids'   => $productIds,
                    'page_size'     => $by_page,
                    'page'          => $i,
                    'use_tmp_index' => $useTmpIndex,
                );

                $this->addToQueue('algoliasearch/observer', 'rebuildProductIndex', $data, $by_page);
            }
        } else {
            $this->addToQueue('algoliasearch/observer', 'rebuildProductIndex',
                array('store_id' => $storeId, 'product_ids' => $productIds), count($productIds));
        }

        return $this;
    }

    public function prepareEntityIndex($index, $separator = ' ')
    {
        if ($this->config->isEnabledBackend(Mage::app()->getStore()->getId()) === false) {
            return parent::prepareEntityIndex($index, $separator);
        }

        foreach ($index as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $index[$key] = implode($separator, array_unique(array_filter($value)));
            } else {
                if (empty($index[$key])) {
                    unset($index[$key]);
                }
            }
        }

        return $index;
    }

    public function saveSettings($isFullProductReindex = false)
    {
        $this->addToQueue('algoliasearch/observer', 'saveSettings', array('isFullProductReindex' => $isFullProductReindex), 1);
    }
}
