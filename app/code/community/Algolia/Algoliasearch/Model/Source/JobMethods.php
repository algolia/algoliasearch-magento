<?php

class Algolia_Algoliasearch_Model_Source_JobMethods
{
    protected $_methods = array(
        'saveConfigurationToAlgolia' => 'Save Configuration',
        'moveIndex' => 'Move Index',
        'deleteObjects' => 'Object Deletion',
        'rebuildStoreCategoryIndex' => 'Store Category Reindex',
        'rebuildCategoryIndex' => 'Category Reindex',
        'rebuildStoreProductIndex' => 'Store Product Reindex',
        'rebuildProductIndex' => 'Product Reindex',
        'rebuildStoreAdditionalSectionsIndex' => 'Additional Section Reindex',
        'rebuildStoreSuggestionIndex' => 'Suggestion Reindex',
        'rebuildStorePageIndex' => 'Page Reindex',
    );

    /**
     * @return array
     */
    public function getMethods()
    {
        return $this->_methods;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        foreach ($this->_methods as $method => $label) {
            $option[] = array(
                'value' => $method,
                'label' => $label,
            );
        }
        return $options;
    }
}
