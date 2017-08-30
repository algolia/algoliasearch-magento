<?php

class Algolia_Algoliasearch_Helper_Config extends Mage_Core_Helper_Abstract
{
    const MINIMAL_QUERY_LENGTH = 'algoliasearch/ui/minimal_query_length';
    const SEARCH_DELAY = 'algoliasearch/ui/search_delay';

    const ENABLE_FRONTEND = 'algoliasearch/credentials/enable_frontend';
    const ENABLE_BACKEND = 'algoliasearch/credentials/enable_backend';
    const IS_POPUP_ENABLED = 'algoliasearch/credentials/is_popup_enabled';
    const APPLICATION_ID = 'algoliasearch/credentials/application_id';
    const API_KEY = 'algoliasearch/credentials/api_key';
    const SEARCH_ONLY_API_KEY = 'algoliasearch/credentials/search_only_api_key';
    const INDEX_PREFIX = 'algoliasearch/credentials/index_prefix';
    const IS_INSTANT_ENABLED = 'algoliasearch/credentials/is_instant_enabled';
    const USE_ADAPTIVE_IMAGE = 'algoliasearch/credentials/use_adaptive_image';

    const REPLACE_CATEGORIES = 'algoliasearch/instant/replace_categories';
    const INSTANT_SELECTOR = 'algoliasearch/instant/instant_selector';
    const FACETS = 'algoliasearch/instant/facets';
    const MAX_VALUES_PER_FACET = 'algoliasearch/instant/max_values_per_facet';
    const SORTING_INDICES = 'algoliasearch/instant/sorts';
    const XML_ADD_TO_CART_ENABLE = 'algoliasearch/instant/add_to_cart_enable';

    const NB_OF_PRODUCTS_SUGGESTIONS = 'algoliasearch/autocomplete/nb_of_products_suggestions';
    const NB_OF_CATEGORIES_SUGGESTIONS = 'algoliasearch/autocomplete/nb_of_categories_suggestions';
    const NB_OF_QUERIES_SUGGESTIONS = 'algoliasearch/autocomplete/nb_of_queries_suggestions';
    const AUTOCOMPLETE_SECTIONS = 'algoliasearch/autocomplete/sections';
    const EXCLUDED_PAGES = 'algoliasearch/autocomplete/excluded_pages';
    const MIN_POPULARITY = 'algoliasearch/autocomplete/min_popularity';
    const MIN_NUMBER_OF_RESULTS = 'algoliasearch/autocomplete/min_number_of_results';
    const DISPLAY_SUGGESTIONS_CATEGORIES = 'algoliasearch/autocomplete/display_categories_with_suggestions';
    const RENDER_TEMPLATE_DIRECTIVES = 'algoliasearch/autocomplete/render_template_directives';
    const AUTOCOMPLETE_MENU_DEBUG = 'algoliasearch/autocomplete/debug';

    const NUMBER_OF_PRODUCT_RESULTS = 'algoliasearch/products/number_product_results';
    const PRODUCT_ATTRIBUTES = 'algoliasearch/products/product_additional_attributes';
    const PRODUCT_CUSTOM_RANKING = 'algoliasearch/products/custom_ranking_product_attributes';
    const RESULTS_LIMIT = 'algoliasearch/products/results_limit';
    const SHOW_SUGGESTIONS_NO_RESULTS = 'algoliasearch/products/show_suggestions_on_no_result_page';
    const INDEX_VISIBILITY = 'algoliasearch/products/index_visibility';
    const INDEX_OUT_OF_STOCK_OPTIONS = 'algoliasearch/products/index_out_of_stock_options';
    const INDEX_WHOLE_CATEGORY_TREE = 'algoliasearch/products/index_whole_category_tree';

    const CATEGORY_ATTRIBUTES = 'algoliasearch/categories/category_additional_attributes2';
    const INDEX_PRODUCT_COUNT = 'algoliasearch/categories/index_product_count';
    const CATEGORY_CUSTOM_RANKING = 'algoliasearch/categories/custom_ranking_category_attributes';
    const SHOW_CATS_NOT_INCLUDED_IN_NAVIGATION = 'algoliasearch/categories/show_cats_not_included_in_navigation';

