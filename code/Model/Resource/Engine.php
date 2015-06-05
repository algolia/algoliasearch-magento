<?php
/**
 * Algolia search engine model.
 */
class Algolia_Algoliasearch_Model_Resource_Engine extends Mage_CatalogSearch_Model_Resource_Fulltext_Engine
{
    const ONE_TIME_AMOUNT = 100;
    private $helper;
    private $queue;
    private $config;

    public function _construct()
    {
        parent::_construct();

        $this->helper = Mage::helper('algoliasearch');
        $this->queue = Mage::getSingleton('algoliasearch/queue');
        $this->config = new Algolia_Algoliasearch_Helper_Config();
    }

    /**
     * Retrieve allowed visibility values for current engine
     *
     * @return array
     */
    public function getAllowedVisibility()
    {
        return Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds();
    }

    /**
     * Define if current search engine supports advanced index
     *
     * @return bool
     */
    public function allowAdvancedIndex()
    {
        return FALSE;
    }

    /**
     * Enqueue removing data from index.
     * Execute the job only once to avoid overlapping with another job that adds indexes.
     *
     * Examples:
     * (product, null, null) => Clean product index of all stores
     * (product, 1, null)    => Clean product index of store Id=1
     * (product, 1, 2)       => Clean index of product Id=2 and its store view Id=1
     * (product, null, 2)    => Clean index of all store views of product Id=2
     *
     * @param string $entity 'product'|'category'
     * @param int|null $storeId
     * @param int|array|null $entityId
     * @return Algolia_Algoliasearch_Model_Resource_Engine
     */
    public function cleanEntityIndex($entity, $storeId = NULL, $entityId = NULL)
    {
        if (is_array($entityId) && count($entityId) > self::ONE_TIME_AMOUNT) {
            foreach (array_chunk($entityId, self::ONE_TIME_AMOUNT) as $chunk) {
                $this->_cleanEntityIndex($entity, $storeId, $chunk);
            }
        } else {
            $this->_cleanEntityIndex($entity, $storeId, $entityId);
        }
        return $this;
    }

    /**
     * @param string $entity 'product'|'category'
     * @param int|null $storeId
     * @param int|array|null $entityId
     * @return Algolia_Algoliasearch_Model_Resource_Engine
     */
    protected function _cleanEntityIndex($entity, $storeId = NULL, $entityId = NULL)
    {
        $data = $entityId;

        if (is_array($data) == false)
            $data = array($data);

        if ($entity == 'product')
            $this->helper->removeProducts($data, $storeId);

        if ($entity == 'category')
            $this->helper->removeCategories($data, $storeId);

        return $this;
    }

    /**
     * Enqueue indexing for the specified categories
     *
     * @param null|int $storeId
     * @param null|int|array $categoryIds
     * @return Algolia_Algoliasearch_Model_Resource_Engine
     */
    public function rebuildCategoryIndex($storeId = NULL, $categoryIds = NULL)
    {
        if (is_array($categoryIds) && count($categoryIds) > self::ONE_TIME_AMOUNT) {
            foreach (array_chunk($categoryIds, self::ONE_TIME_AMOUNT) as $chunk) {
                $this->_rebuildCategoryIndex($storeId, $chunk);
            }
        } else {
            $this->_rebuildCategoryIndex($storeId, $categoryIds);
        }
        return $this;
    }

    /**
     * @param null|int $storeId
     * @param null|int|array $categoryIds
     * @return Algolia_Algoliasearch_Model_Resource_Engine
     */
    protected function _rebuildCategoryIndex($storeId = NULL, $categoryIds = NULL)
    {
        $data = array(
            'store_id'     => $storeId,
            'category_ids' => $categoryIds,
        );
        $this->queue->add('algoliasearch/observer', 'rebuildCategoryIndex', $data, 3);
        return $this;
    }

    /**
     * Enqueue indexing for the specified products
     *
     * @param null|int $storeId
     * @param null|int|array $productIds
     * @return Algolia_Algoliasearch_Model_Resource_Engine
     */
    public function rebuildProductIndex($storeId = NULL, $productIds = NULL)
    {
        if (is_array($productIds) && count($productIds) > self::ONE_TIME_AMOUNT) {
            foreach (array_chunk($productIds, self::ONE_TIME_AMOUNT) as $chunk) {
                $this->_rebuildProductIndex($storeId, $chunk);
            }
        } else {
            $this->_rebuildProductIndex($storeId, $productIds);
        }
        return $this;
    }

    /**
     * @param null|int $storeId
     * @param null|int|array $productIds
     * @return Algolia_Algoliasearch_Model_Resource_Engine
     */
    protected function _rebuildProductIndex($storeId = NULL, $productIds = NULL)
    {
        $data = array(
            'store_id'    => $storeId,
            'product_ids' => $productIds,
        );
        $this->queue->add('algoliasearch/observer', 'rebuildProductIndex', $data, 3);
        return $this;
    }

    /**
     * Prepare index array. Array values will be converted to a string glued by separator.
     *
     * @param array $index
     * @param string $separator
     * @return string
     */
    public function prepareEntityIndex($index, $separator = ' ')
    {
        foreach ($index as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $index[$key] = join($separator, array_unique(array_filter($value)));
            } else if (empty($index[$key])) {
                unset($index[$key]);
            }
        }
        return $index;
    }

    /**
     * Custom alias for the original method that checks whether layered navigation is allowed
     *
     * @return bool
     */
    public function isLayeredNavigationAllowed()
    {
        return $this->isLeyeredNavigationAllowed();
    }

    /**
     * Define if engine is available
     *
     * @return bool
     */
    public function test()
    {
        if ( ! $this->helper->isEnabled()) {
            return parent::test();
        }

        return
            $this->config->getApplicationID() &&
            $this->config->getAPIKey() &&
            $this->config->getSearchOnlyAPIKey();
    }
}
