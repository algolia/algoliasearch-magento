<?php

class Algolia_Algoliasearch_Helper_Config extends Mage_Core_Helper_Abstract
{
    const MINIMAL_QUERY_LENGTH                 = 'algoliasearch/ui/minimal_query_length';
    const SEARCH_DELAY                         = 'algoliasearch/ui/search_delay';

    const ENABLE_FRONTEND                      = 'algoliasearch/credentials/enable_frontend';
    const ENABLE_BACKEND                       = 'algoliasearch/credentials/enable_backend';
    const IS_POPUP_ENABLED                     = 'algoliasearch/credentials/is_popup_enabled';
    const APPLICATION_ID                       = 'algoliasearch/credentials/application_id';
    const API_KEY                              = 'algoliasearch/credentials/api_key';
    const SEARCH_ONLY_API_KEY                  = 'algoliasearch/credentials/search_only_api_key';
    const INDEX_PREFIX                         = 'algoliasearch/credentials/index_prefix';
    const IS_INSTANT_ENABLED                   = 'algoliasearch/credentials/is_instant_enabled';

    const REPLACE_CATEGORIES                   = 'algoliasearch/instant/replace_categories';
    const INSTANT_SELECTOR                     = 'algoliasearch/instant/instant_selector';
    const FACETS                               = 'algoliasearch/instant/facets';
    const MAX_VALUES_PER_FACET                 = 'algoliasearch/instant/max_values_per_facet';
    const SORTING_INDICES                      = 'algoliasearch/instant/sorts';
    const XML_ADD_TO_CART_ENABLE               = 'algoliasearch/instant/add_to_cart_enable';

    const AUTOCOMPLETE_ADD_SECTIONS            = 'algoliasearch/autocomplete/additional_sections';

    const NUMBER_OF_PRODUCT_SUGGESTIONS        = 'algoliasearch/products/number_product_suggestions';
    const NUMBER_OF_PRODUCT_RESULTS            = 'algoliasearch/products/number_product_results';
    const PRODUCT_ATTRIBUTES                   = 'algoliasearch/products/product_additional_attributes';
    const PRODUCT_CUSTOM_RANKING               = 'algoliasearch/products/custom_ranking_product_attributes';
    const RESULTS_LIMIT                        = 'algoliasearch/products/results_limit';

    const NUMBER_OF_CATEGORY_SUGGESTIONS       = 'algoliasearch/categories/number_category_suggestions';
    const CATEGORY_ATTRIBUTES                  = 'algoliasearch/categories/category_additional_attributes2';
    const INDEX_PRODUCT_COUNT                  = 'algoliasearch/categories/index_product_count';
    const CATEGORY_CUSTOM_RANKING              = 'algoliasearch/categories/custom_ranking_category_attributes';

    const NUMBER_OF_PAGE_SUGGESTIONS           = 'algoliasearch/pages/number_page_suggestions';
    const EXCLUDED_PAGES                       = 'algoliasearch/pages/excluded_pages';

    const NUMBER_QUERY_SUGGESTIONS             = 'algoliasearch/suggestions/number_query_suggestions';
    const MIN_POPULARITY                       = 'algoliasearch/suggestions/min_popularity';
    const MIN_NUMBER_OF_RESULTS                = 'algoliasearch/suggestions/min_number_of_results';

    const REMOVE_IF_NO_RESULT                  = 'algoliasearch/relevance/remove_words_if_no_result';

    const MAX_RETRIES                          = 'algoliasearch/queue/retries';
    const IS_ACTIVE                            = 'algoliasearch/queue/active';
    const NUMBER_OF_ELEMENT_BY_PAGE            = 'algoliasearch/queue/number_of_element_by_page';
    const NUMBER_OF_JOB_TO_RUN                 = 'algoliasearch/queue/number_of_job_to_run';
    const NO_PROCESS                           = 'algoliasearch/queue/noprocess';

    const XML_PATH_IMAGE_WIDTH                 = 'algoliasearch/image/width';
    const XML_PATH_IMAGE_HEIGHT                = 'algoliasearch/image/height';
    const XML_PATH_IMAGE_TYPE                  = 'algoliasearch/image/type';