    const IS_ACTIVE = 'algoliasearch/queue/active';
    const NUMBER_OF_ELEMENT_BY_PAGE = 'algoliasearch/queue/number_of_element_by_page';
    const NUMBER_OF_JOB_TO_RUN = 'algoliasearch/queue/number_of_job_to_run';
    const RETRY_LIMIT = 'algoliasearch/queue/number_of_retries';
    const CHECK_PRICE_INDEX = 'algoliasearch/queue/check_price_index';
    const CHECK_STOCK_INDEX = 'algoliasearch/queue/check_stock_index';

    const XML_PATH_IMAGE_WIDTH = 'algoliasearch/image/width';
    const XML_PATH_IMAGE_HEIGHT = 'algoliasearch/image/height';
    const XML_PATH_IMAGE_TYPE = 'algoliasearch/image/type';

    const ENABLE_ANALYTICS = 'algoliasearch/analytics/enable_analytics';
    const ANALYTICS_DELAY = 'algoliasearch/analytics/delay';
    const ANALYTICS_TRIGGER_ON_UI_INTERACTION = 'algoliasearch/analytics/trigger_on_ui_interaction';
    const ANALYTICS_PUSH_INITIAL_SEARCH = 'algoliasearch/analytics/push_initial_search';

    const ENABLE_SYNONYMS = 'algoliasearch/synonyms/enable_synonyms';
    const SYNONYMS = 'algoliasearch/synonyms/synonyms';
    const ONEWAY_SYNONYMS = 'algoliasearch/synonyms/oneway_synonyms';
    const SYNONYMS_FILE = 'algoliasearch/synonyms/synonyms_file';

    const REMOVE_IF_NO_RESULT = 'algoliasearch/advanced/remove_words_if_no_result';
    const PARTIAL_UPDATES = 'algoliasearch/advanced/partial_update';
    const CUSTOMER_GROUPS_ENABLE = 'algoliasearch/advanced/customer_groups_enable';
    const MAKE_SEO_REQUEST = 'algoliasearch/advanced/make_seo_request';
    const REMOVE_BRANDING = 'algoliasearch/advanced/remove_branding';
    const AUTOCOMPLETE_SELECTOR = 'algoliasearch/advanced/autocomplete_selector';
    const INDEX_PRODUCT_ON_CATEGORY_PRODUCTS_UPDATE = 'algoliasearch/advanced/index_product_on_category_products_update';
    const INDEX_ALL_CATEGORY_PRODUCTS_ON_CATEGORY_UPDATE = 'algoliasearch/advanced/index_all_category_product_on_category_update';

    const SHOW_OUT_OF_STOCK = 'cataloginventory/options/show_out_of_stock';
    const LOGGING_ENABLED = 'algoliasearch/credentials/debug';

    const EXTRA_SETTINGS_PRODUCTS = 'algoliasearch/advanced_settings/products_extra_settings';
    const EXTRA_SETTINGS_CATEGORIES = 'algoliasearch/advanced_settings/categories_extra_settings';
    const EXTRA_SETTINGS_PAGES = 'algoliasearch/advanced_settings/pages_extra_settings';
    const EXTRA_SETTINGS_SUGGESTIONS = 'algoliasearch/advanced_settings/suggestions_extra_settings';
    const EXTRA_SETTINGS_ADDITIONAL_SECTIONS = 'algoliasearch/advanced_settings/additional_sections_extra_settings';

    protected $_productTypeMap = array();

    public function indexVisibility($storeId = null)
    {
        return Mage::getStoreConfig(self::INDEX_VISIBILITY, $storeId);
    }

