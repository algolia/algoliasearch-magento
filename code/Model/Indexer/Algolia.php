<?php

class Algolia_Algoliasearch_Model_Indexer_Algolia extends Mage_Index_Model_Indexer_Abstract
{
    const EVENT_MATCH_RESULT_KEY = 'algoliasearch_match_result';

    /** @var Algolia_Algoliasearch_Model_Resource_Engine */
    protected $engine;

    /** @var Algolia_Algoliasearch_Helper_Config */
    protected $config;

    /** @var Algolia_Algoliasearch_Helper_Logger */
    protected $logger;

    public static $product_categories = [];
    protected static $credential_error = false;

    public function __construct()
    {
        parent::__construct();

        $this->engine = new Algolia_Algoliasearch_Model_Resource_Engine();
        $this->config = Mage::helper('algoliasearch/config');
        $this->logger = Mage::helper('algoliasearch/logger');
    }

    protected $_matchedEntities = [
        Mage_Catalog_Model_Product::ENTITY => [
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION,
            Mage_Index_Model_Event::TYPE_DELETE,
        ],
        Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY => [
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
        ],
        Mage_Core_Model_Store::ENTITY => [
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
        ],
        Mage_Core_Model_Store_Group::ENTITY => [
            Mage_Index_Model_Event::TYPE_SAVE,
        ],
        Mage_Core_Model_Config_Data::ENTITY => [
            Mage_Index_Model_Event::TYPE_SAVE,
        ],
        Mage_Catalog_Model_Convert_Adapter_Product::ENTITY => [
            Mage_Index_Model_Event::TYPE_SAVE,
        ],
        Mage_Catalog_Model_Category::ENTITY => [
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
        ],
    ];

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
        return Mage::helper('algoliasearch')->__('Rebuild product index.
        Please enable the queueing system to do it asynchronously (CRON) if you have a lot of products in System > Configuration > Algolia Search > Queue configuration');
    }

    public function matchEvent(Mage_Index_Model_Event $event)
    {
        /** @var Mage_Index_Model_Indexer $indexer */
        $indexer = Mage::getModel('index/indexer');
        $process = $indexer->getProcessByCode('algolia_search_indexer');

        $result = $process->getMode() !== Mage_Index_Model_Process::MODE_MANUAL;

        $result = $result && $event->getEntity() !== 'core_config_data';

        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, $result);

