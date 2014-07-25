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
     * @return Mage_CatalogSearch_Model_Resource_Fulltext
     */
    public function prepareResult($object, $queryText, $query)
    {
        // Fallback to default catalog search if Algolia search is disabled
        if ( ! $this->_helper->isEnabled()) {
            return parent::prepareResult($object, $queryText, $query);
        }

        $adapter = $this->_getWriteAdapter();

        if (!$query->getIsProcessed() || true) {
            $answer = Mage::helper('algoliasearch')->query(Mage::helper('algoliasearch')->getIndexName(Mage::app()->getStore()->getId()), $queryText, array(
                'hitsPerPage' => 1000, // retrieve all the hits (hard limit is 1000)
                'attributesToRetrieve' => array(),
                'tagFilters' => 'product'
            ));

            $i = 0;
            foreach ($answer['hits'] as $hit) {
                $objectIdParts = preg_split('/_/', $hit['objectID']);
                $productId = isset($objectIdParts[1]) ? $objectIdParts[1] : NULL;
                if ($productId) {
                    $sql = sprintf("INSERT INTO `" . $this->getTable('catalogsearch/result') . "`"
                        . " (`query_id`, `product_id`, `relevance`) VALUES"
                        . " (%d, %d, %d)"
                        . " ON DUPLICATE KEY UPDATE `relevance`=VALUES(`relevance`)",
                        $query->getId(),
                        $productId,
                        1000 - $i // relevance based on position
                    );
                    $adapter->query($sql);
                    ++$i;
                }
            }

            $query->setIsProcessed(1);
        }

        return $this;
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
        return preg_replace("#\s+#siu", ' ', trim(strip_tags($value)));
    }
}
