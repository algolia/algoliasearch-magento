<?php

class Algolia_Algoliasearch_Helper_Entity_Producthelper extends Algolia_Algoliasearch_Helper_Entity_Helper
{
    protected static $_productAttributes;

    protected static $_predefinedProductAttributes = array('name', 'url_key', 'description', 'image', 'thumbnail');

    protected function getIndexNameSuffix()
    {
        return '_products';
    }

    public function getAllAttributes($add_empty_row = false)
    {
        if (is_null(self::$_productAttributes))
        {
            self::$_productAttributes = array();

            /** @var $config Mage_Eav_Model_Config */
            $config = Mage::getSingleton('eav/config');

            $allAttributes = $config->getEntityAttributeCodes('catalog_product');

            $productAttributes = array_merge(array('name', 'path', 'categories', 'categories_without_path', 'description', 'ordered_qty', 'stock_qty', 'price', 'rating_summary', 'media_gallery'), $allAttributes);

            $excludedAttributes = array(
                'all_children', 'available_sort_by', 'children', 'children_count', 'custom_apply_to_products',
                'custom_design', 'custom_design_from', 'custom_design_to', 'custom_layout_update', 'custom_use_parent_settings',
                'default_sort_by', 'display_mode', 'filter_price_range', 'global_position', 'image', 'include_in_menu', 'is_active',
                'is_always_include_in_menu', 'is_anchor', 'landing_page', 'level', 'lower_cms_block',
                'page_layout', 'path_in_store', 'position', 'small_image', 'thumbnail', 'url_key', 'url_path',
                'visible_in_menu');

            $productAttributes = array_diff($productAttributes, $excludedAttributes);

            foreach ($productAttributes as $attributeCode)
                self::$_productAttributes[$attributeCode] = $config->getAttribute('catalog_product', $attributeCode)->getFrontendLabel();
        }

        $attributes = self::$_productAttributes;

        if ($add_empty_row === true)
            $attributes[''] = '';

        uksort($attributes, function ($a, $b) {
            return strcmp($a, $b);
        });

        return $attributes;
    }

    protected function isAttributeEnabled($additionalAttributes, $attr_name)
    {
        foreach ($additionalAttributes as $attr)
            if ($attr['attribute'] === $attr_name)
                return true;

        return false;
    }