    public function indexOutOfStockOptions($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::INDEX_OUT_OF_STOCK_OPTIONS, $storeId);
    }

    public function indexWholeCategoryTree($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::INDEX_WHOLE_CATEGORY_TREE, $storeId);
    }

    public function showCatsNotIncludedInNavigation($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::SHOW_CATS_NOT_INCLUDED_IN_NAVIGATION, $storeId);
    }

    public function isDefaultSelector($storeId = null)
    {
        return '.algolia-search-input' === $this->getAutocompleteSelector($storeId);
    }

    public function getAutocompleteSelector($storeId = null)
    {
        return Mage::getStoreConfig(self::AUTOCOMPLETE_SELECTOR, $storeId);
    }

    public function indexProductOnCategoryProductsUpdate($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::INDEX_PRODUCT_ON_CATEGORY_PRODUCTS_UPDATE, $storeId);
    }

    public function indexAllCategoryProductsOnCategoryUpdate($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::INDEX_ALL_CATEGORY_PRODUCTS_ON_CATEGORY_UPDATE, $storeId);
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

    public function displaySuggestionsCategories($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::DISPLAY_SUGGESTIONS_CATEGORIES, $storeId);
    }

    public function isEnabledFrontEnd($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::ENABLE_FRONTEND, $storeId);
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

    public function getImageWidth($storeId = null)
    {
        $imageWidth = Mage::getStoreConfig(self::XML_PATH_IMAGE_WIDTH, $storeId);
        if (empty($imageWidth)) {
            return;
        }

        return $imageWidth;
    }

    public function getImageHeight($storeId = null)
    {
        $imageHeight = Mage::getStoreConfig(self::XML_PATH_IMAGE_HEIGHT, $storeId);
        if (empty($imageHeight)) {
            return;
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

        if (is_array($attrs)) {
            return array_values($attrs);
        }

        return array();
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

    public function shouldCheckPriceIndex($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::CHECK_PRICE_INDEX, $storeId);
    }

    public function shouldCheckStockIndex($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::CHECK_STOCK_INDEX, $storeId);
    }

    public function getRetryLimit($storeId = null)
    {
        return (int) Mage::getStoreConfig(self::RETRY_LIMIT, $storeId);
    }

    public function getRemoveWordsIfNoResult($storeId = null)
    {
        return Mage::getStoreConfig(self::REMOVE_IF_NO_RESULT, $storeId);
    }

    public function getNumberOfProductResults($storeId = null)
    {
        return (int) Mage::getStoreConfig(self::NUMBER_OF_PRODUCT_RESULTS, $storeId);
    }

    public function getResultsLimit($storeId = null)
    {
        return Mage::getStoreConfig(self::RESULTS_LIMIT, $storeId);
    }

    public function isPopupEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::IS_POPUP_ENABLED, $storeId);
    }

    public function replaceCategories($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::REPLACE_CATEGORIES, $storeId);
    }

    public function isAutoCompleteEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::IS_POPUP_ENABLED, $storeId);
    }

    public function isInstantEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::IS_INSTANT_ENABLED, $storeId);
    }

    public function useAdaptiveImage($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::USE_ADAPTIVE_IMAGE, $storeId);
    }

    public function getInstantSelector($storeId = null)
    {
        return Mage::getStoreConfig(self::INSTANT_SELECTOR, $storeId);
    }

    public function getExcludedPages($storeId = null)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::EXCLUDED_PAGES, $storeId));

        if (is_array($attrs)) {
            return $attrs;
        }

        return array();
    }

    public function getRenderTemplateDirectives($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::RENDER_TEMPLATE_DIRECTIVES, $storeId);
    }

    public function isAutocompleteDebugEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::AUTOCOMPLETE_MENU_DEBUG, $storeId);
    }

    public function getSortingIndices($storeId = null)
    {
        /** @var Algolia_Algoliasearch_Helper_Entity_Producthelper $product_helper */
        $product_helper = Mage::helper('algoliasearch/entity_producthelper');

        $attrs = unserialize(Mage::getStoreConfig(self::SORTING_INDICES, $storeId));

        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');
        $group_id = $customerSession->getCustomerGroupId();

        foreach ($attrs as &$attr) {
            if ($this->isCustomerGroupsEnabled($storeId)) {
                if (strpos($attr['attribute'], 'price') !== false) {
                    $suffix_index_name = 'group_'.$group_id;

                    $attr['name'] = $product_helper->getIndexName($storeId).'_'.$attr['attribute'].'_'.$suffix_index_name.'_'.$attr['sort'];
                } else {
                    $attr['name'] = $product_helper->getIndexName($storeId).'_'.$attr['attribute'].'_'.$attr['sort'];
                }
            } else {
                if (strpos($attr['attribute'], 'price') !== false) {
                    $attr['name'] = $product_helper->getIndexName($storeId).'_'.$attr['attribute'].'_'.'default'.'_'.$attr['sort'];
                } else {
                    $attr['name'] = $product_helper->getIndexName($storeId).'_'.$attr['attribute'].'_'.$attr['sort'];
                }
            }
        }

        if (is_array($attrs)) {
            return $attrs;
        }

        return array();
    }

    public function getApplicationID($storeId = null)
    {
        return trim(Mage::getStoreConfig(self::APPLICATION_ID, $storeId));
    }

    public function getAPIKey($storeId = null)
    {
        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');

        $encrypted = trim(Mage::getStoreConfig(self::API_KEY, $storeId));

        return $coreHelper->decrypt($encrypted);
    }

    public function getSearchOnlyAPIKey($storeId = null)
    {
        return trim(Mage::getStoreConfig(self::SEARCH_ONLY_API_KEY, $storeId));
    }

    public function getIndexPrefix($storeId = null)
    {
        return trim(Mage::getStoreConfig(self::INDEX_PREFIX, $storeId));
    }

    public function getAttributesToRetrieve($group_id)
    {
        if (false === $this->isCustomerGroupsEnabled()) {
            return array();
        }

        $attributes = array();
        foreach ($this->getProductAdditionalAttributes() as $attribute) {
            if ($attribute['attribute'] !== 'price' && $attribute['retrievable'] === '1') {
                $attributes[] = $attribute['attribute'];
            }
        }

        foreach ($this->getCategoryAdditionalAttributes() as $attribute) {
            if ($attribute['retrievable'] === '1') {
                $attributes[] = $attribute['attribute'];
            }
        }

        $attributes = array_merge($attributes, array(
            'objectID',
            'name',
            'url',
            'visibility_search',
            'visibility_catalog',
            'categories',
            'categories_without_path',
            'thumbnail_url',
            'image_url',
            'in_stock',
            'type_id',
        ));

        /** @var Mage_Directory_Model_Currency $currencyDirectory */
        $currencyDirectory = Mage::getModel('directory/currency');
        $currencies = $currencyDirectory->getConfigAllowCurrencies();

        foreach ($currencies as $currency) {
            $attributes[] = 'price.'.$currency.'.default';
            $attributes[] = 'price.'.$currency.'.default_formated';
            $attributes[] = 'price.'.$currency.'.group_'.$group_id;
            $attributes[] = 'price.'.$currency.'.group_'.$group_id.'_formated';
            $attributes[] = 'price.'.$currency.'.special_from_date';
            $attributes[] = 'price.'.$currency.'.special_to_date';
        }

        $attributes = array_unique($attributes);

        return array_values($attributes);
    }

    public function getCategoryAdditionalAttributes($storeId = null)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::CATEGORY_ATTRIBUTES, $storeId));

        if (is_array($attrs)) {
            return $attrs;
        }

        return array();
    }

    public function getProductAdditionalAttributes($storeId = null)
    {
        $attributes = unserialize(Mage::getStoreConfig(self::PRODUCT_ATTRIBUTES, $storeId));

        $facets = unserialize(Mage::getStoreConfig(self::FACETS, $storeId));
        $attributes = $this->addIndexableAttributes($attributes, $facets, '0');

        $sorts = unserialize(Mage::getStoreConfig(self::SORTING_INDICES, $storeId));
        $attributes = $this->addIndexableAttributes($attributes, $sorts, '0');

        $customRankings = unserialize(Mage::getStoreConfig(self::PRODUCT_CUSTOM_RANKING, $storeId));
        $customRankings = array_filter($customRankings, function ($customRanking) {
            return $customRanking['attribute'] != 'custom_attribute';
        });
        $attributes = $this->addIndexableAttributes($attributes, $customRankings, '0', '0');

        if (is_array($attributes)) {
            return $attributes;
        }

        return array();
    }

    public function getFacets($storeId = null)
    {
        $attrs = unserialize(Mage::getStoreConfig(self::FACETS, $storeId));
        foreach ($attrs as &$attr) {
            if ($attr['type'] == 'other') {
                $attr['type'] = $attr['other_type'];
            }
        }

        if (is_array($attrs)) {
            return array_values($attrs);
        }

        return array();
    }

    public function getCategoryCustomRanking($storeId = null)
    {
        return $this->getCustomRanking(self::CATEGORY_CUSTOM_RANKING, $storeId);
    }

    public function getProductCustomRanking($storeId = null)
    {
        return $this->getCustomRanking(self::PRODUCT_CUSTOM_RANKING, $storeId);
    }

    public function getCurrency($storeId = null)
    {
        $currencySymbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())
                              ->getSymbol();

        return $currencySymbol;
    }

    public function getPopularQueries($storeId = null)
    {
        if (!$this->isInstantEnabled($storeId) || !$this->showSuggestionsOnNoResultsPage($storeId)) {
            return array();
        }

        if ($storeId === null) {
            $storeId = Mage::app()->getStore()->getId();
        }

        /** @var Algolia_Algoliasearch_Helper_Entity_Suggestionhelper $suggestionHelper */
        $suggestionHelper = Mage::helper('algoliasearch/entity_suggestionhelper');
        $popularQueries = $suggestionHelper->getPopularQueries($storeId);

        return $popularQueries;
    }

    /**
     * Loads product type mapping from configuration (default) > algoliasearch > product_map > (product type).
     *
     * @param $originalType
     *
     * @return string
     */
    public function getMappedProductType($originalType)
    {
        if (!isset($this->_productTypeMap[$originalType])) {
            $mappedType = (string) Mage::app()->getConfig()->getNode('default/algoliasearch/product_map/'.$originalType);

            if ($mappedType) {
                $this->_productTypeMap[$originalType] = $mappedType;
            } else {
                $this->_productTypeMap[$originalType] = $originalType;
            }
        }

        return $this->_productTypeMap[$originalType];
    }

    public function getExtensionVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Algolia_Algoliasearch->version;
    }

    public function isEnabledAnalytics($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::ENABLE_ANALYTICS, $storeId);
    }

    public function getAnalyticsDelay($storeId = null)
    {
        return (int) Mage::getStoreConfig(self::ANALYTICS_DELAY, $storeId);
    }

    public function getTriggerOnUIInteraction($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::ANALYTICS_TRIGGER_ON_UI_INTERACTION, $storeId);
    }

    public function getPushInitialSearch($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::ANALYTICS_PUSH_INITIAL_SEARCH, $storeId);
    }

    public function isEnabledSynonyms($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::ENABLE_SYNONYMS, $storeId);
    }

    public function getSynonyms($storeId = null)
    {
        $synonyms = unserialize(Mage::getStoreConfig(self::SYNONYMS, $storeId));

        if (is_array($synonyms)) {
            return $synonyms;
        }

        return array();
    }

    public function getOnewaySynonyms($storeId = null)
    {
        $onewaySynonyms = unserialize(Mage::getStoreConfig(self::ONEWAY_SYNONYMS, $storeId));

        if (is_array($onewaySynonyms)) {
            return $onewaySynonyms;
        }

        return array();
    }

    public function getSynonymsFile($storeId = null)
    {
        $filename = Mage::getStoreConfig(self::SYNONYMS_FILE, $storeId);
        if (!$filename) {
            return;
        }

        return Mage::getBaseDir('media').'/algoliasearch-admin-config-uploads/'.$filename;
    }

    public function getExtraSettings($section, $storeId = null)
    {
        $constant = 'EXTRA_SETTINGS_'.mb_strtoupper($section);

        return trim(Mage::getStoreConfig(constant('self::'.$constant), $storeId));
    }

    private function getCustomRanking($configName, $storeId = null)
    {
        $attrs = unserialize(Mage::getStoreConfig($configName, $storeId));

        if (is_array($attrs)) {
            foreach ($attrs as $index => $attr) {
                if ($attr['attribute'] == 'custom_attribute') {
                    $attrs[$index]['attribute'] = $attr['custom_attribute'];
                }
            }

            return $attrs;
        }

        return array();
    }

    private function addIndexableAttributes($attributes, $addedAttributes, $searchable = '1', $retrievable = '1', $indexNoValue = '1')
    {
        foreach ((array) $addedAttributes as $addedAttribute) {
            foreach ((array) $attributes as $attribute) {
                if ($addedAttribute['attribute'] == $attribute['attribute']) {
                    continue 2;
                }
            }

            $attributes[] = array(
                'attribute'         => $addedAttribute['attribute'],
                'searchable'        => $searchable,
                'retrievable'       => $retrievable,
                'index_no_value'    => $indexNoValue,
            );
        }

        return $attributes;
    }
}
