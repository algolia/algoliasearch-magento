<?php

class Algolia_Algoliasearch_Model_Indexer_Algolia extends Algolia_Algoliasearch_Model_Indexer_Abstract
{
    const EVENT_MATCH_RESULT_KEY = 'algoliasearch_match_result';

    /** @var Algolia_Algoliasearch_Helper_Config */
    protected $config;

    /** @var Algolia_Algoliasearch_Helper_Logger */
    protected $logger;

    public static $product_categories = array();
    protected static $credential_error = false;

    public function __construct()
    {
        parent::__construct();

        $this->config = Mage::helper('algoliasearch/config');
        $this->logger = Mage::helper('algoliasearch/logger');
    }

    protected $_matchedEntities = array(
        Mage_Catalog_Model_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION,
            Mage_Index_Model_Event::TYPE_DELETE,
        ),
        Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
        ),
        Mage_Core_Model_Store::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
        ),
        Mage_Core_Model_Store_Group::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
        ),
        Mage_Core_Model_Config_Data::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
        ),
        Mage_Catalog_Model_Convert_Adapter_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
        ),
        Mage_Catalog_Model_Category::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
        ),
    );

    public function getName()
    {
        return Mage::helper('algoliasearch')->__('Algolia Search Products');
    }

    public function getDescription()
    {
        /** @var Algolia_Algoliasearch_Helper_Data $helper */
        $helper = Mage::helper('algoliasearch');
        $decription = $helper->__('Rebuild products.').' '.$helper->__($this->enableQueueMsg);

        return $decription;
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

    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        if ($this->config->isModuleOutputEnabled() === false) {
            return;
        }

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
            $this->reindexSpecificCategories($data['catalogsearch_update_category_id']);
        }

        /*
         * Reindex products.
         */
        if (!empty($data['catalogsearch_update_product_id'])) {
            $this->reindexSpecificProducts($data['catalogsearch_update_product_id']);
        }
    }

    /**
     * Rebuild all index data.
     */
    public function reindexAll()
    {
        if ($this->config->isModuleOutputEnabled() === false) {
            return $this;
        }

        if (!$this->config->getApplicationID() || !$this->config->getAPIKey() || !$this->config->getSearchOnlyAPIKey()) {
            /** @var Mage_Adminhtml_Model_Session $session */
            $session = Mage::getSingleton('adminhtml/session');
            $session->addError('Algolia reindexing failed: You need to configure your Algolia credentials in System > Configuration > Algolia Search.');

            $this->logger->log('ERROR Credentials not configured correctly');

            return $this;
        }

        $this->logger->start('PRODUCTS FULL REINDEX');
        $this->engine->rebuildProducts();
        $this->logger->stop('PRODUCTS FULL REINDEX');

        return $this;
    }
}
