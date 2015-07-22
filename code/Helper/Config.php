<?php

class Algolia_Algoliasearch_Helper_Config extends Mage_Core_Helper_Abstract
{
    const XML_PATH_MINIMAL_QUERY_LENGTH             = 'algoliasearch/ui/minimal_query_length';
    const XML_PATH_SEARCH_DELAY                     = 'algoliasearch/ui/search_delay';

    const XML_PATH_IS_POPUP_ENABLED                 = 'algoliasearch/credentials/is_popup_enabled';
    const XML_PATH_APPLICATION_ID                   = 'algoliasearch/credentials/application_id';
    const XML_PATH_API_KEY                          = 'algoliasearch/credentials/api_key';
    const XML_PATH_SEARCH_ONLY_API_KEY              = 'algoliasearch/credentials/search_only_api_key';
    const XML_PATH_INDEX_PREFIX                     = 'algoliasearch/credentials/index_prefix';
    const XML_PATH_IS_INSTANT_ENABLED               = 'algoliasearch/credentials/is_instant_enabled';

    const XML_PATH_REPLACE_CATEGORIES               = 'algoliasearch/instant/replace_categories';
    const XML_PATH_INSTANT_SELECTOR                 = 'algoliasearch/instant/instant_selector';
    const XML_PATH_FACETS                           = 'algoliasearch/instant/facets';
    const XML_PATH_SORTING_INDICES                  = 'algoliasearch/instant/sorts';

    const XML_PATH_AUTOCOMPLETE_ADD_SECTIONS        = 'algoliasearch/autocomplete/additional_sections';

    const XML_PATH_PRODUCT_ATTRIBUTES               = 'algoliasearch/products/product_additional_attributes';
    const XML_PATH_PRODUCT_CUSTOM_RANKING           = 'algoliasearch/products/custom_ranking_product_attributes';
    const XML_PATH_RESULTS_LIMIT                    = 'algoliasearch/products/results_limit';

    const XML_PATH_CATEGORY_ATTRIBUTES              = 'algoliasearch/categories/category_additional_attributes2';
    const XML_PATH_INDEX_PRODUCT_COUNT              = 'algoliasearch/categories/index_product_count';
    const XML_PATH_CATEGORY_CUSTOM_RANKING          = 'algoliasearch/categories/custom_ranking_category_attributes';

    const XML_PATH_EXCLUDED_PAGES                   = 'algoliasearch/pages/excluded_pages';

    const XML_PATH_MIN_POPULARITY                   = 'algoliasearch/suggestions/min_popularity';
    const XML_PATH_MIN_NUMBER_OF_RESULTS            = 'algoliasearch/suggestions/min_number_of_results';

    const XML_PATH_REMOVE_IF_NO_RESULT              = 'algoliasearch/relevance/remove_words_if_no_result';

    const XML_PATH_NUMBER_OF_PRODUCT_SUGGESTIONS    = 'algoliasearch/ui/number_product_suggestions';
    const XML_PATH_NUMBER_OF_PRODUCT_RESULTS        = 'algoliasearch/ui/number_product_results';
    const XML_PATH_NUMBER_OF_CATEGORY_SUGGESTIONS   = 'algoliasearch/ui/number_category_suggestions';
    const XML_PATH_NUMBER_OF_PAGE_SUGGESTIONS       = 'algoliasearch/ui/number_page_suggestions';
    const XML_PATH_NUMBER_QUERY_SUGGESTIONS         = 'algoliasearch/ui/number_query_suggestions';
    const XML_PATH_MAX_VALUES_PER_FACET             = 'algoliasearch/ui/max_values_per_facet';
    const XML_PATH_REMOVE_BRANDING                  = 'algoliasearch/ui/remove_branding';
    const XML_ADD_TO_CART_ENABLE                    = 'algoliasearch/ui/add_to_cart_enable';

    const XML_PATH_MAX_RETRIES                      = 'algoliasearch/queue/retries';
    const XML_PATH_IS_ACTIVE                        = 'algoliasearch/queue/active';
    const XML_PATH_NUMBER_OF_ELEMENT_BY_PAGE        = 'algoliasearch/queue/number_of_element_by_page';
    const XML_PATH_NUMBER_OF_JOB_TO_RUN             = 'algoliasearch/queue/number_of_job_to_run';

    const XML_PATH_PARTIAL_UPDATES                  = 'algoliasearch/advanced/partial_update';
    const XML_PATH_CUSTOMER_GROUPS_ENABLE           = 'algoliasearch/advanced/customer_groups_enable';

