<?php

class Algolia_Algoliasearch_Model_Resource_Fulltext extends Mage_CatalogSearch_Model_Resource_Fulltext
{
    /**
     * Algolia search engine instance
     *
     * @var null|Algolia_Algoliasearch_Model_Resource_Engine
     */
    protected $_engine = NULL;

    /**
     * Algolia search helper
     *
     * @var null|Algolia_Algoliasearch_Helper_Data
     */
    protected $_helper = NULL;

    public function _construct()
    {
        parent::_construct();
        $this->_helper = Mage::helper('algoliasearch');
    }

    /**
     * Prepare results for query
     *
     * @param Mage_CatalogSearch_Model_Fulltext $object
     * @param string $queryText
     * @param Mage_CatalogSearch_Model_Query $query
     * @return Algolia_Algoliasearch_Model_Resource_Fulltext
     */
    public function prepareResult($object, $queryText, $query)
    {
        Varien_Profiler::start('Algolia/FullText-prepareResult');
        try {
            $this->beginTransaction();
            if ( ! $this->lockQueryForTransaction($query)) {
                $this->commit();
                Varien_Profiler::stop('Algolia/FullText-prepareResult');
                return $this;
            }

            // Fallback to default catalog search if Algolia search is disabled
            if ( ! $this->_helper->isEnabled()) {
                parent::prepareResult($object, $queryText, $query);
                $this->commit();
                Varien_Profiler::stop('Algolia/FullText-prepareResult');
                return $this;
            }

            Varien_Profiler::start('Algolia/FullText-prepareResult-process');
            if (!$query->getIsProcessed())
            {

                $resultsLimit = Mage::helper('algoliasearch')->getResultsLimit($query->getStoreId());
                try {
                    $answer = Mage::helper('algoliasearch')->query(Mage::helper('algoliasearch')->getIndexName(Mage::app()->getStore()->getId()), $queryText, array(
                        'hitsPerPage' => max(5,min($resultsLimit, 1000)), // retrieve all the hits (hard limit is 1000)
                        'attributesToRetrieve' => 'objectID',
                        'attributesToHighlight' => '',
                        'attributesToSnippet' => '',
                        'tagFilters' => 'product',
                        'removeWordsIfNoResult'=> Mage::helper('algoliasearch')->getRemoveWordsIfNoResult(Mage::app()->getStore()->getId()),
                    ));
                } catch (Exception $e) {
                    Mage::getSingleton('catalog/session')->addError(Mage::helper('algoliasearch')->__('Search failed. Please try again.'));
                    throw $e;
                }

                $data = array();
                foreach ($answer['hits'] as $i => $hit) {
                    $objectIdParts = explode('_', $hit['objectID'], 2);
                    $productId = ! empty($objectIdParts[1]) && ctype_digit($objectIdParts[1]) ? (int)$objectIdParts[1] : NULL;
                    if ($productId) {
                        $data[$productId] = array(
                            'query_id' => $query->getId(),
                            'product_id' => $productId,
                            'relevance' => $resultsLimit - $i,
                        );
                    }
                }

                // Filter products that do not exist or are disabled for the website (e.g. if product was deleted or removed
                // from catalog but not yet from index). Avoids foreign key errors and incorrect listings
                if ($data) {
                    $existingProductIds = $this->_getWriteAdapter()->fetchCol($this->_getWriteAdapter()->select()
                        ->from($this->_getWriteAdapter()->getTableName('catalog_product_website'), array('product_id'))
                        ->where('website_id = ?', Mage::app()->getStore($query->getStoreId())->getWebsiteId())
                        ->where('product_id IN (?)', array_keys($data))
                    );
                    $ignoreProductIds = array_diff(array_keys($data), $existingProductIds);
                    foreach ($ignoreProductIds as $productId) {
                        unset($data[$productId]);
                    }
                }

                // Delete old results that are no longer relevant
                if ($query->getId()) {
                    $deleteWhere = $this->_getWriteAdapter()->quoteInto('query_id = ?', $query->getId());
                    if ($data) {
                        $deleteWhere .= ' AND '.$this->_getWriteAdapter()->quoteInto('product_id NOT IN (?)', array_keys($data));
                    }
                    $this->_getWriteAdapter()->delete($this->getTable('catalogsearch/result'), $deleteWhere);
                }

                // Insert results / update relevance
                if ($data) {
                    // Lock for update to avoid deadlocks with in-progress orders (foreign key on catalog_product_entity locks rows)
                    $stock = Mage::getSingleton('cataloginventory/stock');
                    $stock->getResource()->getProductsStock($stock, array_keys($data), true);

                    $this->_getWriteAdapter()->insertOnDuplicate(
                         $this->getTable('catalogsearch/result'),
                         array_values($data),
                         array('relevance')
                    );
                }
                $query->setIsProcessed(1);
            }

            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            Mage::logException($e);
        }
        Varien_Profiler::stop('Algolia/FullText-prepareResult-process');
        Varien_Profiler::stop('Algolia/FullText-prepareResult');

        return $this;
    }