        return $result;
    }

    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, true);
        switch ($event->getEntity()) {
            case Mage_Catalog_Model_Product::ENTITY:
                $this->_registerCatalogProductEvent($event);
                break;
            case Mage_Catalog_Model_Convert_Adapter_Product::ENTITY:
                $event->addNewData('algoliasearch_reindex_all', true);
                break;
            case Mage_Core_Model_Store_Group::ENTITY:
                $event->addNewData('algoliasearch_reindex_all', true);
                break;
            case Mage_CatalogInventory_Model_Stock_Item::ENTITY:
                if (false == $this->config->getShowOutOfStock()) {
                    $this->_registerCatalogInventoryStockItemEvent($event);
                }
                break;
        }
    }

    protected function _registerCatalogInventoryStockItemEvent(Mage_Index_Model_Event $event)
    {
        if ($event->getType() == Mage_Index_Model_Event::TYPE_SAVE) {
            $object = $event->getDataObject();

            /** @var Mage_Catalog_Model_Abstract $modelProduct */
            $modelProduct = Mage::getModel('catalog/product');

            /** @var Mage_Catalog_Model_Product $product */
            $product = $modelProduct->load($object->getProductId());

            try {
                // In case of wrong credentials or overquota or block account. To avoid checkout process to fail

                $event->addNewData('catalogsearch_delete_product_id', $product->getId());
                $event->addNewData('catalogsearch_update_category_id', $product->getCategoryIds());
            } catch (\Exception $e) {
                $this->logger->log('Error while trying to update stock');
                $this->logger->log($e->getMessage());
                $this->logger->log($e->getTraceAsString());
            }
        }
    }

    protected function _registerCatalogProductEvent(Mage_Index_Model_Event $event)
    {
        switch ($event->getType()) {
            case Mage_Index_Model_Event::TYPE_SAVE:
                /** @var $product Mage_Catalog_Model_Product */
                $product = $event->getDataObject();
                $event->addNewData('catalogsearch_update_product_id', $product->getId());
                $event->addNewData('catalogsearch_update_category_id', $product->getCategoryIds());

                /* product_categories is filled in Observer::saveProduct */
                if (isset(static::$product_categories[$product->getId()])) {
                    $oldCategories = static::$product_categories[$product->getId()];
                    $newCategories = $product->getCategoryIds();

                    $diffCategories = array_merge(array_diff($oldCategories, $newCategories),
                        array_diff($newCategories, $oldCategories));

                    $event->addNewData('catalogsearch_update_category_id', $diffCategories);
                }
                break;

            case Mage_Index_Model_Event::TYPE_DELETE:

                /** @var $product Mage_Catalog_Model_Product */
                $product = $event->getDataObject();
                $event->addNewData('catalogsearch_update_product_id', $product->getId());
                $event->addNewData('catalogsearch_update_category_id', $product->getCategoryIds());
                break;

            case Mage_Index_Model_Event::TYPE_MASS_ACTION:
                /** @var Varien_Object $actionObject */
                $actionObject = $event->getDataObject();

                $event->addNewData('catalogsearch_update_product_id', $actionObject->getProductIds());

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
        if (!$this->config->getApplicationID() || !$this->config->getAPIKey() || !$this->config->getSearchOnlyAPIKey()) {
            if (self::$credential_error === false) {
                /** @var Mage_Adminhtml_Model_Session $session */
                $session = Mage::getSingleton('adminhtml/session');
                $session->addError('Algolia indexing failed: You need to configure your Algolia credentials in System > Configuration > Algolia Search.');

                self::$credential_error = true;
            }

            return;
        }

        $data = $event->getNewData();

        /*
         * Reindex all products
         */
        if (!empty($data['algoliasearch_reindex_all'])) {
            $process = $event->getProcess();
            $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        }

        if (!empty($data['catalogsearch_update_category_id'])) {
            $updateCategoryIds = $data['catalogsearch_update_category_id'];
            $updateCategoryIds = is_array($updateCategoryIds) ? $updateCategoryIds : [$updateCategoryIds];

            foreach ($updateCategoryIds as $id) {
                /** @var Mage_Catalog_Model_Category $categoryModel */
                $categoryModel = Mage::getModel('catalog/category');
                $categories = $categoryModel->getCategories($id);

                /** @var Mage_Catalog_Model_Category $category */
                foreach ($categories as $category) {
                    $updateCategoryIds[] = $category->getId();
                }
            }

            $this->engine->rebuildCategoryIndex(null, $updateCategoryIds);
        }

        /*
         * Reindex products.
         */
        if (!empty($data['catalogsearch_update_product_id'])) {
            $updateProductIds = $data['catalogsearch_update_product_id'];
            $updateProductIds = is_array($updateProductIds) ? $updateProductIds : [$updateProductIds];
            $productIds = $updateProductIds;

            foreach ($updateProductIds as $updateProductId) {
                if (!$this->_isProductComposite($updateProductId)) {
                    $parentIds = $this->_getResource()->getRelationsByChild($updateProductId);

                    if (!empty($parentIds)) {
                        $productIds = array_merge($productIds, $parentIds);
                    }
                }
            }

            if (!empty($productIds)) {
                $this->engine->removeProducts(null, $productIds);
                $this->engine->rebuildProductIndex(null, $productIds);
            }
        }
    }

    /**
     * Rebuild all index data.
     */
    public function reindexAll()
    {
        if (!$this->config->getApplicationID() || !$this->config->getAPIKey() || !$this->config->getSearchOnlyAPIKey()) {
            /** @var Mage_Adminhtml_Model_Session $session */
            $session = Mage::getSingleton('adminhtml/session');
            $session->addError('Algolia reindexing failed: You need to configure your Algolia credentials in System > Configuration > Algolia Search.');

            $this->logger->log('ERROR Credentials not configured correctly');

            return;
        }

        $this->logger->start('PRODUCTS FULL REINDEX');
        $this->engine->rebuildProducts();
        $this->logger->stop('PRODUCTS FULL REINDEX');

        return $this;
    }
}
