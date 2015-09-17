<?php

class Algolia_Algoliasearch_Model_Resource_Fulltext extends Mage_CatalogSearch_Model_Resource_Fulltext
{

    /** Empty because we need it to do nothing (no mysql stuff), Indexing is handled by Model/Indexer/Algolia */

    private $engine;
    private $config;

    public function __construct()
    {
        parent::__construct();
        $this->engine = new Algolia_Algoliasearch_Model_Resource_Engine();
        $this->config = Mage::helper('algoliasearch/config');
    }

    public function prepareResult($object, $queryText, $query)
    {
        return $this;
    }

    protected function _saveProductIndexes($storeId, $productIndexes)
    {
        return $this;
    }

    public function cleanEntityIndex($entity, $storeId = NULL, $entityId = NULL)
    {
        return $this;
    }

    public function rebuildCategoryIndex($storeId = NULL, $categoryIds = NULL)
    {
        return $this;
    }

    /**
     * Only used when reindexing everything. Otherwise Model/Indexer/Algolia will take care of the rest
     */
    public function rebuildIndex($storeId = null, $productIds = null)
    {
        if (! $this->config->getApplicationID() || ! $this->config->getAPIKey() || ! $this->config->getSearchOnlyAPIKey())
        {
            Mage::getSingleton('adminhtml/session')->addError('Algolia reindexing failed: You need to configure your Algolia credentials in System > Configuration > Algolia Search.');
            return;
        }

        /** Avoid Indexing twice */
        if (is_array($productIds) && $productIds > 0)
            return $this;

        $this->engine->saveSettings();

        if ($storeId == null)
        {
            foreach (Mage::app()->getStores() as $id => $store)
                $this->engine->rebuildProductIndex($id, null);
        }
        else
            $this->engine->rebuildProductIndex($storeId, null);

        return $this;
    }

    public function rebuildProductIndex($storeId = NULL, $productIds = NULL)
    {
        return $this;
    }

    public function getAttributeValue($attributeCode, $value, $storeId, $entity = 'catalog_category')
    {
        return $value;
    }
}