    public function isCustomerGroupsEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CUSTOMER_GROUPS_ENABLE, $storeId);
    }

    public function isPartialUpdateEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_PARTIAL_UPDATES, $storeId);
    }

    public function getAutocompleteAdditionnalSections($storeId = null)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::XML_PATH_AUTOCOMPLETE_ADD_SECTIONS, $storeId));

        if (is_array($attrs))
            return array_values($attrs);

        return array();
    }

    public function getNumberOfQuerySuggestions($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_NUMBER_QUERY_SUGGESTIONS, $storeId);
    }

    public function getMinPopularity($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_MIN_POPULARITY, $storeId);
    }

    public function getMinNumberOfResults($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_MIN_NUMBER_OF_RESULTS, $storeId);
    }

    public function isAddToCartEnable($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_ADD_TO_CART_ENABLE, $storeId);
    }

    public function isRemoveBranding($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_REMOVE_BRANDING, $storeId);
    }

    public function getMaxValuesPerFacet($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_MAX_VALUES_PER_FACET, $storeId);
    }

    public function getQueueMaxRetries($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_MAX_RETRIES, $storeId);
    }

    public function getNumberOfElementByPage($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_NUMBER_OF_ELEMENT_BY_PAGE, $storeId);
    }

    public function getNumberOfJobToRun($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_NUMBER_OF_JOB_TO_RUN, $storeId);
    }

    public function isQueueActive($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_IS_ACTIVE, $storeId);
    }

    public function getRemoveWordsIfNoResult($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_REMOVE_IF_NO_RESULT, $storeId);
    }

    public function getNumberOfProductSuggestions($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_NUMBER_OF_PRODUCT_SUGGESTIONS, $storeId);
    }

    public function getNumberOfProductResults($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_NUMBER_OF_PRODUCT_RESULTS, $storeId);
    }

    public function getNumberOfCategorySuggestions($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_NUMBER_OF_CATEGORY_SUGGESTIONS, $storeId);
    }

    public function getNumberOfPageSuggestions($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_NUMBER_OF_PAGE_SUGGESTIONS, $storeId);
    }

    public function getResultsLimit($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_RESULTS_LIMIT, $storeId);
    }

    public function isPopupEnabled($storeId = NULL)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_IS_POPUP_ENABLED, $storeId);
    }

    public function replaceCategories($storeId = NULL)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_REPLACE_CATEGORIES, $storeId);
    }

    public function isAutoCompleteEnabled($storeId = NULL)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_IS_POPUP_ENABLED, $storeId);
    }

    public function isInstantEnabled($storeId = NULL)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_IS_INSTANT_ENABLED, $storeId);
    }

    public function getInstantSelector($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_INSTANT_SELECTOR, $storeId);
    }

    public function getExcludedPages($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::XML_PATH_EXCLUDED_PAGES, $storeId));

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getSortingIndices($storeId = NULL)
    {
        $product_helper = Mage::helper('algoliasearch/entity_producthelper');

        $attrs = unserialize(Mage::getStoreConfig(self::XML_PATH_SORTING_INDICES, $storeId));

        $group_id = Mage::getSingleton('customer/session')->getCustomerGroupId();

        $suffix_index_name = '';

        if ($this->isCustomerGroupsEnabled($storeId))
        {
            $suffix_index_name = '_group_' . $group_id;
        }

        foreach ($attrs as &$attr)
            $attr['index_name'] = $product_helper->getIndexName($storeId).$suffix_index_name.'_'.$attr['attribute'].'_'.$attr['sort'];

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getApplicationID($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_APPLICATION_ID, $storeId);
    }

    public function getAPIKey($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_API_KEY, $storeId);
    }

    public function getSearchOnlyAPIKey($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_SEARCH_ONLY_API_KEY, $storeId);
    }

    public function getIndexPrefix($storeId = NULL)
    {
        return Mage::getStoreConfig(self::XML_PATH_INDEX_PREFIX, $storeId);
    }

    public function getCategoryAdditionalAttributes($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::XML_PATH_CATEGORY_ATTRIBUTES, $storeId));

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getProductAdditionalAttributes($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::XML_PATH_PRODUCT_ATTRIBUTES, $storeId));

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getFacets($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::XML_PATH_FACETS, $storeId));

        foreach ($attrs as &$attr)
            if ($attr['type'] == 'other')
                $attr['type'] = $attr['other_type'];

        if (is_array($attrs))
            return array_values($attrs);

        return array();
    }

    public function getCategoryCustomRanking($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::XML_PATH_CATEGORY_CUSTOM_RANKING, $storeId));

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getProductCustomRanking($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::XML_PATH_PRODUCT_CUSTOM_RANKING, $storeId));

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getCurrency($storeId = NULL)
    {
        $currencySymbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();

        return $currencySymbol;
    }
}
