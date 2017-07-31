<?php

class Algolia_Algoliasearch_Helper_Entity_Producthelper extends Algolia_Algoliasearch_Helper_Entity_Helper
{
    protected static $_productAttributes;
    protected static $_currencies;

    protected static $_predefinedProductAttributes = array(
        'name',
        'url_key',
        'description',
        'image',
        'small_image',
        'thumbnail',
        'msrp_enabled', // NEEDED to handle msrp behavior
        'tax_class_id', // Needed for tax calculation
        'price_type', // Needed for bundle prices
    );

    private $excludedAttrsFromBundledProducts = array(
        'news_from_date',
        'news_to_date',
        'special_price',
        'special_from_date',
        'special_to_date',
    );

    private $noAttributes = array();

    protected function getIndexNameSuffix()
    {
        return '_products';
    }

    public function getAllAttributes($add_empty_row = false)
    {
        if (is_null(self::$_productAttributes)) {
            self::$_productAttributes = array();

            /** @var $config Mage_Eav_Model_Config */
            $config = Mage::getSingleton('eav/config');

            $allAttributes = $config->getEntityAttributeCodes('catalog_product');

            $productAttributes = array_merge(array(
                'name',
                'path',
                'categories',
                'categories.level0',
                'categories.level1',
                'categories.level2',
                'categories.level3',
                'categories.level4',
                'categories_without_path',
                'main_categories',
                'main_categories.level0',
                'main_categories.level1',
                'main_categories.level2',
                'main_categories.level3',
                'main_categories.level4',
                'description',
                'ordered_qty',
                'total_ordered',
                'stock_qty',
                'rating_summary',
                'media_gallery',
                'in_stock',
            ), $allAttributes);

            $excludedAttributes = $this->getExcludedAttributes();

            $productAttributes = array_diff($productAttributes, $excludedAttributes);

            foreach ($productAttributes as $attributeCode) {
                self::$_productAttributes[$attributeCode] = $config->getAttribute('catalog_product', $attributeCode)
                                                                   ->getFrontendLabel();
            }
        }

        $attributes = self::$_productAttributes;

        if ($add_empty_row === true) {
            $attributes[''] = '';
        }

        uksort($attributes, function ($a, $b) {
            return strcmp($a, $b);
        });

        return $attributes;
    }

    protected function getExcludedAttributes()
    {
        return array(
            'all_children',
            'available_sort_by',
            'children',
            'children_count',
            'custom_apply_to_products',
            'custom_design',
            'custom_design_from',
            'custom_design_to',
            'custom_layout_update',
            'custom_use_parent_settings',
            'default_sort_by',
            'display_mode',
            'filter_price_range',
            'global_position',
            'image',
            'include_in_menu',
            'is_active',
            'is_always_include_in_menu',
            'is_anchor',
            'landing_page',
            'level',
            'lower_cms_block',
            'page_layout',
            'path_in_store',
            'position',
            'small_image',
            'thumbnail',
            'url_key',
            'url_path',
            'visible_in_menu',
        );
    }

    public function isAttributeEnabled($additionalAttributes, $attr_name)
    {
        foreach ($additionalAttributes as $attr) {
            $additionalAttribute = $attr['attribute'];

            $dotPosition = strpos($attr['attribute'], '.');
            if ($dotPosition !== false) {
                $additionalAttribute = substr($attr['attribute'], 0, $dotPosition);
            }

            if ($additionalAttribute === $attr_name) {
                return true;
            }
        }

        return false;
    }

    public function getProductCollectionQuery($storeId, $productIds = null, $only_visible = true, $withoutData = false)
    {
        /** @var $products Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $products = Mage::getResourceModel('catalog/product_collection');

        $products = $products->setStoreId($storeId);
        $products = $products->addStoreFilter($storeId);

        if ($productIds && count($productIds) > 0) {
            $products = $products->addAttributeToFilter('entity_id', array('in' => $productIds));
        }

        if ($only_visible) {
            $visibilityAttributeValues = $this->getVisibilityAttributeValues($storeId);

            $products = $products->addAttributeToFilter('visibility', array('in' => $visibilityAttributeValues));
            $products = $products->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        }

        if ($withoutData === false) {
            if (false === $this->config->getShowOutOfStock($storeId) && $only_visible == true) {
                /** @var Mage_CatalogInventory_Model_Stock $catalogInventory_stock */
                $catalogInventory_stock = Mage::getSingleton('cataloginventory/stock');
                $catalogInventory_stock->addInStockFilterToCollection($products);
            }

            $products = $products
                ->addAttributeToSelect('special_from_date')
                ->addAttributeToSelect('special_to_date')
                ->addAttributeToSelect('visibility')
                ->addAttributeToSelect('status');

            $additionalAttr = $this->config->getProductAdditionalAttributes($storeId);

