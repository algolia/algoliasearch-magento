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

    const NB_OF_PRODUCTS_SUGGESTIONS           = 'algoliasearch/autocomplete/nb_of_products_suggestions';
    const NB_OF_CATEGORIES_SUGGESTIONS         = 'algoliasearch/autocomplete/nb_of_categories_suggestions';
    const NB_OF_QUERIES_SUGGESTIONS            = 'algoliasearch/autocomplete/nb_of_queries_suggestions';
    const AUTOCOMPLETE_SECTIONS                = 'algoliasearch/autocomplete/sections';
    const EXCLUDED_PAGES                       = 'algoliasearch/autocomplete/excluded_pages';
    const MIN_POPULARITY                       = 'algoliasearch/autocomplete/min_popularity';
    const MIN_NUMBER_OF_RESULTS                = 'algoliasearch/autocomplete/min_number_of_results';

    const NUMBER_OF_PRODUCT_RESULTS            = 'algoliasearch/products/number_product_results';
    const PRODUCT_ATTRIBUTES                   = 'algoliasearch/products/product_additional_attributes';
    const PRODUCT_CUSTOM_RANKING               = 'algoliasearch/products/custom_ranking_product_attributes';
    const RESULTS_LIMIT                        = 'algoliasearch/products/results_limit';
    const SHOW_SUGGESTIONS_NO_RESULTS          = 'algoliasearch/products/show_suggestions_on_no_result_page';

    const CATEGORY_ATTRIBUTES                  = 'algoliasearch/categories/category_additional_attributes2';
    const INDEX_PRODUCT_COUNT                  = 'algoliasearch/categories/index_product_count';
    const CATEGORY_CUSTOM_RANKING              = 'algoliasearch/categories/custom_ranking_category_attributes';


    const IS_ACTIVE                            = 'algoliasearch/queue/active';
    const NUMBER_OF_ELEMENT_BY_PAGE            = 'algoliasearch/queue/number_of_element_by_page';
    const NUMBER_OF_JOB_TO_RUN                 = 'algoliasearch/queue/number_of_job_to_run';

    const XML_PATH_IMAGE_WIDTH                 = 'algoliasearch/image/width';
    const XML_PATH_IMAGE_HEIGHT                = 'algoliasearch/image/height';
    const XML_PATH_IMAGE_TYPE                  = 'algoliasearch/image/type';

    const REMOVE_IF_NO_RESULT                  = 'algoliasearch/advanced/remove_words_if_no_result';
    const PARTIAL_UPDATES                      = 'algoliasearch/advanced/partial_update';
    const CUSTOMER_GROUPS_ENABLE               = 'algoliasearch/advanced/customer_groups_enable';
    const MAKE_SEO_REQUEST                     = 'algoliasearch/advanced/make_seo_request';
    const REMOVE_BRANDING                      = 'algoliasearch/advanced/remove_branding';
    const AUTOCOMPLETE_SELECTOR                = 'algoliasearch/advanced/autocomplete_selector';

    const SHOW_OUT_OF_STOCK                    = 'cataloginventory/options/show_out_of_stock';
    const LOGGING_ENABLED                      = 'algoliasearch/credentials/debug';

    protected $_productTypeMap = array();

    public function isDefaultSelector($storeId = null)
    {
        return '.algolia-search-input' === $this->getAutocompleteSelector($storeId);
    }

    public function getAutocompleteSelector($storeId = null)
    {
        return Mage::getStoreConfig(self::AUTOCOMPLETE_SELECTOR, $storeId);
    }

    public function getNumberOfQueriesSuggestions($storeId = null)
    {
        return Mage::getStoreConfig(self::NB_OF_QUERIES_SUGGESTIONS, $storeId);
    }

    public function getNumberOfProductsSuggestions($storeId = null)
    {
        return Mage::getStoreConfig(self::NB_OF_PRODUCTS_SUGGESTIONS, $storeId);
    }

    public function getNumberOfCategoriesSuggestions($storeId = null)
    {
        return Mage::getStoreConfig(self::NB_OF_CATEGORIES_SUGGESTIONS, $storeId);
    }

    public function showSuggestionsOnNoResultsPage($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::SHOW_SUGGESTIONS_NO_RESULTS, $storeId);
    }

    public function isEnabledFrontEnd($storeId = null)
    {
        // Frontend = Backend + Frontent
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
        $imageWidth = Mage::getStoreConfig(self::XML_PATH_IMAGE_WIDTH, $storeId);
        if(empty($imageWidth)) {
            return null;
        }
        return $imageWidth;
    }

    public function getImageHeight($storeId = null)
    {
        $imageHeight = Mage::getStoreConfig(self::XML_PATH_IMAGE_HEIGHT, $storeId);
        if(empty($imageHeight)) {
            return null;
        }
        return $imageHeight;
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

    public function getAutocompleteSections($storeId = null)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::AUTOCOMPLETE_SECTIONS, $storeId));

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

    public function getNumberOfProductResults($storeId = NULL)
    {
        return (int) Mage::getStoreConfig(self::NUMBER_OF_PRODUCT_RESULTS, $storeId);
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

                    $attr['name'] = $product_helper->getIndexName($storeId) . '_' . $attr['attribute'] . '_' .$suffix_index_name.'_'.$attr['sort'];
                }
                else
                    $attr['name'] = $product_helper->getIndexName($storeId). '_' .$attr['attribute'] . '_'.$attr['sort'];
            }
            else
            {
                if (strpos($attr['attribute'], 'price') !== false)
                    $attr['name'] = $product_helper->getIndexName($storeId). '_' .$attr['attribute'].'_' . 'default' . '_'.$attr['sort'];
                else
                    $attr['name'] = $product_helper->getIndexName($storeId). '_' .$attr['attribute'] . '_'.$attr['sort'];
            }
        }

        if (is_array($attrs))
            return $attrs;

        return array();
    }

    public function getApplicationID($storeId = NULL)
    {
        return trim(Mage::getStoreConfig(self::APPLICATION_ID, $storeId));
    }

    public function getAPIKey($storeId = NULL)
    {
        return trim(Mage::getStoreConfig(self::API_KEY, $storeId));
    }

    public function getSearchOnlyAPIKey($storeId = NULL)
    {
        return trim(Mage::getStoreConfig(self::SEARCH_ONLY_API_KEY, $storeId));
    }

    public function getIndexPrefix($storeId = NULL)
    {
        return trim(Mage::getStoreConfig(self::INDEX_PREFIX, $storeId));
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

    public function getPopularQueries($storeId = null)
    {
        if ($storeId === null) {
            $storeId = Mage::app()->getStore()->getId();
        }

        $suggestion_helper = Mage::helper('algoliasearch/entity_suggestionhelper');
        $popularQueries = $suggestion_helper->getPopularQueries($storeId);

        return $popularQueries;
    }

    /**
     * Loads product type mapping from configuration (default) > algoliasearch > product_map > (product type)
     *
     * @param $originalType
     * @return string
     */
    public function getMappedProductType($originalType)
    {
        if (!isset($this->_productTypeMap[$originalType]))
        {
            $mappedType = (string)Mage::app()->getConfig()->getNode('default/algoliasearch/product_map/' . $originalType);

            if ($mappedType)
                $this->_productTypeMap[$originalType] = $mappedType;
            else
                $this->_productTypeMap[$originalType] = $originalType;
        }

        return $this->_productTypeMap[$originalType];
    }
}