    const PARTIAL_UPDATES                      = 'algoliasearch/advanced/partial_update';
    const CUSTOMER_GROUPS_ENABLE               = 'algoliasearch/advanced/customer_groups_enable';
    const MAKE_SEO_REQUEST                     = 'algoliasearch/advanced/make_seo_request';
    const REMOVE_BRANDING                      = 'algoliasearch/advanced/remove_branding';

    const SHOW_OUT_OF_STOCK                    = 'cataloginventory/options/show_out_of_stock';
    const LOGGING_ENABLED                      = 'dev/log/active';

    public function isEnabledFrontEnd($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::ENABLE_BACKEND, $storeId) && Mage::getStoreConfigFlag(self::ENABLE_FRONTEND, $storeId);
    }

    public function isEnabledBackend($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::ENABLE_BACKEND, $storeId);
    }

    public function makeSeoRequest($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::MAKE_SEO_REQUEST, $storeId);
    }

    public function isLoggingEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::LOGGING_ENABLED, $storeId);
    }

    public function getShowOutOfStock($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::SHOW_OUT_OF_STOCK, $storeId);
    }

    public function noProcess($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::NO_PROCESS, $storeId);
    }

    public function getImageWidth($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_IMAGE_WIDTH, $storeId);
    }

    public function getImageHeight($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_IMAGE_HEIGHT, $storeId);
    }

    public function getImageType($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_IMAGE_TYPE, $storeId);
    }

    public function isCustomerGroupsEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::CUSTOMER_GROUPS_ENABLE, $storeId);
    }

    public function isPartialUpdateEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::PARTIAL_UPDATES, $storeId);
    }

    public function getAutocompleteAdditionnalSections($storeId = null)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::AUTOCOMPLETE_ADD_SECTIONS, $storeId));

        if (is_array($attrs))
            return array_values($attrs);

        return array();
    }

    public function getNumberOfQuerySuggestions($storeId = null)
    {
        return Mage::getStoreConfig(self::NUMBER_QUERY_SUGGESTIONS, $storeId);
    }

    public function getMinPopularity($storeId = null)
    {
        return Mage::getStoreConfig(self::MIN_POPULARITY, $storeId);
    }

    public function getMinNumberOfResults($storeId = null)
    {
        return Mage::getStoreConfig(self::MIN_NUMBER_OF_RESULTS, $storeId);
    }

    public function isAddToCartEnable($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_ADD_TO_CART_ENABLE, $storeId);
    }

    public function isRemoveBranding($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::REMOVE_BRANDING, $storeId);
    }

    public function getMaxValuesPerFacet($storeId = null)
    {
        return Mage::getStoreConfig(self::MAX_VALUES_PER_FACET, $storeId);
    }

    public function getQueueMaxRetries($storeId = null)
    {
        return Mage::getStoreConfig(self::MAX_RETRIES, $storeId);
    }

    public function getNumberOfElementByPage($storeId = null)
    {
        return Mage::getStoreConfig(self::NUMBER_OF_ELEMENT_BY_PAGE, $storeId);
    }

    public function getNumberOfJobToRun($storeId = null)
    {
        return Mage::getStoreConfig(self::NUMBER_OF_JOB_TO_RUN, $storeId);
    }

    public function isQueueActive($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::IS_ACTIVE, $storeId);
    }

    public function getRemoveWordsIfNoResult($storeId = NULL)
    {
        return Mage::getStoreConfig(self::REMOVE_IF_NO_RESULT, $storeId);
    }

    public function getNumberOfProductSuggestions($storeId = NULL)
    {
        return (int) Mage::getStoreConfig(self::NUMBER_OF_PRODUCT_SUGGESTIONS, $storeId);
    }

    public function getNumberOfProductResults($storeId = NULL)
    {
        return (int) Mage::getStoreConfig(self::NUMBER_OF_PRODUCT_RESULTS, $storeId);
    }

    public function getNumberOfCategorySuggestions($storeId = NULL)
    {
        return (int) Mage::getStoreConfig(self::NUMBER_OF_CATEGORY_SUGGESTIONS, $storeId);
    }

    public function getNumberOfPageSuggestions($storeId = NULL)
    {
        return (int) Mage::getStoreConfig(self::NUMBER_OF_PAGE_SUGGESTIONS, $storeId);
    }

    public function getResultsLimit($storeId = NULL)
    {
        return Mage::getStoreConfig(self::RESULTS_LIMIT, $storeId);
    }

    public function isPopupEnabled($storeId = NULL)
    {
        return Mage::getStoreConfigFlag(self::IS_POPUP_ENABLED, $storeId);
    }

    public function replaceCategories($storeId = NULL)
    {
        return Mage::getStoreConfigFlag(self::REPLACE_CATEGORIES, $storeId);
    }

    public function isAutoCompleteEnabled($storeId = NULL)
    {
        return Mage::getStoreConfigFlag(self::IS_POPUP_ENABLED, $storeId);
    }

    public function isInstantEnabled($storeId = NULL)
    {
        return Mage::getStoreConfigFlag(self::IS_INSTANT_ENABLED, $storeId);
    }

    public function getInstantSelector($storeId = NULL)
    {
        return Mage::getStoreConfig(self::INSTANT_SELECTOR, $storeId);
    }

    public function getExcludedPages($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::EXCLUDED_PAGES, $storeId));

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getSortingIndices($storeId = NULL)
    {
        $product_helper = Mage::helper('algoliasearch/entity_producthelper');

        $attrs = unserialize(Mage::getStoreConfig(self::SORTING_INDICES, $storeId));

        $group_id = Mage::getSingleton('customer/session')->getCustomerGroupId();

        foreach ($attrs as &$attr)
        {
            if ($this->isCustomerGroupsEnabled($storeId))
            {
                if (strpos($attr['attribute'], 'price') !== false)
                {
                    $suffix_index_name = 'group_' . $group_id;

                    $attr['index_name'] = $product_helper->getIndexName($storeId) . '_' . $attr['attribute'] . '_' .$suffix_index_name.'_'.$attr['sort'];
                }
                else
                    $attr['index_name'] = $product_helper->getIndexName($storeId). '_' .$attr['attribute'] . '_'.$attr['sort'];
            }
            else
            {
                if (strpos($attr['attribute'], 'price') !== false)
                    $attr['index_name'] = $product_helper->getIndexName($storeId). '_' .$attr['attribute'].'_' . 'default' . '_'.$attr['sort'];
                else
                    $attr['index_name'] = $product_helper->getIndexName($storeId). '_' .$attr['attribute'] . '_'.$attr['sort'];
            }
        }

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getApplicationID($storeId = NULL)
    {
        return Mage::getStoreConfig(self::APPLICATION_ID, $storeId);
    }

    public function getAPIKey($storeId = NULL)
    {
        return Mage::getStoreConfig(self::API_KEY, $storeId);
    }

    public function getSearchOnlyAPIKey($storeId = NULL)
    {
        return Mage::getStoreConfig(self::SEARCH_ONLY_API_KEY, $storeId);
    }

    public function getIndexPrefix($storeId = NULL)
    {
        return Mage::getStoreConfig(self::INDEX_PREFIX, $storeId);
    }

    public function getCategoryAdditionalAttributes($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::CATEGORY_ATTRIBUTES, $storeId));

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getProductAdditionalAttributes($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::PRODUCT_ATTRIBUTES, $storeId));

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getFacets($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::FACETS, $storeId));

        foreach ($attrs as &$attr)
            if ($attr['type'] == 'other')
                $attr['type'] = $attr['other_type'];

        if (is_array($attrs))
            return array_values($attrs);

        return array();
    }

    public function getCategoryCustomRanking($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::CATEGORY_CUSTOM_RANKING, $storeId));

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getProductCustomRanking($storeId = NULL)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::PRODUCT_CUSTOM_RANKING, $storeId));

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