    public function getProductCollectionQuery($storeId, $productIds = null, $only_visible = true, $additional_attributes = true)
    {
        /** @var $products Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $products = Mage::getResourceModel('catalog/product_collection');

        $products = $products->setStoreId($storeId)
                        ->addStoreFilter($storeId);

        if ($only_visible)
            $products = $products->addAttributeToFilter('visibility', array('in' => Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds()));

        if (false === $this->config->getShowOutOfStock($storeId))
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($products);

        $products = $products->addFinalPrice()
                        ->addAttributeToSelect('special_from_date')
                        ->addAttributeToSelect('special_to_date')
                        ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);

        $additionalAttr = $this->config->getProductAdditionalAttributes($storeId);

        if ($additional_attributes)
        {
            foreach ($additionalAttr as &$attr)
                $attr = $attr['attribute'];

            $products = $products->addAttributeToSelect(array_values(array_merge(static::$_predefinedProductAttributes, $additionalAttr)));
        }

        if ($productIds && count($productIds) > 0)
            $products = $products->addAttributeToFilter('entity_id', array('in' => $productIds));

        Mage::dispatchEvent('algolia_rebuild_store_product_index_collection_load_before', array('store' => $storeId, 'collection' => $products));

        return $products;
    }

    public function setSettings($storeId)
    {
        $attributesToIndex          = array();
        $unretrievableAttributes    = array();
        $attributesForFaceting      = array();

        foreach ($this->config->getProductAdditionalAttributes($storeId) as $attribute)
        {
            if ($attribute['searchable'] == '1')
            {
                if ($attribute['order'] == 'ordered')
                    $attributesToIndex[] = $attribute['attribute'];
                else
                    $attributesToIndex[] = 'unordered('.$attribute['attribute'].')';
            }

            if ($attribute['retrievable'] != '1')
                $unretrievableAttributes[] = $attribute['attribute'];
        }

        $customRankings = $this->config->getProductCustomRanking($storeId);

        $customRankingsArr = array();

        $facets = $this->config->getFacets();

        foreach($facets as $facet)
        {
            if ($facet['attribute'] === 'price')
            {
                $facet['attribute'] = 'price.default';

                if ($this->config->isCustomerGroupsEnabled($storeId))
                {
                    foreach ($groups = Mage::getModel('customer/group')->getCollection() as $group)
                    {
                        $group_id = (int)$group->getData('customer_group_id');

                        $attributesForFaceting[] = 'price.group_' . $group_id;
                    }
                }
            }

            $attributesForFaceting[] = $facet['attribute'];
        }


        foreach ($customRankings as $ranking)
            $customRankingsArr[] =  $ranking['order'] . '(' . $ranking['attribute'] . ')';


        $indexSettings = array(
            'attributesToIndex'         => array_values(array_unique($attributesToIndex)),
            'customRanking'             => $customRankingsArr,
            'unretrievableAttributes'   => $unretrievableAttributes,
            'attributesForFaceting'     => $attributesForFaceting,
            'maxValuesPerFacet'         => (int) $this->config->getMaxValuesPerFacet($storeId)
        );

        // Additional index settings from event observer
        $transport = new Varien_Object($indexSettings);
        Mage::dispatchEvent('algolia_index_settings_prepare', array('store_id' => $storeId, 'index_settings' => $transport));
        $indexSettings = $transport->getData();

        $mergeSettings = $this->algolia_helper->mergeSettings($this->getIndexName($storeId), $indexSettings);

        $this->algolia_helper->setSettings($this->getIndexName($storeId), $mergeSettings);

        /**
         * Handle Slaves
         */
        $sorting_indices = $this->config->getSortingIndices();

        if (count($sorting_indices) > 0)
        {
            $slaves = array();

            foreach ($sorting_indices as $values)
            {
                if ($this->config->isCustomerGroupsEnabled($storeId))
                {
                    if ($values['attribute'] === 'price')
                    {
                        foreach ($groups = Mage::getModel('customer/group')->getCollection() as $group)
                        {
                            $group_id = (int)$group->getData('customer_group_id');

                            $suffix_index_name = 'group_' . $group_id;

                            $slaves[] = $this->getIndexName($storeId) . '_' .$values['attribute'].'_' . $suffix_index_name . '_' . $values['sort'];
                        }
                    }
                }
                else
                {
                    if ($values['attribute'] === 'price')
                        $slaves[] = $this->getIndexName($storeId) . '_' .$values['attribute']. '_default_' . $values['sort'];
                    else
                        $slaves[] = $this->getIndexName($storeId) . '_' .$values['attribute']. '_' . $values['sort'];
                }
            }

            $this->algolia_helper->setSettings($this->getIndexName($storeId), array('slaves' => $slaves));

            foreach ($sorting_indices as $values)
            {
                if ($this->config->isCustomerGroupsEnabled($storeId))
                {
                    if (strpos($values['attribute'], 'price') !== false)
                    {
                        foreach ($groups = Mage::getModel('customer/group')->getCollection() as $group)
                        {
                            $group_id = (int)$group->getData('customer_group_id');

                            $suffix_index_name = 'group_' . $group_id;

                            $sort_attribute = strpos($values['attribute'], 'price') !== false ? $values['attribute'].'.'.$suffix_index_name : $values['attribute'];

                            $mergeSettings['ranking'] = array($values['sort'].'('.$sort_attribute.')', 'typo', 'geo', 'words', 'proximity', 'attribute', 'exact', 'custom');

                            $this->algolia_helper->setSettings($this->getIndexName($storeId).'_'.$values['attribute'].'_'. $suffix_index_name .'_'.$values['sort'], $mergeSettings);
                        }
                    }
                }
                else
                {
                    $sort_attribute = strpos($values['attribute'], 'price') !== false ? $values['attribute'].'.'.'default' : $values['attribute'];

                    $mergeSettings['ranking'] = array($values['sort'].'('.$sort_attribute.')', 'typo', 'geo', 'words', 'proximity', 'attribute', 'exact', 'custom');

                    $this->algolia_helper->setSettings($this->getIndexName($storeId) . '_' .$values['attribute'].'_' . 'default' . '_'.$values['sort'], $mergeSettings);
                }
            }
        }
    }

