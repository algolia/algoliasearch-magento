<?php

abstract class Algolia_Algoliasearch_Model_Indexer_Abstract extends Mage_Index_Model_Indexer_Abstract
{
    /** @var Algolia_Algoliasearch_Model_Resource_Engine */
    protected $engine;

    protected $enableQueueMsg = 'Please enable the queueing system to do it asynchronously (CRON) if you have a lot of products in System > Configuration > Algolia Search > Queue configuration';

    public function __construct()
    {
        parent::__construct();

        $this->engine = Mage::getResourceModel('algoliasearch/engine');
    }

    /**
     * This function will update all the requested categories and their child
     * categories in Algolia. You can provide either a single category ID or an
     * array of category IDs. A category ID should be either a string or an
     * integer.
     *
     * @param array|string|int $updateCategoryIds
     */
    public function reindexSpecificCategories($updateCategoryIds)
    {
        $updateCategoryIds = (array) $updateCategoryIds;

        foreach ($updateCategoryIds as $id) {
            /** @var Mage_Catalog_Model_Category $categoryModel */
            $categoryModel = Mage::getModel('catalog/category');
            $categories = $categoryModel->getCategories($id);

            foreach ($categories as $category) {
                $updateCategoryIds[] = $category->getId();
            }
        }

        $this->engine->rebuildCategoryIndex(null, $updateCategoryIds);
    }

    /**
     * This function will update all the requested products and their parent
     * products in Algolia. You can provide either a single product ID or an
     * array of product IDs. A product ID should be either a string or an
     * integer.
     *
     * @param array|string|int $updateProductIds
     */
    public function reindexSpecificProducts($updateProductIds)
    {
        $updateProductIds = (array) $updateProductIds;
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
            $this->engine->rebuildProductIndex(null, $productIds);
        }
    }

    /**
     * @return Mage_CatalogSearch_Model_Resource_Indexer_Fulltext
     */
    protected function _getResource()
    {
        return Mage::getResourceSingleton('catalogsearch/indexer_fulltext');
    }

    /**
     * Check whether a product is composite.
     *
     * @param int $productId
     *
     * @return bool
     */
    protected function _isProductComposite($productId)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product')->loadByAttribute('entity_id', $productId);

        if ($product === false) {
            return false;
        }

        return $product->isComposite();
    }
}
