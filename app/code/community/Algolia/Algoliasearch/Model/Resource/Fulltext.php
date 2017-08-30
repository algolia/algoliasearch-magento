<?php

class Algolia_Algoliasearch_Model_Resource_Fulltext extends Mage_CatalogSearch_Model_Resource_Fulltext
{
    /** Empty because we need it to do nothing (no mysql stuff), Indexing is handled by Model/Indexer/Algolia */
    protected $engine;

    /** @var Algolia_Algoliasearch_Helper_Config */
    protected $config;

    /** @var Algolia_Algoliasearch_Helper_Logger */
    protected $logger;

    public function __construct()
    {
        parent::__construct();

        $this->engine = new Algolia_Algoliasearch_Model_Resource_Engine();
        $this->config = Mage::helper('algoliasearch/config');
        $this->logger = Mage::helper('algoliasearch/logger');
    }

    public function prepareResult($object, $queryText, $query)
    {
        if (!$this->config->getApplicationID() || !$this->config->getAPIKey() || $this->config->isEnabledFrontEnd() === false) {
            return parent::prepareResult($object, $queryText, $query);
        }

        return $this;
    }

    protected function _saveProductIndexes($storeId, $productIndexes)
    {
        if ($this->config->isEnabledBackend(Mage::app()->getStore()->getId()) === false) {
            return parent::_saveProductIndexes($storeId, $productIndexes);
        }

        return $this;
    }

    /**
     * Only used when reindexing everything. Otherwise Model/Indexer/Algolia will take care of the rest.
     */
    public function rebuildIndex($storeId = null, $productIds = null)
    {
        if ($this->config->isEnabledBackend(Mage::app()->getStore()->getId()) === false) {
            return parent::rebuildIndex($storeId, $productIds);
        }

        if ($this->config->isModuleOutputEnabled() === false) {
            return parent::rebuildIndex($storeId, $productIds);
        }

        if (!$this->config->getApplicationID() || !$this->config->getAPIKey() || !$this->config->getSearchOnlyAPIKey()) {
            /** @var Mage_Adminhtml_Model_Session $session */
            $session = Mage::getSingleton('adminhtml/session');
            $session->addError('Algolia reindexing failed: You need to configure your Algolia credentials in System > Configuration > Algolia Search.');

            return null;
        }

        /* Avoid Indexing twice */
        if (is_array($productIds) && $productIds > 0) {
            return $this;
        }

        $this->engine->rebuildProducts($storeId);

        return $this;
    }
}