    private function handlePrice(&$product, $sub_products, &$customData, $groups = array(), $current_group_id = null)
    {
        $customData['price'] = array();

        $customData['price']['default']             = (double) Mage::helper('tax')->getPrice($product, $product->getPrice(), null, null, null, null, $product->getStore(), null);
        $customData['price']['default_formated'] = $product->getStore()->formatPrice($customData['price']['default'], false);

        if ($this->config->isCustomerGroupsEnabled($product->getStoreId()))
        {
            foreach ($groups as $group)
            {
                $group_id = (int)$group->getData('customer_group_id');

                $customData['price']['group_' . $group_id] = $customData['price']['default'];
                $customData['price']['group_' . $group_id . '_formated'] = $customData['price']['default_formated'];
            }
        }

        $special_price = null;

        if ($current_group_id !== null) // If fetch special price for groups
        {
            $discounted_price = Mage::getResourceModel('catalogrule/rule')->getRulePrice(
                Mage::app()->getLocale()->storeTimeStamp($product->getStoreId()),
                Mage::app()->getStore($product->getStoreId())->getWebsiteId(),
                $current_group_id,
                $product->getId()
            );

            if ($discounted_price !== false)
                $special_price = $discounted_price;

            if ($this->config->isCustomerGroupsEnabled($product->getStoreId()))
            {
                foreach ($groups as $group)
                {
                    $group_id = (int)$group->getData('customer_group_id');

                    $discounted_price = Mage::getResourceModel('catalogrule/rule')->getRulePrice(
                        Mage::app()->getLocale()->storeTimeStamp($product->getStoreId()),
                        Mage::app()->getStore($product->getStoreId())->getWebsiteId(),
                        $group_id,
                        $product->getId()
                    );

                    if ($discounted_price !== false)
                    {
                        $customData['price']['group_' . $group_id] = (double) Mage::helper('tax')->getPrice($product, $discounted_price, null, null, null, null, $product->getStore(), null);
                        $customData['price']['group_' . $group_id . '_formated'] = $product->getStore()->formatPrice($customData['price']['group_' . $group_id], false);
                    }
                }
            }

        }
        else // If fetch default special price
        {
            $special_price = (double) $product->getFinalPrice();
        }

        if ($special_price && $special_price !== $customData['price']['default'])
        {
            $customData['price']['special_from_date'] = strtotime($product->getSpecialFromDate());
            $customData['price']['special_to_date'] = strtotime($product->getSpecialToDate());

            $customData['price']['default_original_formated'] = $customData['price']['default'.'_formated'];

            $special_price = (double) Mage::helper('tax')->getPrice($product, $special_price, null, null, null, null, $product->getStore(), null);
            $customData['price']['default'] = $special_price;
            $customData['price']['default_formated'] = $product->getStore()->formatPrice($special_price, false);
        }

        if ($product->getTypeId() == 'configurable' || $product->getTypeId() == 'grouped' || $product->getTypeId() == 'bundle')
        {
            $min = PHP_INT_MAX;
            $max = 0;

            if ($product->getTypeId() == 'bundle')
            {
                $_priceModel = $product->getPriceModel();

                list($min, $max) = $_priceModel->getTotalPrices($product, null, null, true);
            }

            if ($product->getTypeId() == 'grouped' || $product->getTypeId() == 'configurable')
            {
                foreach ($sub_products as $sub_product)
                {
                    $price = (double) Mage::helper('tax')->getPrice($sub_product, $sub_product->getFinalPrice(), null, null, null, null, $product->getStore(), null);

                    $min = min($min, $price);
                    $max = max($max, $price);
                }
            }

            if ($min != $max)
            {
                $customData['price']['default_formated'] = $product->getStore()->formatPrice($min, false) . ' - ' . $product->getStore()->formatPrice($max, false);

                if ($this->config->isCustomerGroupsEnabled($product->getStoreId()))
                {
                    foreach ($groups as $group)
                    {
                        $group_id = (int)$group->getData('customer_group_id');

                        $customData['price']['group_' . $group_id] = 0;
                        $customData['price']['group_' . $group_id . '_formated'] = $product->getStore()->formatPrice($min, false) . ' - ' . $product->getStore()->formatPrice($max, false);
                    }
                }

                //// Do not keep special price that is already taken into account in min max
                unset($customData['price']['special_from_date']);
                unset($customData['price']['special_to_date']);
                unset($customData['price']['default_original_formated']);

                $customData['price']['default'] = 0; // will be reset just after
            }

            if ($customData['price']['default'] == 0)
            {
                $customData['price']['default'] = $min;

                if ($min === $max)
                    $customData['price']['default_formated'] = $product->getStore()->formatPrice($min, false);

                if ($this->config->isCustomerGroupsEnabled($product->getStoreId()))
                {
                    foreach ($groups as $group)
                    {
                        $group_id = (int)$group->getData('customer_group_id');
                        $customData['price']['group_' . $group_id] = $min;

                        if ($min === $max)
                            $customData['price']['group_' . $group_id] = $product->getStore()->formatPrice($min, false);
                    }
                }
            }
        }
    }

