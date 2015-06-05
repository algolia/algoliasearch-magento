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

    public function getAllowedVisibility()
    {
        return Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds();
    }

    public function allowAdvancedIndex()
    {
        return FALSE;
    }

    public function removeProducts($storeId = null, $product_ids = null)
    {
        if (is_array($product_ids) == false)
            $product_ids = array($product_ids);

        if (is_array($product_ids) && count($product_ids) > self::ONE_TIME_AMOUNT) {
            foreach (array_chunk($product_ids, self::ONE_TIME_AMOUNT) as $chunk) {
                $this->helper->removeProducts($chunk, $storeId);
            }
        } else {
            $this->helper->removeProducts($product_ids, $storeId);
        }

        return $this;
    }

    public function removeCategories($storeId = null, $category_ids = null)
    {
        if (is_array($category_ids) == false)
            $category_ids = array($category_ids);

        if (is_array($category_ids) && count($category_ids) > self::ONE_TIME_AMOUNT) {
            foreach (array_chunk($category_ids, self::ONE_TIME_AMOUNT) as $chunk) {
                $this->helper->removeCategories($chunk, $storeId);
            }
        } else {
            $this->helper->removeCategories($category_ids, $storeId);
        }

        return $this;
    }

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

    protected function _rebuildCategoryIndex($storeId = NULL, $categoryIds = NULL)
    {
        $data = array(
            'store_id'     => $storeId,
            'category_ids' => $categoryIds,
        );
        $this->queue->add('algoliasearch/observer', 'rebuildCategoryIndex', $data, 3);
        return $this;
    }

    public function rebuildAll()
    {
        $queue = Mage::getSingleton('algoliasearch/queue');
        $helper = Mage::helper('algoliasearch');

        foreach (Mage::app()->getStores() as $store)
        {
            if ($store->getIsActive())
            {
                $queue->add('algoliasearch/observer', 'rebuildProductIndex', array('store_id' => $store->getId(), 'product_ids' =>  array()), 3);
                $queue->add('algoliasearch/observer', 'rebuildCategoryIndex', array('store_id' => $store->getId(), 'category_ids' =>  array()), 3);
            }
            else
            {
                $helper->deleteStoreIndex($store->getId());
            }
        }

        // if debug
        $queue->run();
    }


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

    protected function _rebuildProductIndex($storeId = NULL, $productIds = NULL)
    {
        $data = array(
            'store_id'    => $storeId,
            'product_ids' => $productIds,
        );
        $this->queue->add('algoliasearch/observer', 'rebuildProductIndex', $data, 3);
        return $this;
    }

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

    public function test()
    {
        return true;
    }
}