            /* Map instead of foreach because otherwise it adds quotes to the last attribute  **/
            $additionalAttr = array_map(function ($attr) {
                return $attr['attribute'];
            }, $additionalAttr);

            $products = $products->addAttributeToSelect(array_values(array_merge(static::$_predefinedProductAttributes,
                $additionalAttr)));

            $products->addFinalPrice();

            if ($only_visible === false) {
                $fromPart = $products->getSelect()->getPart(Varien_Db_Select::FROM);
                $fromPart['price_index']['joinType'] = 'left join';
                $products->getSelect()->setPart(Varien_Db_Select::FROM, $fromPart);
            }
        }

        Mage::dispatchEvent('algolia_rebuild_store_product_index_collection_load_before', array('store' => $storeId, 'collection' => $products)); // Only for backward compatibility
        Mage::dispatchEvent('algolia_after_products_collection_build', array('store' => $storeId, 'collection' => $products));

        return $products;
    }

    public function setSettings($storeId, $saveToTmpIndicesToo = false)
    {
        $searchableAttributes = array();
        $unretrievableAttributes = array();
        $attributesForFaceting = array();

        foreach ($this->config->getProductAdditionalAttributes($storeId) as $attribute) {
            if ($attribute['searchable'] == '1') {
                if ($attribute['order'] == 'ordered') {
                    $searchableAttributes[] = $attribute['attribute'];
                } else {
                    $searchableAttributes[] = 'unordered('.$attribute['attribute'].')';
                }
            }

            if ($attribute['retrievable'] != '1') {
                $unretrievableAttributes[] = $attribute['attribute'];
            }

            if ($attribute['attribute'] == 'categories' && $attribute['searchable'] == '1') {
                $searchableAttributes[] = $attribute['order'] == 'ordered' ? 'categories_without_path' : 'unordered(categories_without_path)';
            }
        }

        $customRankings = $this->config->getProductCustomRanking($storeId);

        $customRankingAttributes = array();

        $facets = $this->config->getFacets($storeId);

        /** @var Mage_Directory_Model_Currency $directoryCurrency */
        $directoryCurrency = Mage::getModel('directory/currency');
        $currencies = $directoryCurrency->getConfigAllowCurrencies();

        foreach ($facets as $facet) {
            if ($facet['attribute'] === 'price') {
                foreach ($currencies as $currency_code) {
                    $facet['attribute'] = 'price.'.$currency_code.'.default';

                    if ($this->config->isCustomerGroupsEnabled($storeId)) {
                        /** @var Mage_Customer_Model_Group $group */
                        foreach ($groups = Mage::getModel('customer/group')->getCollection() as $group) {
                            $group_id = (int) $group->getData('customer_group_id');

                            $attributesForFaceting[] = 'price.'.$currency_code.'.group_'.$group_id;
                        }
                    }

                    $attributesForFaceting[] = $facet['attribute'];
                }
            } else {
                $attributesForFaceting[] = $facet['attribute'];
            }
        }

        foreach ($customRankings as $ranking) {
            $customRankingAttributes[] = $ranking['order'].'('.$ranking['attribute'].')';
        }

        if ($this->config->replaceCategories($storeId) && !in_array('categories', $attributesForFaceting, true)) {
            $attributesForFaceting[] = 'categories';
        }

        $indexSettings = array(
            'searchableAttributes'    => array_values(array_unique($searchableAttributes)),
            'customRanking'           => $customRankingAttributes,
            'unretrievableAttributes' => $unretrievableAttributes,
            'attributesForFaceting'   => $attributesForFaceting,
            'maxValuesPerFacet'       => (int) $this->config->getMaxValuesPerFacet($storeId),
            'removeWordsIfNoResults'  => $this->config->getRemoveWordsIfNoResult($storeId),
        );

        // Additional index settings from event observer
        $transport = new Varien_Object($indexSettings);
        Mage::dispatchEvent('algolia_index_settings_prepare', array('store_id' => $storeId, 'index_settings' => $transport)); // Only for backward compatibility
        Mage::dispatchEvent('algolia_products_index_before_set_settings', array('store_id' => $storeId, 'index_settings' => $transport));
        $indexSettings = $transport->getData();

        $indexName = $this->getIndexName($storeId);

        $mergeSettings = $this->algolia_helper->mergeSettings($indexName, $indexSettings);

        $this->algolia_helper->setSettings($indexName, $mergeSettings);
        if ($saveToTmpIndicesToo === true) {
            $this->algolia_helper->setSettings($indexName.'_tmp', $mergeSettings);
        }

        /*
         * Handle replicas
         */
        $sorting_indices = $this->config->getSortingIndices($storeId);

        if (count($sorting_indices) > 0) {
            $replicas = array();

            foreach ($sorting_indices as $values) {
                if ($this->config->isCustomerGroupsEnabled($storeId) && $values['attribute'] === 'price') {
                    foreach ($groups = Mage::getModel('customer/group')->getCollection() as $group) {
                        $group_id = (int) $group->getData('customer_group_id');

                        $suffix_index_name = 'group_'.$group_id;

                        $replicas[] = $this->getIndexName($storeId).'_'.$values['attribute'].'_'.$suffix_index_name.'_'.$values['sort'];
                    }
                } else {
                    if ($values['attribute'] === 'price') {
                        $replicas[] = $this->getIndexName($storeId).'_'.$values['attribute'].'_default_'.$values['sort'];
                    } else {
                        $replicas[] = $this->getIndexName($storeId).'_'.$values['attribute'].'_'.$values['sort'];
                    }
                }
            }

            $this->algolia_helper->setSettings($this->getIndexName($storeId), array('replicas' => $replicas));

            /** @var Mage_Core_Model_Store $store */
            $store = Mage::getModel('core/store')->load($storeId);
            $baseCurrencyCode = $store->getBaseCurrencyCode();

            foreach ($sorting_indices as $values) {
                if ($this->config->isCustomerGroupsEnabled($storeId) && $values['attribute'] === 'price') {
                    foreach ($groups = Mage::getModel('customer/group')->getCollection() as $group) {
                        $group_id = (int) $group->getData('customer_group_id');

                        $suffix_index_name = 'group_'.$group_id;

                        $sort_attribute = $values['attribute'] === 'price' ? $values['attribute'].'.'.$baseCurrencyCode.'.'.$suffix_index_name : $values['attribute'];

                        $mergeSettings['ranking'] = array(
                            $values['sort'].'('.$sort_attribute.')',
                            'typo',
                            'geo',
                            'words',
                            'proximity',
                            'attribute',
                            'exact',
                            'custom',
                        );

                        $this->algolia_helper->setSettings($this->getIndexName($storeId).'_'.$values['attribute'].'_'.$suffix_index_name.'_'.$values['sort'],
                            $mergeSettings);
                    }
                } else {
                    $sort_attribute = $values['attribute'] === 'price' ? $values['attribute'].'.'.$baseCurrencyCode.'.'.'default' : $values['attribute'];

                    $mergeSettings['ranking'] = array(
                        $values['sort'].'('.$sort_attribute.')',
                        'typo',
                        'geo',
                        'words',
                        'proximity',
                        'attribute',
                        'exact',
                        'custom',
                    );

                    if ($values['attribute'] === 'price') {
                        $this->algolia_helper->setSettings($this->getIndexName($storeId).'_'.$values['attribute'].'_default_'.$values['sort'],
                            $mergeSettings);
                    } else {
                        $this->algolia_helper->setSettings($this->getIndexName($storeId).'_'.$values['attribute'].'_'.$values['sort'],
                            $mergeSettings);
                    }
                }
            }
        }

        if ($this->config->isEnabledSynonyms($storeId) === true) {
            if ($synonymsFile = $this->config->getSynonymsFile($storeId)) {
                $synonymsToSet = json_decode(file_get_contents($synonymsFile));
            } else {
                $synonymsToSet = array();

                $synonyms = $this->config->getSynonyms($storeId);
                foreach ($synonyms as $objectID => $synonym) {
                    if (!trim($synonym['synonyms'])) {
                        continue;
                    }

                    $synonymsToSet[] = array(
                        'objectID' => $objectID,
                        'type' => 'synonym',
                        'synonyms' => $this->explodeSynomyms($synonym['synonyms']),
                    );
                }

                $onewaySynonyms = $this->config->getOnewaySynonyms($storeId);
                foreach ($onewaySynonyms as $objectID => $onewaySynonym) {
                    if (!trim($onewaySynonym['input']) || !trim($onewaySynonym['synonyms'])) {
                        continue;
                    }

                    $synonymsToSet[] = array(
                        'objectID' => $objectID,
                        'type' => 'oneWaySynonym',
                        'input' => $onewaySynonym['input'],
                        'synonyms' => $this->explodeSynomyms($onewaySynonym['synonyms']),
                    );
                }
            }

            $this->algolia_helper->setSynonyms($this->getIndexName($storeId, $saveToTmpIndicesToo), $synonymsToSet);
        } elseif ($saveToTmpIndicesToo === true) {
            $this->algolia_helper->copySynonyms($this->getIndexName($storeId), $this->getIndexName($storeId, $saveToTmpIndicesToo));
        }
    }

    protected function getFields($store)
    {
        /** @var Mage_Tax_Helper_Data $tax_helper */
        $tax_helper = Mage::helper('tax');

        if ($tax_helper->getPriceDisplayType($store) == Mage_Tax_Model_Config::DISPLAY_TYPE_EXCLUDING_TAX) {
            return array('price' => false);
        }

        if ($tax_helper->getPriceDisplayType($store) == Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX) {
            return array('price' => true);
        }

        return array('price' => false, 'price_with_tax' => true);
    }

    protected function formatPrice($price, $includeContainer, $currency_code)
    {
        /** @var Mage_Directory_Model_Currency $directoryCurrency */
        $directoryCurrency = Mage::getModel('directory/currency');

        if (!isset(static::$_currencies[$currency_code])) {
            static::$_currencies[$currency_code] = $directoryCurrency->load($currency_code);
        }

        /** @var Mage_Directory_Model_Currency $currency */
        $currency = static::$_currencies[$currency_code];

        if ($currency) {
            return $currency->format($price, array(), $includeContainer);
        }

        return $price;
    }

    protected function handlePrice(Mage_Catalog_Model_Product &$product, $sub_products, &$customData)
    {
        $fields = $this->getFields($product->getStore());
        $customer_groups_enabled = $this->config->isCustomerGroupsEnabled($product->getStoreId());
        $store = $product->getStore();
        $type = $this->config->getMappedProductType($product->getTypeId());

        /** @var Mage_Directory_Model_Currency $directoryCurrency */
        $directoryCurrency = Mage::getModel('directory/currency');
        $currencies = $directoryCurrency->getConfigAllowCurrencies();

        if (Mage::helper('core')->isModuleEnabled('Mage_Weee') &&
            Mage::helper('weee')->getPriceDisplayType($product->getStore()) == 0) {
            $weeeTaxAmount = Mage::helper('weee')->getAmountForDisplay($product);
        } else {
            $weeeTaxAmount = 0;
        }

        $baseCurrencyCode = $store->getBaseCurrencyCode();

        $groups = array();

        if ($customer_groups_enabled) {
            $groups = Mage::getModel('customer/group')->getCollection();
        }

        /** @var Mage_Tax_Helper_Data $taxHelper */
        $taxHelper = Mage::helper('tax');

        /** @var Mage_Directory_Helper_Data $directoryHelper */
        $directoryHelper = Mage::helper('directory');

        foreach ($fields as $field => $with_tax) {
            $customData[$field] = array();

            foreach ($currencies as $currency_code) {
                $customData[$field][$currency_code] = array();

                $price = (double) $taxHelper->getPrice($product, $product->getPrice(), $with_tax, null, null, null, $product->getStore(), null);
                $price = $directoryHelper->currencyConvert($price, $baseCurrencyCode, $currency_code);
                $price += $weeeTaxAmount;

                $customData[$field][$currency_code]['default'] = $price;
                $customData[$field][$currency_code]['default_formated'] = $this->formatPrice($price, false, $currency_code);

                $special_price = (double) $taxHelper->getPrice($product, $product->getFinalPrice(), $with_tax, null, null, null, $product->getStore(), null);
                $special_price = $directoryHelper->currencyConvert($special_price, $baseCurrencyCode, $currency_code);
                $special_price += $weeeTaxAmount;

                if ($customer_groups_enabled) {
                    // If fetch special price for groups

                    foreach ($groups as $group) {
                        $group_id = (int) $group->getData('customer_group_id');
                        $product->setCustomerGroupId($group_id);

                        $discounted_price = $product->getPriceModel()->getFinalPrice(1, $product);
                        $discounted_price = $directoryHelper->currencyConvert($discounted_price, $baseCurrencyCode, $currency_code);
                        $discounted_price += $weeeTaxAmount;

                        if ($discounted_price !== false) {
                            $customData[$field][$currency_code]['group_'.$group_id] = (double) $taxHelper->getPrice($product,
                                                                                                      $discounted_price,
                                                                                                      $with_tax, null,
                                                                                                      null, null,
                                                                                                      $product->getStore(),
                                                                                                      null);
                            $customData[$field][$currency_code]['group_'.$group_id] = $directoryHelper->currencyConvert($customData[$field][$currency_code]['group_'.$group_id],
                                                                                              $baseCurrencyCode,
                                                                                              $currency_code);
                            $customData[$field][$currency_code]['group_'.$group_id.'_formated'] = $store->formatPrice($customData[$field][$currency_code]['group_'.$group_id],
                                false, $currency_code);
                        } else {
                            $customData[$field][$currency_code]['group_'.$group_id] = $customData[$field][$currency_code]['default'];
                            $customData[$field][$currency_code]['group_'.$group_id.'_formated'] = $customData[$field][$currency_code]['default_formated'];
                        }
                    }

                    $product->setCustomerGroupId(null);
                }

                $customData[$field][$currency_code]['special_from_date'] = strtotime($product->getSpecialFromDate());
                $customData[$field][$currency_code]['special_to_date'] = strtotime($product->getSpecialToDate());

                if ($customer_groups_enabled) {
                    foreach ($groups as $group) {
                        $group_id = (int) $group->getData('customer_group_id');

                        if ($special_price && $special_price < $customData[$field][$currency_code]['group_'.$group_id]) {
                            $customData[$field][$currency_code]['group_'.$group_id.'_original_formated'] = $customData[$field][$currency_code]['default_formated'];

                            $customData[$field][$currency_code]['group_'.$group_id] = $special_price;
                            $customData[$field][$currency_code]['group_'.$group_id.'_formated'] = $this->formatPrice($special_price,
                                false, $currency_code);
                        }
                    }
                } else {
                    if ($special_price && $special_price < $customData[$field][$currency_code]['default']) {
                        $customData[$field][$currency_code]['default_original_formated'] = $customData[$field][$currency_code]['default_formated'];

                        $customData[$field][$currency_code]['default'] = $special_price;
                        $customData[$field][$currency_code]['default_formated'] = $this->formatPrice($special_price,
                            false, $currency_code);
                    }
                }

                if ($type == 'grouped' || $type == 'bundle') {
                    $min = PHP_INT_MAX;
                    $max = 0;

                    if ($type == 'bundle') {
                        $_priceModel = $product->getPriceModel();

                        list($min, $max) = $_priceModel->getTotalPrices($product, null, $with_tax, true);
                        $min = (double) $min;
                        $max = (double) $max;
                    }

                    if ($type == 'grouped') {
                        if (count($sub_products) > 0) {
                            foreach ($sub_products as $sub_product) {
                                $price = (double) $taxHelper->getPrice($product, $sub_product->getFinalPrice(), $with_tax,
                                                         null, null, null, $product->getStore(), null);

                                $min = min($min, $price);
                                $max = max($max, $price);
                            }
                        } else {
                            $min = $max;
                        } // avoid to have PHP_INT_MAX in case of no subproducts (Corner case of visibility and stock options)
                    }

                    if ($min != $max) {
                        $min = $directoryHelper->currencyConvert($min, $baseCurrencyCode, $currency_code);
                        $max = $directoryHelper->currencyConvert($max, $baseCurrencyCode, $currency_code);

                        $dashed_format = $this->formatPrice($min, false, $currency_code).' - '.$this->formatPrice($max,
                                false, $currency_code);

                        if (isset($customData[$field][$currency_code]['default_original_formated']) === false || $min <= $customData[$field][$currency_code]['default']) {
                            $customData[$field][$currency_code]['default_formated'] = $dashed_format;

                            //// Do not keep special price that is already taken into account in min max
                            unset($customData['price']['special_from_date']);
                            unset($customData['price']['special_to_date']);
                            unset($customData['price']['default_original_formated']);

                            $customData[$field][$currency_code]['default'] = 0; // will be reset just after
                        }

                        if ($customer_groups_enabled) {
                            foreach ($groups as $group) {
                                $group_id = (int) $group->getData('customer_group_id');

                                if ($min != $max && $min <= $customData[$field][$currency_code]['group_'.$group_id]) {
                                    $customData[$field][$currency_code]['group_'.$group_id] = 0;
                                } else {
                                    $customData[$field][$currency_code]['group_'.$group_id] = $customData[$field][$currency_code]['default'];
                                }

                                $customData[$field][$currency_code]['group_'.$group_id.'_formated'] = $dashed_format;
                            }
                        }
                    }

                    if ($customData[$field][$currency_code]['default'] == 0) {
                        $customData[$field][$currency_code]['default'] = $min;

                        if ($min === $max) {
                            $customData[$field][$currency_code]['default_formated'] = $this->formatPrice($min, false,
                                $currency_code);
                        }
                    }

                    if ($customer_groups_enabled) {
                        foreach ($groups as $group) {
                            $group_id = (int) $group->getData('customer_group_id');

                            if ($customData[$field][$currency_code]['group_'.$group_id] == 0) {
                                $customData[$field][$currency_code]['group_'.$group_id] = $min;

                                if ($min === $max) {
                                    $customData[$field][$currency_code]['group_'.$group_id.'_formated'] = $customData[$field][$currency_code]['default_formated'];
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    protected function getValueOrValueText(Mage_Catalog_Model_Product $product, $name, Mage_Catalog_Model_Resource_Eav_Attribute $resource)
    {
        $value_text = $product->getAttributeText($name);
        if (!$value_text) {
            $value_text = $resource->getFrontend()->getValue($product);
        }

        return $value_text;
    }

    public function getObject(Mage_Catalog_Model_Product $product)
    {
        $type = $this->config->getMappedProductType($product->getTypeId());
        $this->logger->start('CREATE RECORD '.$product->getId().' '.$this->logger->getStoreName($product->storeId));
        $this->logger->log('Product type ('.$product->getTypeId().', mapped to: '.$type.')');

        $defaultData = array();

        $transport = new Varien_Object($defaultData);
        Mage::dispatchEvent('algolia_product_index_before', array('product' => $product, 'custom_data' => $transport));
        $defaultData = $transport->getData();

        $defaultData = is_array($defaultData) ? $defaultData : explode('|', $defaultData);

        $visibility = (int) $product->getVisibility();

        /** @var Mage_Catalog_Model_Product_Visibility $catalogProductVisibility */
        $catalogProductVisibility = Mage::getSingleton('catalog/product_visibility');
        $visibleInCatalog = $catalogProductVisibility->getVisibleInCatalogIds();
        $visibleInSearch = $catalogProductVisibility->getVisibleInSearchIds();

        $customData = array(
            'objectID'           => $product->getId(),
            'name'               => $product->getName(),
            'url'                => $product->getProductUrl(),
            'visibility_search'  => (int) (in_array($visibility, $visibleInSearch)),
            'visibility_catalog' => (int) (in_array($visibility, $visibleInCatalog)),
        );

        $additionalAttributes = $this->config->getProductAdditionalAttributes($product->getStoreId());
        $groups = null;

        if ($this->isAttributeEnabled($additionalAttributes, 'description')) {
            $customData['description'] = $product->getDescription();
        }

        $categories = array();
        $categories_with_path = array();

        $_categoryIds = $product->getCategoryIds();

        if (is_array($_categoryIds) && count($_categoryIds) > 0) {
            $categoryCollection = Mage::getResourceModel('catalog/category_collection')->addAttributeToSelect('name')
                                      ->addAttributeToFilter('entity_id', $_categoryIds)
                                      ->addFieldToFilter('level', array('gt' => 1))->addIsActiveFilter();

            if ($this->config->showCatsNotIncludedInNavigation($product->getStoreId()) == false) {
                $categoryCollection->addAttributeToFilter('include_in_menu', '1');
            }

            $rootCat = Mage::app()->getStore($product->getStoreId())->getRootCategoryId();

            /** @var Mage_Catalog_Model_Category $category */
            foreach ($categoryCollection as $category) {
                // Check and skip all categories that is not
                // in the path of the current store.
                $path = $category->getPath();
                $path_parts = explode('/', $path);
                if (isset($path_parts[1]) && $path_parts[1] != $rootCat) {
                    continue;
                }

                $categoryName = $category->getName();

                if ($categoryName) {
                    $categories[] = $categoryName;
                }

                $category->getUrlInstance()->setStore($product->getStoreId());
                $path = array();

                foreach ($category->getPathIds() as $treeCategoryId) {
                    $name = $this->getCategoryName($treeCategoryId, $product->getStoreId());
                    if ($name) {
                        $path[] = $name;
                    }
                }

                $categories_with_path[] = $path;
            }
        }

        if ($this->config->indexWholeCategoryTree($product->getStoreId())) {
            foreach ($categories_with_path as $result) {
                for ($i = count($result) - 1; $i > 0; $i--) {
                    $categories_with_path[] = array_slice($result, 0, $i);
                }
            }
        }

        $categories_with_path = array_intersect_key($categories_with_path,
            array_unique(array_map('serialize', $categories_with_path)));

        $categories_hierarchical = array();
        $mainCategories = array();

        $level_name = 'level';

        /** @var array $category */
        foreach ($categories_with_path as $category) {
            $categoriesCount = count($category);

            for ($i = 0; $i < $categoriesCount; $i++) {
                if (isset($categories_hierarchical[$level_name.$i]) === false) {
                    $categories_hierarchical[$level_name.$i] = array();
                }

                $mainCategories[$level_name.$i][] = $category[$i];

                if ($this->config->indexWholeCategoryTree($product->getStoreId())) {
                    $categories_hierarchical[$level_name.$i][] = implode(' /// ', array_slice($category, 0, $i + 1));
                } else {
                    if ($i === ($categoriesCount - 1)) {
                        $categories_hierarchical[$level_name.$i][] = implode(' /// ', $category);
                    }
                }
            }
        }

        foreach ($categories_hierarchical as &$level) {
            $level = array_values(array_unique($level));
        }

        foreach ($mainCategories as &$level) {
            $level = array_values(array_unique($level));
        }

        foreach ($categories_with_path as &$category) {
            $category = implode(' /// ', $category);
        }

        $customData['categories'] = $categories_hierarchical;
        $customData['categories_without_path'] = $categories;

        if ($this->isAttributeEnabled($additionalAttributes, 'main_categories')) {
            $customData['main_categories'] = $mainCategories;
        }

        /** @var Algolia_Algoliasearch_Helper_Image $imageHelper */
        $imageHelper = Mage::helper('algoliasearch/image');

        if (false === isset($defaultData['thumbnail_url'])) {
            /** @var Algolia_Algoliasearch_Helper_Image $thumb */
            $thumb = $imageHelper->init($product, 'thumbnail')->resize(75, 75);

            try {
                $customData['thumbnail_url'] = $thumb->toString();
            } catch (\Exception $e) {
                $this->logger->log($e->getMessage());
                $this->logger->log($e->getTraceAsString());

                $placeholderUrl = Mage::getDesign()->getSkinUrl($thumb->getPlaceholder());

                $customData['thumbnail_url'] = $imageHelper->removeProtocol($placeholderUrl);
            }
        }

        if (false === isset($defaultData['image_url'])) {
            /** @var Algolia_Algoliasearch_Helper_Image $image */
            $image = $imageHelper->init($product, $this->config->getImageType())
                         ->resize($this->config->getImageWidth(), $this->config->getImageHeight());

            try {
                $customData['image_url'] = $image->toString();
            } catch (\Exception $e) {
                $this->logger->log($e->getMessage());
                $this->logger->log($e->getTraceAsString());

                $placeholderUrl = Mage::getDesign()->getSkinUrl($image->getPlaceholder());

                $customData['image_url'] = $imageHelper->removeProtocol($placeholderUrl);
            }

            if ($this->isAttributeEnabled($additionalAttributes, 'media_gallery')) {
                $product->load('media_gallery');

                $customData['media_gallery'] = array();

                foreach ($product->getMediaGalleryImages() as $image) {
                    $customData['media_gallery'][] = $imageHelper->removeProtocol($image->getUrl());
                }
            }
        }

        $sub_products = null;
        $ids = null;

        if ($type == 'configurable' || $type == 'grouped' || $type == 'bundle') {
            if ($type == 'bundle') {
                $ids = array();

                $selection = $product->getTypeInstance(true)->getSelectionsCollection($product->getTypeInstance(true)
                                                                                              ->getOptionsIds($product),
                    $product);

                foreach ($selection as $option) {
                    $ids[] = $option->product_id;
                }
            }

            if ($type == 'configurable' || $type == 'grouped') {
                $ids = $product->getTypeInstance(true)->getChildrenIds($product->getId());
                $ids = call_user_func_array('array_merge', $ids);
            }

            if (count($ids)) {
                $collection = $this->getProductCollectionQuery($product->getStoreId(), $ids, false);
                $sub_products = $collection->load();
            } else {
                $sub_products = array();
            }
        }

        if (false === isset($defaultData['in_stock'])) {
            $stockItem = $product->getStockItem();

            $customData['in_stock'] = (int) $stockItem->getIsInStock();
        }

        // skip default calculation if we have provided these attributes via the observer in $defaultData
        if (false === isset($defaultData['ordered_qty']) && $this->isAttributeEnabled($additionalAttributes,
                'ordered_qty')
        ) {
            $customData['ordered_qty'] = (int) $product->getOrderedQty();
        }

        if (false === isset($defaultData['total_ordered']) && $this->isAttributeEnabled($additionalAttributes,
                'total_ordered')
        ) {
            $customData['total_ordered'] = (int) $product->getTotalOrdered();
        }

        if (false === isset($defaultData['stock_qty']) && $this->isAttributeEnabled($additionalAttributes,
                'stock_qty')
        ) {
            $customData['stock_qty'] = (int) $product->getStockQty();
        }

        if (Mage::helper('core')->isModuleEnabled('Mage_Review')) {
            if ($this->isAttributeEnabled($additionalAttributes, 'rating_summary')) {
                $customData['rating_summary'] = (int) $product->getRatingSummary();
            }
        }

        $this->setNoAttributes($additionalAttributes);

        foreach ($additionalAttributes as $attribute) {
            $attribute_name = $attribute['attribute'];
            if (isset($customData[$attribute_name])) {
                continue;
            }

            $value = $product->getData($attribute_name);

            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute_resource */
            $attribute_resource = $product->getResource()->getAttribute($attribute_name);

            if ($attribute_resource) {
                $attribute_resource->setStoreId($product->getStoreId());

                $values = array();
                $subProductImages = array();

                /**
                 * if $value is missing or if the attribute is SKU,
                 * use values from child products.
                 */
                if (($value === null || 'sku' == $attribute_name) && ($type == 'configurable' || $type == 'grouped' || $type == 'bundle')) {
                    if ($value !== null) {
                        $values[] = $this->getValueOrValueText($product, $attribute_name, $attribute_resource);
                    }

                    $all_sub_products_out_of_stock = true;

                    if ($type !== 'bundle' || in_array($attribute_name, $this->excludedAttrsFromBundledProducts, true) === false) {
                        foreach ($sub_products as $sub_product) {
                            $isInStock = (int)$sub_product->getStockItem()->getIsInStock();

                            if ($isInStock == false && $this->config->indexOutOfStockOptions($product->getStoreId()) == false) {
                                continue;
                            }

                            $all_sub_products_out_of_stock = false;

                            $value = $sub_product->getData($attribute_name);

                            if ($value) {
                                $textValue = $this->getValueOrValueText($sub_product, $attribute_name, $attribute_resource);

                                $values[] = $textValue;

                                if (mb_strtolower($attribute_name, 'utf-8') === 'color') {
                                    $image = $imageHelper->init($sub_product, $this->config->getImageType())
                                                         ->resize($this->config->getImageWidth(),
                                                             $this->config->getImageHeight());

                                    try {
                                        $textValueInLower = mb_strtolower($textValue, 'utf-8');
                                        $subProductImages[$textValueInLower] = $image->toString();
                                    } catch (\Exception $e) {
                                        $this->logger->log($e->getMessage());
                                        $this->logger->log($e->getTraceAsString());
                                    }
                                }
                            }
                        }
                    }

                    if (is_array($values) && count($values) > 0) {
                        $customData[$attribute_name] = array_values(array_unique($values, SORT_REGULAR));
                    }

                    if (empty($subProductImages) === false) {
                        $customData['images_data'] = $subProductImages;
                    }

                    // Set main product out of stock if all
                    // sub-products are out of stock.
                    if ($customData['in_stock'] && $all_sub_products_out_of_stock) {
                        $customData['in_stock'] = 0;
                    }
                } elseif (!is_array($value)) {
                    $value = $this->getValueOrValueText($product, $attribute_name, $attribute_resource);
                }

                if ($value && !isset($customData[$attribute_name])) {
                    $customData[$attribute_name] = $value;
                }
            }
        }

        $msrpEnabled = method_exists(Mage::helper('catalog'), 'canApplyMsrp') ? (bool) Mage::helper('catalog')
                                                                                          ->canApplyMsrp($product) : false;

        if (false === $msrpEnabled) {
            $this->handlePrice($product, $sub_products, $customData);
        } else {
            unset($customData['price']);
        }

        // Only for backward compatibility
        $transport = new Varien_Object($customData);
        Mage::dispatchEvent('algolia_subproducts_index', array('custom_data' => $transport, 'sub_products' => $sub_products, 'productObject' => $product));
        $customData = $transport->getData();

        $customData = array_merge($customData, $defaultData);

        $customData['type_id'] = $type;

        $this->castProductObject($customData);

        $customData = $this->clearNoValues($customData);

        $transport = new Varien_Object($customData);
        Mage::dispatchEvent('algolia_after_create_product_object', array('product_data' => $transport, 'sub_products' => $sub_products, 'productObject' => $product));
        $customData = $transport->getData();

        $this->logger->stop('CREATE RECORD '.$product->getId().' '.$this->logger->getStoreName($product->storeId));

        return $customData;
    }

    public function getAllProductIds($storeId)
    {
        $products = Mage::getModel('catalog/product')->getCollection();

        $products = $products->setStoreId($storeId);
        $products = $products->addStoreFilter($storeId);

        return $products->getAllIds();
    }

    public function shouldIndexProductByItsVisibility(Mage_Catalog_Model_Product $product, $storeId)
    {
        $productVisibility = (int) $product->getVisibility();
        $indexVisibility = $this->config->indexVisibility($storeId);

        $shouldIndex = ($productVisibility > Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);

        if ($indexVisibility === 'only_search') {
            $shouldIndex = ($productVisibility === Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH || $productVisibility === Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
        } elseif ($indexVisibility === 'only_catalog') {
            $shouldIndex = ($productVisibility === Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG || $productVisibility === Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
        }

        return $shouldIndex;
    }

    private function getVisibilityAttributeValues($storeId)
    {
        $indexVisibility = $this->config->indexVisibility($storeId);

        /** @var Mage_Catalog_Model_Product_Visibility $catalog_productVisibility */
        $catalog_productVisibility = Mage::getSingleton('catalog/product_visibility');

        $visibilityMethod = 'getVisibleInSiteIds';
        if ($indexVisibility === 'only_search') {
            $visibilityMethod = 'getVisibleInSearchIds';
        } elseif ($indexVisibility === 'only_catalog') {
            $visibilityMethod = 'getVisibleInCatalogIds';
        }

        return $catalog_productVisibility->{$visibilityMethod}();
    }

    private function explodeSynomyms($synonyms)
    {
        return array_map('trim', explode(',', $synonyms));
    }

    private function setNoAttributes($attributes)
    {
        foreach ($attributes as $attribute) {
            if (isset($attribute['index_no_value']) && $attribute['index_no_value'] !== '1') {
                $this->noAttributes[$attribute['attribute']] = 1;
            }
        }
    }

    private function clearNoValues($customData, $rootLevel = true)
    {
        foreach ($customData as $attribute => $value) {
            if (is_array($value) && $rootLevel && isset($this->noAttributes[$attribute])) {
                $customData[$attribute] = $this->clearNoValues($value, false);
            }

            if (($rootLevel !== true || isset($this->noAttributes[$attribute])) && $value === Mage::helper('catalog')->__('No')) {
                unset($customData[$attribute]);
            }
        }

        return $customData;
    }
}