    public function getObject(Mage_Catalog_Model_Product $product)
    {
        $this->logger->start('CREATE RECORD '.$product->getId(). ' '.$this->logger->getStoreName($product->storeId));
        $this->logger->log('Product type ('.$product->getTypeId().')');
        $defaultData    = array();

        $transport      = new Varien_Object($defaultData);

        Mage::dispatchEvent('algolia_product_index_before', array('product' => $product, 'custom_data' => $transport));

        $defaultData    = $transport->getData();

        $defaultData    = is_array($defaultData) ? $defaultData : explode("|",$defaultData);

        $customData = array(
            'objectID'          => $product->getId(),
            'name'              => $product->getName(),
            'url'               => $product->getProductUrl()
        );

        $additionalAttributes = $this->config->getProductAdditionalAttributes($product->getStoreId());
        $groups = null;

        if ($this->isAttributeEnabled($additionalAttributes, 'description'))
            $customData['description'] = $product->getDescription();

        $categories             = array();
        $categories_with_path   = array();

        $_categoryIds = $product->getCategoryIds();
        if (count($_categoryIds)) {
            $categoryCollection = Mage::getResourceModel('catalog/category_collection')
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('entity_id', $_categoryIds)
                ->addIsActiveFilter();

            foreach ($categoryCollection as $category)
            {
                $categoryName = $category->getName();

                if ($categoryName)
                    $categories[] = $categoryName;

                $category->getUrlInstance()->setStore($product->getStoreId());
                $path = array();

                foreach ($category->getPathIds() as $treeCategoryId)
                {
                    $name = $this->getCategoryName($treeCategoryId, $product->getStoreId());
                    if ($name)
                        $path[] = $name;
                }

                $categories_with_path[] = $path;
            }
        }

        foreach ($categories_with_path as $result)
        {
            for ($i = count($result) - 1; $i > 0; $i--)
            {
                $categories_with_path[] = array_slice($result, 0, $i);
            }
        }

        $categories_with_path = array_intersect_key($categories_with_path, array_unique(array_map('serialize', $categories_with_path)));

        $categories_hierarchical = array();

        $level_name = 'level';

        foreach ($categories_with_path as $category)
        {
            for ($i = 0; $i < count($category); $i++)
            {
                if (isset($categories_hierarchical[$level_name.$i]) === false)
                    $categories_hierarchical[$level_name.$i] = array();

                $categories_hierarchical[$level_name.$i][] = implode(' /// ', array_slice($category, 0, $i + 1));
            }
        }

        foreach ($categories_hierarchical as &$level)
        {
            $level = array_values(array_unique($level));
        }

        foreach ($categories_with_path as &$category)
            $category = implode(' /// ',$category);

        $customData['categories'] = $categories_hierarchical;//array_values($categories_with_path);

        $customData['categories_without_path'] = $categories;

        if (false === isset($defaultData['thumbnail_url']))
        {
            try
            {
                $customData['thumbnail_url'] = $product->getThumbnailUrl();
                $customData['thumbnail_url'] = str_replace(array('https://', 'http://'
                ), '//', $customData['thumbnail_url']);
            }
            catch (\Exception $e) {}
        }

        if (false === isset($defaultData['image_url']))
        {
            try
            {
                $customData['image_url'] = Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getImage());
                $customData['image_url'] = str_replace(array('https://', 'http://'), '//', $customData['image_url']);
            }
            catch (\Exception $e) {}


            if ($this->isAttributeEnabled($additionalAttributes, 'media_gallery'))
            {
                $product->load('media_gallery');

                $customData['media_gallery'] = array();

                foreach ($product->getMediaGalleryImages() as $image)
                    $customData['media_gallery'][] = str_replace(array('https://', 'http://'), '//', $image->getUrl());
            }
        }

