<?php

class Algolia_Algoliasearch_Helper_Personalization extends Mage_Core_Helper_Abstract
{
    /** @var Algolia_Algoliasearch_Helper_Config */
    private $config;

    /** @var Algolia_Algoliasearch_Helper_Algoliahelper */
    private $algoliaHelper;

    /** @var Algolia_Algoliasearch_Helper_Entity_Categoryhelper  */
    private $categoriesHelper;

    /** @var Algolia_Algoliasearch_Helper_Entity_Producthelper  */
    private $productsHelper;

    /** @var Algolia_Algoliasearch_Helper_Logger */
    private $logger;

    private $readDb;

    private $tableNamePrefix;

    private $products = array();

    public function __construct()
    {
        $this->config = Mage::helper('algoliasearch/config');
        $this->algoliaHelper = Mage::helper('algoliasearch/algoliahelper');
        $this->categoriesHelper = Mage::helper('algoliasearch/entity_categoryhelper');
        $this->productsHelper = Mage::helper('algoliasearch/entity_producthelper');
        $this->logger = Mage::helper('algoliasearch/logger');

        /** @var Mage_Core_Model_Resource $coreResource */
        $coreResource = Mage::getSingleton('core/resource');
        $this->readDb = $coreResource->getConnection('core_read');

        $this->tableNamePrefix = Mage::getConfig()->getTablePrefix();
    }

    public function reindex()
    {
        /** @var Mage_Core_Model_Store $store */
        foreach (Mage::app()->getStores() as $store) {
            $storeId = $store->getId();

            if ($this->config->isEnabledBackend($storeId) === false || $this->config->isPersonalizationEnabled($storeId) === false || $store->getIsActive() === false) {
                if (php_sapi_name() === 'cli') {
                    echo '[ALGOLIA] INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId)."\n";
                }

                /** @var Mage_Adminhtml_Model_Session $session */
                $session = Mage::getSingleton('adminhtml/session');
                $session->addWarning('[ALGOLIA] INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

                $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

                continue;
            }

            $this->handleOrderedProducts($storeId);
            $this->handleCategories($storeId);

            $this->pushToAlgolia($storeId);
        }
    }

    private function handleOrderedProducts($storeId)
    {
        $query = 'SELECT sfoi.product_id, sfo.customer_id 
            FROM '.$this->tableNamePrefix.'sales_flat_order_item sfoi 
            JOIN '.$this->tableNamePrefix.'sales_flat_order sfo ON sfoi.order_id = sfo.entity_id 
            WHERE sfo.state = "complete" AND sfo.store_id = '.((int) $storeId).' AND sfo.customer_id IS NOT NULL
            ORDER BY sfo.created_at DESC
            LIMIT 8000';
        $results = $this->readDb->query($query);

        foreach ($results as $result) {
            $this->products[$result['product_id']][$result['customer_id']] = true;
        }
    }

    private function handleCategories($storeId)
    {
        $storeRootCategoryPath = sprintf('%d/%d', $this->categoriesHelper->getRootCategoryId(), Mage::app()->getStore($storeId)->getRootCategoryId());

        /* @var Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection $categories */
        $categories = Mage::getResourceModel('catalog/category_collection');

        $categories
            ->addPathFilter($storeRootCategoryPath)
            ->addIsActiveFilter()
            ->setStoreId($storeId)
            ->addFieldToFilter('level', array('gt' => 1));

        $usersWithCategories = array();

        /** @var Mage_Catalog_Model_Category $category */
        foreach ($categories as $category) {
            $query = 'SELECT sfo.customer_id, COUNT(sfoi.product_id) as items_purchased FROM 
                '.$this->tableNamePrefix.'catalog_category_product ccp 
                JOIN '.$this->tableNamePrefix.'sales_flat_order_item sfoi ON ccp.product_id = sfoi.product_id
                JOIN '.$this->tableNamePrefix.'sales_flat_order sfo ON sfoi.order_id = sfo.entity_id
                WHERE ccp.category_id = '.((int) $category->getId()).' AND sfo.state = "complete" AND sfo.customer_id IS NOT NULL AND sfo.store_id = '.((int) $storeId).'
                GROUP BY sfo.customer_id
                HAVING items_purchased >= '.((int) $this->config->getMinCategoryPurchasesForBoost($storeId));

            $results = $this->readDb->query($query);

            foreach ($results as $result) {
                $usersWithCategories[$result['customer_id']][] = array(
                    'categoryId' => (int) $category->getId(),
                    'itemsPurchased' => (int) $result['items_purchased'],
                );
            }
        }

        $dontBoostMoreCategoriesThan = $this->config->dontBoostMoreCategoriesThan($storeId);

        $categoriesWithUsers = array();
        foreach ($usersWithCategories as $userId => &$userCategories) {
            if (count($userCategories) > $dontBoostMoreCategoriesThan) {
                usort($userCategories, function ($a, $b) {
                    return $b['itemsPurchased'] - $a['itemsPurchased'];
                });

                $userCategories = array_slice($userCategories, 0, $dontBoostMoreCategoriesThan);
            }

            foreach ($userCategories as $userCategory) {
                $categoriesWithUsers[$userCategory['categoryId']][$userId] = true;
            }
        }

        foreach ($categoriesWithUsers as $categoryId => $usersIds) {
            $categoryProductIds = $this->getCategoryProductIds($categoryId);

            foreach ($categoryProductIds as $productId) {
                if (isset($this->products[$productId])) {
                    $this->products[$productId] += $usersIds;
                    continue;
                }

                $this->products[$productId] = $usersIds;
            }
        }
    }

    private function getCategoryProductIds($categoryId)
    {
        $query = 'SELECT product_id FROM '.$this->tableNamePrefix.'catalog_category_product WHERE category_id = '.((int) $categoryId);
        $ids = $this->readDb->query($query)->fetchAll(7, 0); // Directly fetch values of the first column (product_id)

        return $ids;
    }

    private function pushToAlgolia($storeId)
    {
        $indexName = $this->productsHelper->getIndexName($storeId);

        $this->resetPersonalizationAttribute($indexName);

        $counter = 0;
        $requests = array();
        foreach ($this->products as $productId => $userIds) {
            $counter++;

            $userIds = array_keys($userIds);

            $requests[] = array(
                'action'   => 'partialUpdateObjectNoCreate',
                'objectID' => $productId,
                'body'     => array('personalization_user_id' => $userIds),
            );

            if ($counter%100 === 0) {
                $this->algoliaHelper->batch($indexName, array(
                    'requests' => $requests,
                ));

                $requests = array();
            }
        }

        if (empty($requests) === false) {
            $this->algoliaHelper->batch($indexName, array(
                'requests' => $requests,
            ));
        }
    }

    private function resetPersonalizationAttribute($indexName)
    {
        $index = $this->algoliaHelper->getIndex($indexName);

        $counter = 0;
        $requests = array();
        foreach ($index->browse('', array('attributesToRetrieve' => array('objectID'))) as $record) {
            $counter++;

            $requests[] = array(
                'action'   => 'partialUpdateObjectNoCreate',
                'objectID' => $record['objectID'],
                'body'     => array('personalization_user_id' => array()),
            );

            if ($counter%100 === 0) {
                $this->algoliaHelper->batch($indexName, array(
                    'requests' => $requests,
                ));

                $requests = array();
            }
        }

        if (empty($requests) === false) {
            $this->algoliaHelper->batch($indexName, array(
                'requests' => $requests,
            ));
        }
    }
}
