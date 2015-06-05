<?php

class Algolia_Algoliasearch_Model_Resource_Fulltext extends Mage_CatalogSearch_Model_Resource_Fulltext
{

    /** Empty because we need it to do nothing (no mysql stuff), Indexing is handled by Model/Indexer/Algolia */

    private $engine;

    public function __construct()
    {
        parent::__construct();
        $this->engine = new Algolia_Algoliasearch_Model_Resource_Engine();
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

    public function rebuildIndex($storeId = null, $productIds = null)
    {
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

    public function getSearchableAttributes($backendType = NULL)
    {
        return $this->_getSearchableAttributes($backendType);
    }

    public function getAttributeValue($attributeCode, $value, $storeId, $entity = 'catalog_category')
    {
        return $value;
    }
}