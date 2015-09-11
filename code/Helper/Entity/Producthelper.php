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

            $productAttributes = array_merge(array('name', 'path', 'categories', 'categories_without_path', 'description', 'ordered_qty', 'stock_qty', 'price_with_tax', 'rating_summary'), $allAttributes);

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
            if ($attr['attribute'] == $attr_name)
                return true;

        return false;
    }

    public function getProductCollectionQuery($storeId, $productIds = null, $only_visible = true)
    {
        /** @var $products Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $products = Mage::getResourceModel('catalog/product_collection');

        $products = $products->setStoreId($storeId)
                        ->addStoreFilter($storeId);

        if ($only_visible)
            $products = $products->addAttributeToFilter('visibility', array('in' => Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds()));

        $products = $products->addFinalPrice()
                        ->addAttributeToSelect('special_from_date')
                        ->addAttributeToSelect('special_to_date')
                        ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);

        $additionalAttr = $this->config->getProductAdditionalAttributes($storeId);

        foreach ($additionalAttr as &$attr)
            $attr = $attr['attribute'];

        $products = $products->addAttributeToSelect(array_values(array_merge(static::$_predefinedProductAttributes, $additionalAttr)));

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
            $attributesForFaceting[] = $facet['attribute'];

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
                    if (strpos($values['attribute'], 'price') !== false)
                    {
                        foreach ($groups = Mage::getModel('customer/group')->getCollection() as $group)
                        {
                            $group_id = (int)$group->getData('customer_group_id');

                            $suffix_index_name = 'group_' . $group_id;

                            $slaves[] = $this->getIndexName($storeId) . '_' . $suffix_index_name . '_' .$values['attribute'].'_'.$values['sort'];
                        }
                    }
                }
                else
                {
                    $slaves[] = $this->getIndexName($storeId) . '_' . 'default' . '_' .$values['attribute'].'_'.$values['sort'];
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

                            $suffix_index_name = '_group_' . $group_id;

                            $mergeSettings['ranking'] = array($values['sort'].'('.$values['attribute'].'.'.$group_id.')', 'typo', 'geo', 'words', 'proximity', 'attribute', 'exact', 'custom');

                            $this->algolia_helper->setSettings($this->getIndexName($storeId).$suffix_index_name.'_'.$values['attribute'].'_'.$values['sort'], $mergeSettings);
                        }
                    }
                }
                else
                {
                    $mergeSettings['ranking'] = array($values['sort'].'('.$values['attribute'].'.'.'default'.')', 'typo', 'geo', 'words', 'proximity', 'attribute', 'exact', 'custom');

                    $this->algolia_helper->setSettings($this->getIndexName($storeId) . '_' . 'default' . '_' .$values['attribute'].'_'.$values['sort'], $mergeSettings);
                }
            }
        }
    }

    private function handlePrice(&$product, &$customData, $group_id = null)
    {
        $key = $group_id === null ? 'default' : $group_id;

        $customData['price'][$key]             = (double) $product->getPrice();
        $customData['price_with_tax'][$key]    = (double) Mage::helper('tax')->getPrice($product, $product->getPrice(), true, null, null, null, null, false);

        $special_price = null;

        if ($group_id !== null) // If fetch special price for groups
        {
            $discounted_price = Mage::getResourceModel('catalogrule/rule')->getRulePrice(
                Mage::app()->getLocale()->storeTimeStamp($product->getStoreId()),
                Mage::app()->getStore($product->getStoreId())->getWebsiteId(),
                $group_id,
                $product->getId());

            if ($discounted_price !== false)
                $special_price = $discounted_price;
        }
        else // If fetch default special price
            $special_price = (double) $product->getFinalPrice();

        if ($special_price && $special_price !== $customData['price'][$key])
        {
            $customData['special_price_from_date'][$key] = strtotime($product->getSpecialFromDate());
            $customData['special_price_to_date'][$key] = strtotime($product->getSpecialToDate());

            $customData['special_price'][$key] = (double) $special_price;
            $customData['special_price_with_tax'][$key] = (double) Mage::helper('tax')->getPrice($product, $special_price, true, null, null, null, null, false);

            $customData['special_price_formated'][$key] = $product->getStore()->formatPrice($customData['special_price'][$key], false);
            $customData['special_price_with_tax_formated'][$key] = $product->getStore()->formatPrice($customData['special_price_with_tax'][$key], false);
        }
        else
        {
            /**
             * In case of partial updates set back to empty so that it get updated
             */
            $customData['special_price'][$key] = '';
            $customData['special_price_with_tax'][$key] = '';
        }

        $customData['price_formated'][$key] = $product->getStore()->formatPrice($customData['price'][$key], false);
        $customData['price_with_tax_formated'][$key] = $product->getStore()->formatPrice($customData['price_with_tax'][$key], false);
    }

    public function getObject(Mage_Catalog_Model_Product $product)
    {
        $defaultData    = array();

        $transport      = new Varien_Object($defaultData);

        Mage::dispatchEvent('algolia_product_index_before', array('product' => $product, 'custom_data' => $transport));

        $defaultData    = $transport->getData();

        $defaultData    = is_array($defaultData) ? $defaultData : explode("|",$defaultData);

        $customData = array(
            'objectID'          => $product->getId(),
            'name'              => $product->getName(),
            'url'               => $product->getProductUrl(),
            'description'       => $product->getDescription()
        );

        foreach (array('price', 'price_with_tax', 'special_price_from_date', 'special_price_to_date', 'special_price'
                    ,'special_price_with_tax', 'special_price_formated', 'special_price_with_tax_formated'
                    ,'price_formated', 'price_with_tax_formated') as $price)
            $customData[$price] = array();

        $this->handlePrice($product, $customData);

        if ($this->config->isCustomerGroupsEnabled())
        {
            foreach ($groups = Mage::getModel('customer/group')->getCollection() as $group)
            {
                $group_id = (int)$group->getData('customer_group_id');
                $this->handlePrice($product, $customData, $group_id);
            }
        }

        $categories             = array();
        $categories_with_path   = array();

        foreach ($this->getProductActiveCategories($product, $product->getStoreId()) as $categoryId)
        {
            $category = Mage::getModel('catalog/category')->load($categoryId);

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
                $customData['image_url'] = $product->getImageUrl();
                $customData['image_url'] = str_replace(array('https://', 'http://'), '//', $customData['image_url']);
            }
            catch (\Exception $e) {}
        }

        $additionalAttributes = $this->config->getProductAdditionalAttributes($product->getStoreId());

        $sub_products = null;
        $ids = null;

        if ($product->getTypeId() == 'configurable' || $product->getTypeId() == 'grouped' || $product->getTypeId() == 'bundle')
        {
            $min = PHP_INT_MAX;
            $max = 0;

            $min_with_tax = PHP_INT_MAX;
            $max_with_tax = 0;

            if ($product->getTypeId() == 'bundle')
            {

                $_priceModel  = $product->getPriceModel();

                list($min, $max) = $_priceModel->getTotalPrices($product, null, null, false);
                list($min_with_tax, $max_with_tax) = $_priceModel->getTotalPrices($product, null, true, false);

                $ids = array();

                $selection = $product->getTypeInstance(true)->getSelectionsCollection($product->getTypeInstance(true)->getOptionsIds($product), $product);

                foreach($selection as $option)
                    $ids[] = $option->product_id;
            }

            if ($product->getTypeId() == 'grouped')
                $sub_products = $product->getTypeInstance(true)->getAssociatedProducts($product);

            if ($product->getTypeId() == 'configurable')
                $sub_products = $product->getTypeInstance(true)->getUsedProducts(null, $product);

            if ($product->getTypeId() == 'grouped' || $product->getTypeId() == 'configurable')
            {
                $ids = array_map(function ($product)
                {
                    return $product->getId();
                }, $sub_products);
            }

            $sub_products = $this->getProductCollectionQuery($product->getStoreId(), $ids, false)->load();

            if ($product->getTypeId() == 'grouped' || $product->getTypeId() == 'configurable')
            {
                foreach ($sub_products as $sub_product)
                {
                    $price = $sub_product->getPrice();
                    $price_with_tax = Mage::helper('tax')->getPrice($sub_product, $price, true, null, null, null, null, false);

                    $min = min($min, $price);
                    $max = max($max, $price);

                    $min_with_tax = min($min_with_tax, $price_with_tax);
                    $max_with_tax = max($max_with_tax, $price_with_tax);
                }
            }

            $customData['min_formated']             = array();
            $customData['max_formated']             = array();
            $customData['min_with_tax_formated']    = array();
            $customData['max_with_tax_formated']    = array();

            $customData['min_formated']['default'] = $product->getStore()->formatPrice($min, false);
            $customData['max_formated']['default'] = $product->getStore()->formatPrice($max, false);
            $customData['min_with_tax_formated']['default'] = $product->getStore()->formatPrice($min_with_tax, false);
            $customData['max_with_tax_formated']['default'] = $product->getStore()->formatPrice($max_with_tax, false);

            if ($this->config->isCustomerGroupsEnabled($product->getStoreId()))
            {
                foreach ($groups = Mage::getModel('customer/group')->getCollection() as $group)
                {
                    $group_id = (int)$group->getData('customer_group_id');

                    $customData['min_formated']['group_' . $group_id]           = $product->getStore()->formatPrice($min, false);
                    $customData['max_formated']['group_' . $group_id]           = $product->getStore()->formatPrice($max, false);
                    $customData['min_with_tax_formated']['group_' . $group_id]  = $product->getStore()->formatPrice($min_with_tax, false);
                    $customData['max_with_tax_formated']['group_' . $group_id]  = $product->getStore()->formatPrice($max_with_tax, false);
                }
            }

        }

        if (false === isset($defaultData['in_stock']))
        {
            $stockItem = $product->getStockItem();

            $customData['in_stock'] = (int) $stockItem->getIsInStock();
        }

        // skip default calculation if we have provided these attributes via the observer in $defaultData
        if (false === isset($defaultData['ordered_qty']) && false === isset($defaultData['stock_qty']))
        {
            $query = Mage::getResourceModel('sales/order_item_collection');
            $query->getSelect()->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('sku','SUM(qty_ordered) as ordered_qty'))
                ->group(array('sku'))
                ->where('sku = ?',array($product->getSku()))
                ->limit(1);

            $ordered_qty = (int) $query->getFirstItem()->ordered_qty;

            $customData['ordered_qty'] = (int) $ordered_qty;
            $customData['stock_qty']   = (int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();

            if ($product->getTypeId() == 'configurable' || $product->getTypeId() == 'grouped' || $product->getTypeId() == 'bundle')
            {
                $ordered_qty  = 0;
                $stock_qty    = 0;

                foreach ($sub_products as $sub_product)
                {
                    $stock_qty += (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($sub_product)->getQty();

                    $query = Mage::getResourceModel('sales/order_item_collection');
                    $query->getSelect()->reset(Zend_Db_Select::COLUMNS)
                        ->columns(array('sku','SUM(qty_ordered) as ordered_qty'))
                        ->group(array('sku'))
                        ->where('sku = ?',array($sub_product->getSku()))
                        ->limit(1);

                    $ordered_qty += (int) $query->getFirstItem()->ordered_qty;
                }

                $customData['ordered_qty'] = $ordered_qty;
                $customData['stock_qty']   = $stock_qty;
            }


            if ($this->isAttributeEnabled($additionalAttributes, 'ordered_qty') === false)
                unset($customData['ordered_qty']);

            if ($this->isAttributeEnabled($additionalAttributes, 'stock_qty') === false)
                unset($customData['stock_qty']);
        }


        if (Mage::helper('core')->isModuleEnabled('Mage_Review'))
        {
            if ($this->isAttributeEnabled($additionalAttributes, 'rating_summary'))
            {
                $summaryData = Mage::getModel('review/review_summary')
                    ->setStoreId($product->getStoreId())
                    ->load($product->getId());

                if ($summaryData['rating_summary'])
                    $customData['rating_summary'] = $summaryData['rating_summary'];
            }
        }

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
                            $customData[$attribute['attribute']] = $values;
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

        $customData = array_merge($customData, $defaultData);

        $customData['type_id'] = $product->getTypeId();

        $this->castProductObject($customData);

        return $customData;
    }
}