        $sub_products = null;
        $ids = null;

        if ($product->getTypeId() == 'configurable' || $product->getTypeId() == 'grouped' || $product->getTypeId() == 'bundle')
        {
            if ($product->getTypeId() == 'bundle')
            {
                $ids = array();

                $selection = $product->getTypeInstance(true)->getSelectionsCollection($product->getTypeInstance(true)->getOptionsIds($product), $product);

                foreach ($selection as $option)
                    $ids[] = $option->product_id;
            }

            if ($product->getTypeId() == 'configurable' || $product->getTypeId() == 'grouped')
                $ids = $product->getTypeInstance(true)->getChildrenIds($product->getId());

            if (count($ids))
            {
                $sub_products = $this->getProductCollectionQuery($product->getStoreId(), $ids, false, false)->load();
            } else
            {
                $sub_products = array();
            }
        }

        if (false === isset($defaultData['in_stock']))
        {
            $stockItem = $product->getStockItem();

            $customData['in_stock'] = (int) $stockItem->getIsInStock();
        }

        // skip default calculation if we have provided these attributes via the observer in $defaultData
        if (false === isset($defaultData['ordered_qty']) && $this->isAttributeEnabled($additionalAttributes, 'ordered_qty'))
            $customData['ordered_qty']   = (int) $product->getOrderedQty();

        if (false === isset($defaultData['stock_qty']) && $this->isAttributeEnabled($additionalAttributes, 'stock_qty'))
            $customData['stock_qty'] = (int) $product->getStockQty();

        if (Mage::helper('core')->isModuleEnabled('Mage_Review'))
            if ($this->isAttributeEnabled($additionalAttributes, 'rating_summary'))
                    $customData['rating_summary'] = $product->getRatingSummary();

        foreach ($additionalAttributes as $attribute)
        {
            if (isset($customData[$attribute['attribute']]))
                continue;

            $value = $product->getData($attribute['attribute']);

            $attribute_ressource = $product->getResource()->getAttribute($attribute['attribute']);

            if ($attribute_ressource)
            {
                $attribute_ressource = $attribute_ressource->setStoreId($product->getStoreId());

                if ($value === null)
                {
                    /** Get values as array in children */
                    if ($product->getTypeId() == 'configurable' || $product->getTypeId() == 'grouped' || $product->getTypeId() == 'bundle')
                    {
                        $values = array();

                        foreach ($sub_products as $sub_product)
                        {
                            $stock = (int) $sub_product->getStockItem()->getIsInStock();

                            if ($stock == false)
                                continue;

                            $value = $sub_product->getData($attribute['attribute']);

                            if ($value)
                            {
                                $value_text = $sub_product->getAttributeText($attribute['attribute']);

                                if ($value_text)
                                    $values[] = $value_text;
                                else
                                    $values[] = $attribute_ressource->getFrontend()->getValue($sub_product);
                            }
                        }

                        if (count($values) > 0)
                        {
                            $customData[$attribute['attribute']] = array_values(array_unique($values));
                        }
                    }
                }
                else
                {
                    $value_text = $product->getAttributeText($attribute['attribute']);

                    if ($value_text)
                        $value = $value_text;
                    else
                    {
                        $attribute_ressource = $attribute_ressource->setStoreId($product->getStoreId());
                        $value = $attribute_ressource->getFrontend()->getValue($product);
                    }

                    if ($value)
                    {
                        $customData[$attribute['attribute']] = $value;
                    }
                }
            }
        }

        $this->handlePrice($product, $sub_products, $customData);

        if ($this->config->isCustomerGroupsEnabled())
        {
            $groups = Mage::getModel('customer/group')->getCollection();

            foreach ($groups as $group)
            {
                $group_id = (int)$group->getData('customer_group_id');
                $this->handlePrice($product, $sub_products, $customData, $groups, $group_id);
            }
        }

        $transport = new Varien_Object($customData);
        Mage::dispatchEvent('algolia_subproducts_index', array('custom_data' => $transport, 'sub_products' => $sub_products));
        $customData = $transport->getData();

        $customData = array_merge($customData, $defaultData);

        $customData['type_id'] = $product->getTypeId();

        $this->castProductObject($customData);

        $this->logger->stop('CREATE RECORD '.$product->getId(). ' '.$this->logger->getStoreName($product->storeId));

        return $customData;
    }
}
