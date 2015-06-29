<?php

class Algolia_Algoliasearch_Helper_Entity_Producthelper extends Algolia_Algoliasearch_Helper_Entity_Helper
{
    protected static $_productAttributes;

    protected static $_predefinedProductAttributes = array('name', 'url_key', 'description', 'image', 'thumbnail');

    protected function getIndexNameSuffix()
    {
        return '_products';
    }

    public function getAllAttributes()
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
                self::$_productAttributes[$attributeCode] = $config->getAttribute('catalog_category', $attributeCode)->getFrontendLabel();

            uksort(self::$_productAttributes, function ($a, $b) {
                return strcmp($a, $b);
            });
        }

        return self::$_productAttributes;
    }

    protected function isAttributeEnabled($additionalAttributes, $attr_name)
    {
        foreach ($additionalAttributes as $attr)
            if ($attr['attribute'] == $attr_name)
                return true;

        return false;
    }

    protected function getReportForProduct($product)
    {
        $report = Mage::getResourceModel('reports/product_collection')
            ->addOrderedQty()
            ->addAttributeToFilter('sku', $product->getSku())
            ->setOrder('ordered_qty', 'desc')
            ->getFirstItem();

        return $report;
    }

    public function getProductCollectionQuery($storeId, $productIds = null)
    {
        /** @var $products Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $products = Mage::getResourceModel('catalog/product_collection');

        $products = $products->setStoreId($storeId)
                        ->addStoreFilter($storeId)
                        ->addAttributeToFilter('visibility', array('in' => Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds()))
                        ->addFinalPrice()
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

    public function getIndexSettings($storeId)
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

        /**
         * Handle Slaves
         */


        $sorting_indices = $this->config->getSortingIndices();

        if (count($sorting_indices) > 0)
        {
            $slaves = array();

            foreach ($sorting_indices as $values)
                $slaves[] = $this->getIndexName($storeId).'_'.$values['attribute'].'_'.$values['sort'];

            $this->algolia_helper->setSettings($this->getIndexName($storeId), array('slaves' => $slaves));

            foreach ($sorting_indices as $values)
            {
                $mergeSettings['ranking'] = array($values['sort'].'('.$values['attribute'].')', 'typo', 'geo', 'words', 'proximity', 'attribute', 'exact', 'custom');

                $this->algolia_helper->setSettings($this->getIndexName($storeId).'_'.$values['attribute'].'_'.$values['sort'], $mergeSettings);
            }
        }

        unset($mergeSettings['ranking']);

        return $mergeSettings;
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
            'price'             => $product->getPrice(),
            'price_with_tax'    => Mage::helper('tax')->getPrice($product, $product->getPrice(), true, null, null, null, null, false),
            'url'               => $product->getProductUrl(),
            'description'       => $product->getDescription()
        );

        $special_price = $product->getFinalPrice();

        if ($special_price != $customData['price'])
        {
            $customData['special_price_from_date']          = strtotime($product->getSpecialFromDate());
            $customData['special_price_to_date']            = strtotime($product->getSpecialToDate());

            $customData['special_price']                    = $special_price;
            $customData['special_price_with_tax']           = Mage::helper('tax')->getPrice($product, $special_price, true, null, null, null, null, false);

            $customData['special_price_formated']           = Mage::helper('core')->formatPrice($customData['special_price'], false);
            $customData['special_price_with_tax_formated']  = Mage::helper('core')->formatPrice($customData['special_price_with_tax'], false);
        }

        $customData['price_formated']           = Mage::helper('core')->formatPrice($customData['price'], false);
        $customData['price_with_tax_formated']  = Mage::helper('core')->formatPrice($customData['price_with_tax'], false);

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

        foreach ($categories_with_path as &$category)
            $category = implode(' /// ',$category);

        $customData['categories'] = array_values($categories_with_path);

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

        if ($product->getTypeId() == 'configurable' || $product->getTypeId() == 'grouped')
        {
            if ($product->getTypeId() == 'grouped')
                $sub_products = $product->getTypeInstance(true)->getAssociatedProducts($product);

            if ($product->getTypeId() == 'configurable')
                $sub_products = $product->getTypeInstance(true)->getUsedProducts(null, $product);

            $min = PHP_INT_MAX;
            $max = 0;

            $min_with_tax = PHP_INT_MAX;
            $max_with_tax = 0;

            foreach ($sub_products as $sub_product)
            {
                $sub_product = Mage::getModel('catalog/product')->load($sub_product->getId());

                $price = $sub_product->getPrice();
                $price_with_tax = Mage::helper('tax')->getPrice($sub_product, $price, true, null, null, null, null, false);

                $min = min($min, $price);
                $max = max($max, $price);

                $min_with_tax = min($min_with_tax, $price_with_tax);
                $max_with_tax = max($max_with_tax, $price_with_tax);
            }

            $customData['min_formated'] = Mage::helper('core')->formatPrice($min, false);
            $customData['max_formated'] = Mage::helper('core')->formatPrice($max, false);
            $customData['min_with_tax_formated'] = Mage::helper('core')->formatPrice($min_with_tax, false);
            $customData['max_with_tax_formated'] = Mage::helper('core')->formatPrice($max_with_tax, false);

        }

        if (false === isset($defaultData['in_stock']))
        {
            $stockItem = $product->getStockItem();

            $customData['in_stock'] = (int) $stockItem->getIsInStock();
        }

        // skip default calculation if we have provided these attributes via the observer in $defaultData
        if (false === isset($defaultData['ordered_qty']) && false === isset($defaultData['stock_qty']))
        {

            $ordered_qty = Mage::getResourceModel('reports/product_collection')
                ->addOrderedQty()
                ->addAttributeToFilter('sku', $product->getSku())
                ->setOrder('ordered_qty', 'desc')
                ->getFirstItem()
                ->ordered_qty;

            $customData['ordered_qty'] = (int) $ordered_qty;
            $customData['stock_qty']   = (int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();

            if ($product->getTypeId() == 'configurable' || $product->getTypeId() == 'grouped')
            {
                $ordered_qty  = 0;
                $stock_qty    = 0;

                foreach ($sub_products as $sub_product)
                {
                    $stock_qty += (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($sub_product)->getQty();

                    $ordered_qty += (int) $this->getReportForProduct($sub_product)->ordered_qty;
                }

                $customData['ordered_qty'] = $ordered_qty;
                $customData['stock_qty']   = $stock_qty;
            }

            if ($this->isAttributeEnabled($additionalAttributes, 'ordered_qty') == false)
                unset($customData['ordered_qty']);

            if ($this->isAttributeEnabled($additionalAttributes, 'stock_qty') == false)
                unset($customData['stock_qty']);
        }

        if ($this->isAttributeEnabled($additionalAttributes, 'rating_summary'))
        {
            $summaryData = Mage::getModel('review/review_summary')
                ->setStoreId($product->getStoreId())
                ->load($product->getId());

            if ($summaryData['rating_summary'])
                $customData['rating_summary'] = $summaryData['rating_summary'];
        }

        foreach ($additionalAttributes as $attribute)
        {
            $value = $product->getData($attribute['attribute']);

            $attribute_ressource = $product->getResource()->getAttribute($attribute['attribute']);

            if ($attribute_ressource)
            {
                $value = $attribute_ressource->getFrontend()->getValue($product);
            }

            if ($value)
                $customData[$attribute['attribute']] = $value;
        }

        $customData = array_merge($customData, $defaultData);

        $customData['type_id'] = $product->getTypeId();

        $this->castProductObject($customData);

        return $customData;
    }
}