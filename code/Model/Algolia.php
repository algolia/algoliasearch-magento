<?php

/**
 * Algolia search indexer
 *
 * @method Algolia_Algoliasearch_Model_Resource_Fulltext _getResource()
 * @method Algolia_Algoliasearch_Model_Resource_Fulltext getResource()
 */
class Algolia_Algoliasearch_Model_Algolia extends Mage_Core_Model_Abstract
{
    const ENTITY_CATEGORY = 'category';
    const ENTITY_PRODUCT  = 'product';

    protected function _construct()
    {
        $this->_init('catalogsearch/fulltext');
    }

    /**
     * Rebuild index for the specified products
     *
     * @param null|int $storeId
     * @param null|int|array $productIds
     * @return Algolia_Algoliasearch_Model_Algolia
     */
    public function rebuildProductIndex($storeId = NULL, $productIds = NULL)
    {
        $this->getResource()->rebuildProductIndex($storeId, $productIds);
        return $this;
    }

    /**
     * Rebuild index for the specified categories
     *
     * @param null|int $storeId
     * @param null|int|array $categoryIds
     * @return Algolia_Algoliasearch_Model_Algolia
     */
    public function rebuildCategoryIndex($storeId = NULL, $categoryIds = NULL)
    {
        $this->getResource()->rebuildCategoryIndex($storeId, $categoryIds);
        return $this;
    }

    /**
     * Delete product index
     *
     * @param null|int $storeId
     * @param null|int|array $productId
     * @return Algolia_Algoliasearch_Model_Algolia
     */
    public function cleanProductIndex($storeId = NULL, $productId = NULL)
    {
        $this->getResource()->cleanIndex(self::ENTITY_PRODUCT, $storeId, $productId);
        return $this;
    }

    /**
     * Delete category index
     *
     * @param int|null $storeId
     * @param int|null|array $categoryId
     * @return Algolia_Algoliasearch_Model_Algolia
     */
    public function cleanCategoryIndex($storeId = NULL, $categoryId = NULL)
    {
        $this->getResource()->cleanIndex(self::ENTITY_CATEGORY, $storeId, $categoryId);
        return $this;
    }

    /**
     * Rebuild all index data for products and categories
     *
     * @return Algolia_Algoliasearch_Model_Algolia
     */
    public function rebuildIndex()
    {
        foreach (Mage::app()->getStores() as $store) { /** @var $store Mage_Core_Model_Store */
            if ($store->getIsActive()) {
                Mage::helper('algoliasearch')->setIndexSettings($store->getId());
                $this->rebuildCategoryIndex($store->getId());
                $this->rebuildProductIndex($store->getId());
            } else {
                Mage::helper('algoliasearch')->deleteStoreIndex($store->getId());
            }
        }
        $this->resetSearchResults();
        return $this;
    }

    /**
     * Reset search results cache
     *
     * @return Mage_CatalogSearch_Model_Fulltext
     */
    public function resetSearchResults()
    {
        $this->getResource()->resetSearchResults();
        return $this;
    }
}
