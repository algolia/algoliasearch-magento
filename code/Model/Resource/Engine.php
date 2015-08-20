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
    private $category_helper;
    private $suggestion_helper;

    public function _construct()
    {
        parent::_construct();

        $this->queue = Mage::getSingleton('algoliasearch/queue');
        $this->config = Mage::helper('algoliasearch/config');
        $this->product_helper = Mage::helper('algoliasearch/entity_producthelper');
        $this->category_helper = Mage::helper('algoliasearch/entity_categoryhelper');
        $this->suggestion_helper = Mage::helper('algoliasearch/entity_suggestionhelper');
    }

    public function addToQueue($observer, $method, $data, $nb_retry)
    {
        if ($this->config->isQueueActive())
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

        $by_page = $this->config->getNumberOfElementByPage();

        if (is_array($product_ids) && count($product_ids) > $by_page)
        {
            foreach (array_chunk($product_ids, $by_page) as $chunk)
                $this->addToQueue('algoliasearch/observer', 'removeProducts', array('store_id' => $storeId, 'product_ids' => $chunk), $this->config->getQueueMaxRetries());
        }
        else
            $this->addToQueue('algoliasearch/observer', 'removeProducts', array('store_id' => $storeId, 'product_ids' => $product_ids), $this->config->getQueueMaxRetries());

        return $this;
    }

    public function removeCategories($storeId = null, $category_ids = null)
    {
        if (is_array($category_ids) == false)
            $category_ids = array($category_ids);

        $by_page = $this->config->getNumberOfElementByPage();

        if (is_array($category_ids) && count($category_ids) > $by_page)
        {
            foreach (array_chunk($category_ids, $by_page) as $chunk)
                $this->addToQueue('algoliasearch/observer', 'removeCategories', array('store_id' => $storeId, 'category_ids' => $chunk), $this->config->getQueueMaxRetries());
        }
        else
            $this->addToQueue('algoliasearch/observer', 'removeCategories', array('store_id' => $storeId, 'category_ids' => $category_ids), $this->config->getQueueMaxRetries());

        return $this;
    }

    public function rebuildCategoryIndex($storeId = null, $categoryIds = null)
    {
        $by_page = $this->config->getNumberOfElementByPage();

        if (is_array($categoryIds) && count($categoryIds) > $by_page) {
            foreach (array_chunk($categoryIds, $by_page) as $chunk) {
                $this->_rebuildCategoryIndex($storeId, $chunk);
            }
        } else {
            $this->_rebuildCategoryIndex($storeId, $categoryIds);
        }
        return $this;
    }

    public function rebuildPages()
    {
        foreach (Mage::app()->getStores() as $store)
        {
            $this->addToQueue('algoliasearch/observer', 'rebuildPageIndex', array('store_id' => $store->getId()), $this->config->getQueueMaxRetries());
        }
    }

    public function rebuildAdditionalSections()
    {
        foreach (Mage::app()->getStores() as $store)
        {
            $this->addToQueue('algoliasearch/observer', 'rebuildAdditionalSectionsIndex', array('store_id' => $store->getId()), $this->config->getQueueMaxRetries());
        }
    }

    public function rebuildSuggestions()
    {
        foreach (Mage::app()->getStores() as $store)
        {
            $size       = $this->suggestion_helper->getSuggestionCollectionQuery($store->getId())->getSize();
            $by_page    = $this->config->getNumberOfElementByPage();
            $nb_page    = ceil($size / $by_page);

            for ($i = 1; $i <= $nb_page; $i++)
            {
                $data = array('store_id' => $store->getId(), 'page_size' => $by_page, 'page' => $i);
                $this->addToQueue('algoliasearch/observer', 'rebuildSuggestionIndex', $data, $this->config->getQueueMaxRetries());
            }

            $this->addToQueue('algoliasearch/observer', 'moveStoreSuggestionIndex', array('store_id' => $store->getId()), $this->config->getQueueMaxRetries());
        }


        return $this;
    }

    public function rebuildProductsAndCategories()
    {
        Mage::getSingleton('algoliasearch/observer')->saveSettings();

        foreach (Mage::app()->getStores() as $store)
        {
            if ($store->getIsActive())
            {
                $this->_rebuildProductIndex($store->getId(), array());

                $this->addToQueue('algoliasearch/observer', 'rebuildCategoryIndex', array('store_id' => $store->getId(), 'category_ids' =>  array()), $this->config->getQueueMaxRetries());
            }
            else
            {
                $this->addToQueue('algoliasearch/observer', 'deleteProductsAndCategoriesStoreIndices', array('store_id' => $store->getId()), $this->config->getQueueMaxRetries());
            }
        }
    }

    public function rebuildProductIndex($storeId = null, $productIds = null)
    {
        $ids = Algolia_Algoliasearch_Helper_Entity_Helper::getStores($storeId);

        foreach ($ids as $id)
        {
            $by_page = $this->config->getNumberOfElementByPage();

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

    private function _rebuildCategoryIndex($storeId = null, $categoryIds = null)
    {
        if ($categoryIds == null || count($categoryIds) == 0)
        {
            $size       = $this->category_helper->getCategoryCollectionQuery($storeId, $categoryIds)->getSize();
            $by_page    = $this->config->getNumberOfElementByPage();
            $nb_page    = ceil($size / $by_page);

            for ($i = 1; $i <= $nb_page; $i++)
            {
                $data = array('store_id' => $storeId, 'category_ids' => $categoryIds, 'page_size' => $by_page, 'page' => $i);
                $this->addToQueue('algoliasearch/observer', 'rebuildCategoryIndex', $data, $this->config->getQueueMaxRetries());
            }
        }
        else
            $this->addToQueue('algoliasearch/observer', 'rebuildCategoryIndex', array('store_id' => $storeId, 'category_ids' => $categoryIds), $this->config->getQueueMaxRetries());


        return $this;
    }

    private function _rebuildProductIndex($storeId, $productIds = null)
    {
        if ($productIds == null || count($productIds) == 0)
        {
            $size       = $this->product_helper->getProductCollectionQuery($storeId, $productIds)->getSize();
            $by_page    = $this->config->getNumberOfElementByPage();
            $nb_page    = ceil($size / $by_page);

            for ($i = 1; $i <= $nb_page; $i++)
            {
                $data = array('store_id' => $storeId, 'product_ids' =>  $productIds, 'page_size' => $by_page, 'page' => $i);
                $this->addToQueue('algoliasearch/observer', 'rebuildProductIndex', $data, $this->config->getQueueMaxRetries());
            }
        }
        else
            $this->addToQueue('algoliasearch/observer', 'rebuildProductIndex', array('store_id' => $storeId, 'product_ids' =>  $productIds), $this->config->getQueueMaxRetries());

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

    public function saveSettings()
    {
        Mage::getSingleton('algoliasearch/observer')->saveSettings();
    }

    public function test()
    {
        return true;
    }
}
