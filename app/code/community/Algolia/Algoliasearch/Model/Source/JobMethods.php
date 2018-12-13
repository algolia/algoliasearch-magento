<?php

class Algolia_Algoliasearch_Model_Source_JobMethods
{
    protected $_methods = array(
        'saveSettings' => 'Save Settings',
        'saveConfigurationToAlgolia' => 'Save Configuration',
        'moveIndex' => 'Move Index',
        'moveProductsTmpIndex' => 'Move Products Temp Index',
        'deleteObjects' => 'Object Deletion',
        'rebuildStoreCategoryIndex' => 'Store Category Reindex',
        'rebuildCategoryIndex' => 'Category Reindex',
        'rebuildStoreProductIndex' => 'Store Product Reindex',
        'rebuildProductIndex' => 'Product Reindex',
        'rebuildStoreAdditionalSectionsIndex' => 'Store Additional Section Reindex',
        'rebuildAdditionalSectionsIndex' => 'Additional Section Reindex',
        'rebuildStoreSuggestionIndex' => 'Store Suggestion Reindex',
        'rebuildSuggestionIndex' => 'Sugesstion Reindex',
        'rebuildStorePageIndex' => 'Store Page Reindex',
        'rebuildPageIndex' => 'Page Reindex',
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
