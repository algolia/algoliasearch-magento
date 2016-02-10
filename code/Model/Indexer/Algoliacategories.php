<?php

class Algolia_Algoliasearch_Model_Indexer_Algoliacategories extends Mage_Index_Model_Indexer_Abstract
{
    const EVENT_MATCH_RESULT_KEY = 'algoliasearch_match_result';

    /** @var Algolia_Algoliasearch_Model_Resource_Engine */
    protected $engine;
    protected $config;

    protected static $credential_error = false;

    public function __construct()
    {
        parent::__construct();

        $this->engine = new Algolia_Algoliasearch_Model_Resource_Engine();
        $this->config = Mage::helper('algoliasearch/config');
    }

    protected $_matchedEntities = array(
        Mage_Catalog_Model_Category::ENTITY                => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE
        )
    );

    protected function _getResource()
    {
        return Mage::getResourceSingleton('catalogsearch/indexer_fulltext');
    }

    public function getName()
    {
        return Mage::helper('algoliasearch')->__('Algolia Search Categories');
    }

    public function getDescription()
    {
        return Mage::helper('algoliasearch')->__('Rebuild category index.
        Please enable the queueing system to do it asynchronously (CRON) if you have a lot of products in System > Configuration > Algolia Search > Queue configuration');
    }

    public function matchEvent(Mage_Index_Model_Event $event)
    {
        $result = $event->getEntity() !== 'core_config_data';;

        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, $result);

        return $result;
    }

    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, TRUE);

        switch ($event->getEntity()) {
            case Mage_Catalog_Model_Category::ENTITY:
                $this->_registerCatalogCategoryEvent($event);
                break;
        }
    }

    protected function _registerCatalogCategoryEvent(Mage_Index_Model_Event $event)
    {
        switch ($event->getType()) {
            case Mage_Index_Model_Event::TYPE_SAVE:

                /** @var $category Mage_Catalog_Model_Category */
                $category   = $event->getDataObject();
                $productIds = $category->getAffectedProductIds();

                if (! $category->getData('is_active') || ! $category->getData('include_in_menu'))
                {
                    $event->addNewData('catalogsearch_delete_category_id', array_merge(array($category->getId()), $category->getAllChildren(TRUE)));

                    if ($productIds)
                    {
                        $event->addNewData('catalogsearch_update_product_id', $productIds);
                    }
                }
                elseif ($productIds)
                {
                    $event->addNewData('catalogsearch_update_product_id', $productIds);
                    $event->addNewData('catalogsearch_update_category_id', array($category->getId()));
                }
                elseif ($movedCategoryId = $category->getMovedCategoryId())
                {
                    $event->addNewData('catalogsearch_update_category_id', array($movedCategoryId));
                }
                else
                {
                    $event->addNewData('catalogsearch_update_category_id', array($category->getId()));
                }

                break;

            case Mage_Index_Model_Event::TYPE_DELETE:

                /** @var $category Mage_Catalog_Model_Category */
                $category = $event->getDataObject();

                $event->addNewData('catalogsearch_delete_category_id', $category->getId());

                break;
        }

        return $this;
    }

    protected function _isProductComposite($productId)
    {
        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product')->load($productId);
        return $product->isComposite();
    }

    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        if (! $this->config->getApplicationID() || ! $this->config->getAPIKey() || ! $this->config->getSearchOnlyAPIKey())
        {
            if (self::$credential_error === false)
            {
                Mage::getSingleton('adminhtml/session')->addError('Algolia indexing failed: You need to configure your Algolia credentials in System > Configuration > Algolia Search.');
                self::$credential_error = true;
            }

            return;
        }

        $data = $event->getNewData();

        /*
         * Reindex all products and all categories and update index settings
         */
        if ( ! empty($data['algoliasearch_reindex_all'])) {
            $process = $event->getProcess();
            $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        }

        /*
         * Clear indexer for the deleted category including all children categories and update index for the related products.
         */
        else if ( ! empty($data['catalogsearch_delete_category_id'])) {
            $categoryIds = $data['catalogsearch_delete_category_id'];
            $this->engine
                ->removeCategories(null, $categoryIds);
            /*
             * Change indexer status as need to reindex related products to update the list of categories.
             * It's low priority so no need to automatically reindex all related products after deleting each category.
             * Do not reindex all if affected products are given or product count is not indexed.
             */
            if ( ! isset($data['catalogsearch_update_product_id'])) {
                $process = $event->getProcess();
                $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
            }
        }

        /*
         * Reindex categories.
         * Category products are tracked separately. The specified categories are active. See _registerCatalogCategoryEvent().
         */
        if ( ! empty($data['catalogsearch_update_category_id'])) {
            $updateCategoryIds = $data['catalogsearch_update_category_id'];
            $updateCategoryIds = is_array($updateCategoryIds) ? $updateCategoryIds : array($updateCategoryIds);

            foreach ($updateCategoryIds as $id)
            {
                $categories = Mage::getModel('catalog/category')->getCategories($id);

                foreach ($categories as $category)
                    $updateCategoryIds[] = $category->getId();
            }

            $this->engine
                ->rebuildCategoryIndex(null, $updateCategoryIds);
        }
    }

    /**
     * Rebuild all index data
     */
    public function reindexAll()
    {
        if (! $this->config->getApplicationID() || ! $this->config->getAPIKey() || ! $this->config->getSearchOnlyAPIKey())
        {
            Mage::getSingleton('adminhtml/session')->addError('Algolia reindexing failed: You need to configure your Algolia credentials in System > Configuration > Algolia Search.');
            return;
        }

        $this->engine->rebuildCategories();

        return $this;
    }
}