    /**
     * Lock query entity for transaction to prevent FK error while updating search results.
     * Use fulltext resource model instead of query resource model to avoid additional override.
     *
     * @param int|Mage_CatalogSearch_Model_Query $queryId
     * @return bool true if query entity was locked
     */
    public function lockQueryForTransaction(Mage_CatalogSearch_Model_Query $queryId)
    {
        if ($queryId instanceof Mage_CatalogSearch_Model_Query) {
            $queryId = $queryId->getId();
        }
        if ( ! $queryId) {
            return FALSE;
        }
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()->forUpdate(TRUE)
            ->from(array('query' => $this->getTable('catalogsearch/search_query')), array('query_id'))
            ->where('query.query_id = ?', $queryId);
        return ($queryId == $adapter->fetchOne($select));
    }

    /**
     * Save multiply product indexes.
     *
     * @param int   $storeId
     * @param array $productIndexes
     * @return Algolia_Algoliasearch_Model_Resource_Fulltext
     */
    protected function _saveProductIndexes($storeId, $productIndexes)
    {
        // Fallback to default catalog search if Algolia search is disabled
        if ( ! $this->_helper->isEnabled()) {
            return parent::_saveProductIndexes($storeId, $productIndexes);
        }

        Mage::helper('algoliasearch')->rebuildStoreProductIndex($storeId, array_keys($productIndexes), $productIndexes);
        return $this;
    }

    /**
     * Delete entity(ies) index
     *
     * @param null|string    $entity catalog|product
     * @param null|int       $storeId
     * @param null|int|array $entityId
     * @return Algolia_Algoliasearch_Model_Resource_Fulltext
     */
    public function cleanEntityIndex($entity, $storeId = NULL, $entityId = NULL)
    {
        if ($this->_helper->isEnabled($storeId) && is_object($this->_engine) && is_callable(array($this->_engine, 'cleanEntityIndex'))) {
            $this->_engine->cleanEntityIndex($entity, $storeId, $entityId);
        }
        return $this;
    }

    /**
     * Rebuild index for the specified categories
     *
     * @param null|int       $storeId
     * @param null|int|array $categoryIds
     * @return Algolia_Algoliasearch_Model_Resource_Fulltext
     */
    public function rebuildCategoryIndex($storeId = NULL, $categoryIds = NULL)
    {
        if ($this->_helper->isEnabled($storeId) && is_object($this->_engine) && is_callable(array($this->_engine, 'rebuildCategoryIndex'))) {
            $this->_engine->rebuildCategoryIndex($storeId, $categoryIds);
        }
        return $this;
    }

    /**
     * Rebuild index for the specified products
     *
     * @param null|int       $storeId
     * @param null|int|array $productIds
     * @return Algolia_Algoliasearch_Model_Resource_Fulltext
     */
    public function rebuildProductIndex($storeId = NULL, $productIds = NULL)
    {
        if ($this->_helper->isEnabled($storeId) && is_object($this->_engine) && is_callable(array($this->_engine, 'rebuildProductIndex'))) {
            $this->_engine->rebuildProductIndex($storeId, $productIds);
        }
        return $this;
    }

    /**
     * Retrieve Searchable attributes
     *
     * @param string $backendType
     * @return array
     */
    public function getSearchableAttributes($backendType = NULL)
    {
        return $this->_getSearchableAttributes($backendType);
    }

    /**
     * Retrieve attribute source value for search
     *
     * @param string $attributeCode
     * @param mixed $value
     * @param int $storeId
     * @param null|string $entity
     * @return mixed
     */
    public function getAttributeValue($attributeCode, $value, $storeId, $entity = 'catalog_category')
    {
        $attribute = Mage::getSingleton('eav/config')->getAttribute($entity, $attributeCode);
        if ( ! $attribute instanceof Mage_Eav_Model_Entity_Attribute_Abstract) {
            return NULL;
        }

        if ($attribute->usesSource()) {
            $attribute->setStoreId($storeId);
            $value = $attribute->getSource()->getOptionText($value);
        }
        if ($attribute->getBackendType() == 'datetime') {
            $value = $this->_getStoreDate($storeId, $value);
        }

        $inputType = $attribute->getFrontend()->getInputType();
        if ($inputType == 'price') {
            $value = Mage::app()->getStore($storeId)->roundPrice($value);
        }

        if (is_array($value)) {
            return array_map(array($this, '_cleanData'), $value);
        } elseif (empty($value) && ($inputType == 'select' || $inputType == 'multiselect')) {
            return NULL;
        }

        return $this->_cleanData($value);
    }

    protected function _cleanData($value)
    {
        if ( ! is_string($value)) {
          return $value;
        }
        return preg_replace("#\s+#siu", ' ', trim(strip_tags($value)));
    }
}
