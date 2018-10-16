<?php

class Algolia_Algoliasearch_Model_Job extends Mage_Core_Model_Abstract
{
    const CACHE_TAG = 'algoliasearch_queue_job';
    protected $_cacheTag = 'algoliasearch_queue_job';
    protected $_eventPrefix = 'algoliasearch_queue_job';
    protected $_eventObject = 'queue_job';

    /**
     * Initialize resources
     */
    protected function _construct()
    {
        $this->_init('algoliasearch/job');
    }

    /**
     * @return array
     */
    public function getMethodOptionArray()
    {
        return array(
            'saveConfigurationToAlgolia' => 'Save Configuration',
            'moveIndex' => 'Move Index',
            'deleteObjects' => 'Object deletion',
            'rebuildStoreCategoryIndex' => 'Store Category Reindex',
            'rebuildCategoryIndex' => 'Category Reindex',
            'rebuildStoreProductIndex' => 'Store Product Reindex',
            'rebuildProductIndex' => 'Product Reindex',
            'rebuildStoreAdditionalSectionsIndex' => 'Additional Section Reindex',
            'rebuildStoreSuggestionIndex' => 'Suggestion Reindex',
            'rebuildStorePageIndex' => 'Page Reindex',
        );
    }
}