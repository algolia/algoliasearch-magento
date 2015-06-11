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
    private $product_helper;

    public function _construct()
    {
        parent::_construct();

        $this->helper = Mage::helper('algoliasearch');
        $this->queue = Mage::getSingleton('algoliasearch/queue');
        $this->config = new Algolia_Algoliasearch_Helper_Config();
        $this->product_helper = new Algolia_Algoliasearch_Helper_Entity_Producthelper();
    }

    public function addToQueue($observer, $method, $data, $nb_retry)
    {
        $later = true;

        /*if (isset($data['product_ids']) && is_array($data['product_ids']) && count($data['product_ids']) == 1)
            $later = false;

        if (isset($data['category_ids']) && is_array($data['category_ids']) && count($data['category_ids']) == 1)
            $later = false;*/

        if ($this->config->isQueueActive() && $later)
            $this->queue->add($observer, $method, $data, $nb_retry);
        else
            Mage::getSingleton($observer)->$method(new Varien_Object($data));
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

        $by_page = $this->config->getNumberOfProductByPage();

        if (is_array($product_ids) && count($product_ids) > $by_page) {
            foreach (array_chunk($product_ids, $by_page) as $chunk) {
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

        $by_page = $this->config->getNumberOfProductByPage();

        if (is_array($category_ids) && count($category_ids) > $by_page) {
            foreach (array_chunk($category_ids, $by_page) as $chunk) {
                $this->helper->removeCategories($chunk, $storeId);
            }
        } else {
            $this->helper->removeCategories($category_ids, $storeId);
        }

        return $this;
    }

    public function rebuildCategoryIndex($storeId = null, $categoryIds = null)
    {
        $by_page = $this->config->getNumberOfProductByPage();

        if (is_array($categoryIds) && count($categoryIds) > $by_page) {
            foreach (array_chunk($categoryIds, $by_page) as $chunk) {
                $this->_rebuildCategoryIndex($storeId, $chunk);
            }
        } else {
            $this->_rebuildCategoryIndex($storeId, $categoryIds);
        }
        return $this;
    }

    protected function _rebuildCategoryIndex($storeId = null, $categoryIds = null)
    {
        $data = array(
            'store_id'     => $storeId,
            'category_ids' => $categoryIds,
        );
        $this->addToQueue('algoliasearch/observer', 'rebuildCategoryIndex', $data, 3);
        return $this;
    }

    public function rebuildAll()
    {
        foreach (Mage::app()->getStores() as $store)
        {
            if ($store->getIsActive())
            {
                $this->_rebuildProductIndex($store->getId(), array());

                $this->addToQueue('algoliasearch/observer', 'rebuildCategoryIndex', array('store_id' => $store->getId(), 'category_ids' =>  array()), 3);
            }
            else
            {
                $this->helper->deleteStoreIndices($store->getId());
            }
        }
    }

    private function getStores($store_id)
    {
        $store_ids = array();

        if ($store_id == null)
        {
            foreach (Mage::app()->getStores() as $store)
                if ($store->getIsActive())
                    $store_ids[] = $store->getId();
        }
        else
            $store_ids = array($store_id);

        return $store_ids;
    }

    public function rebuildProductIndex($storeId = null, $productIds = null)
    {
        $ids = $this->getStores($storeId);

        foreach ($ids as $id)
        {
            $by_page = $this->config->getNumberOfProductByPage();

            if (is_array($productIds) && count($productIds) > $by_page)
            {
                foreach (array_chunk($productIds, $by_page) as $chunk)
                    $this->_rebuildProductIndex($id, $chunk);
            }
            else
                $this->_rebuildProductIndex($id, $productIds);
        }

        return $this;
    }

    protected function _rebuildProductIndex($storeId, $productIds = null)
    {
        if ($productIds == null || count($productIds) == 0)
        {
            $size       = $this->product_helper->getProductCollectionQuery($storeId, $productIds)->getSize();
            $by_page    = $this->config->getNumberOfProductByPage();
            $nb_page    = ceil($size / $by_page);

            for ($i = 1; $i <= $nb_page; $i++)
            {
                $data = array('store_id' => $storeId, 'product_ids' =>  $productIds, 'page_size' => $by_page, 'page' => $i);
                $this->addToQueue('algoliasearch/observer', 'rebuildProductIndex', $data, 3);
            }
        }
        else
            $this->addToQueue('algoliasearch/observer', 'rebuildProductIndex', array('store_id' => $storeId, 'product_ids' =>  $productIds), 3);

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
