<?php

class Algolia_Algoliasearch_Helper_Entity_Categoryhelper extends Algolia_Algoliasearch_Helper_Entity_Helper
{
    protected static $_categoryAttributes;
    protected static $_rootCategoryId = -1;

    protected function getIndexNameSuffix()
    {
        return '_categories';
    }

    public function getIndexSettings($storeId)
    {
        $searchableAttributes = array();
        $unretrievableAttributes = array();

        foreach ($this->config->getCategoryAdditionalAttributes($storeId) as $attribute) {
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
        }

        $customRankings = $this->config->getCategoryCustomRanking($storeId);

        $customRankingsArr = array();

        foreach ($customRankings as $ranking) {
            $customRankingsArr[] = $ranking['order'].'('.$ranking['attribute'].')';
        }

        // Default index settings
        $indexSettings = array(
            'searchableAttributes'    => array_values(array_unique($searchableAttributes)),
            'customRanking'           => $customRankingsArr,
            'unretrievableAttributes' => $unretrievableAttributes,
        );

        // Additional index settings from event observer
        $transport = new Varien_Object($indexSettings);
        Mage::dispatchEvent('algolia_index_settings_prepare', array('store_id' => $storeId, 'index_settings' => $transport));
        Mage::dispatchEvent('algolia_categories_index_before_set_settings', array('store_id' => $storeId, 'index_settings' => $transport));
        $indexSettings = $transport->getData();

        $this->algolia_helper->mergeSettings($this->getIndexName($storeId), $indexSettings);

        return $indexSettings;
    }

    public function getCategoryCollectionQuery($storeId, $categoryIds = null)
    {
        $storeRootCategoryPath = sprintf('%d/%d', $this->getRootCategoryId(), Mage::app()->getStore($storeId)->getRootCategoryId());

        $categories = Mage::getResourceModel('catalog/category_collection'); /* @var $categories Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection */

        $unserializedCategorysAttrs = $this->config->getCategoryAdditionalAttributes($storeId);

        $additionalAttr = array();

        foreach ($unserializedCategorysAttrs as $attr) {
            $additionalAttr[] = $attr['attribute'];
        }

        $additionalAttr[] = 'include_in_menu';
        $additionalAttr[] = 'image';
        $additionalAttr[] = 'thumbnail';

        $categories
            ->addPathFilter($storeRootCategoryPath)
            ->addNameToResult()
            ->addUrlRewriteToResult()
            ->addIsActiveFilter()
            ->setStoreId($storeId)
            ->addAttributeToSelect(array_merge(array('name'), $additionalAttr))
            ->addFieldToFilter('level', array('gt' => 1));

        if ($categoryIds) {
            $categories->addFieldToFilter('entity_id', array('in' => $categoryIds));
        }

        Mage::dispatchEvent('algolia_after_categories_collection_build', array('store' => $storeId, 'collection' => $categories));

        return $categories;
    }

    public function getAllAttributes()
    {
        if (is_null(self::$_categoryAttributes)) {
            self::$_categoryAttributes = array();

            /** @var $config Mage_Eav_Model_Config */
            $config = Mage::getSingleton('eav/config');

            $allAttributes = $config->getEntityAttributeCodes('catalog_category');

            $categoryAttributes = array_merge($allAttributes, array('product_count'));

            $excludedAttributes = array(
                'all_children', 'available_sort_by', 'children', 'children_count', 'custom_apply_to_products',
                'custom_design', 'custom_design_from', 'custom_design_to', 'custom_layout_update', 'custom_use_parent_settings',
                'default_sort_by', 'display_mode', 'filter_price_range', 'global_position', 'image', 'include_in_menu', 'is_active',
                'is_always_include_in_menu', 'is_anchor', 'landing_page', 'level', 'lower_cms_block',
                'page_layout', 'path_in_store', 'position', 'small_image', 'thumbnail', 'url_key', 'url_path',
                'visible_in_menu', );

            $categoryAttributes = array_diff($categoryAttributes, $excludedAttributes);

            foreach ($categoryAttributes as $attributeCode) {
                self::$_categoryAttributes[$attributeCode] = $config->getAttribute('catalog_category', $attributeCode)->getFrontendLabel();
            }
        }

        return self::$_categoryAttributes;
    }

    public function getObject(Mage_Catalog_Model_Category $category)
    {
        $storeId = $category->getStoreId();

        /** @var Algolia_Algoliasearch_Helper_Entity_Producthelper $productHelper */
        $productHelper = Mage::helper('algoliasearch/entity_producthelper');

        $collection = $productHelper->getProductCollectionQuery($storeId, null, true, true);

        $productCollection = clone $collection;
        $productCollection = $productCollection->addCategoryFilter($category);

        $category->setProductCount($productCollection->getSize());

        $customData = array();

        $transport = new Varien_Object($customData);
        Mage::dispatchEvent('algolia_category_index_before', array('category' => $category, 'custom_data' => $transport));
        $customData = $transport->getData();

        $category->getUrlInstance()->setStore($storeId);
        $path = '';
        foreach ($category->getPathIds() as $categoryId) {
            if ($path != '') {
                $path .= ' / ';
            }
            $path .= $this->getCategoryName($categoryId, $storeId);
        }

        $data = array(
            'objectID'        => $category->getId(),
            'name'            => $category->getName(),
            'path'            => $path,
            'level'           => $category->getLevel(),
            'url'             => $category->getUrl(),
            'include_in_menu' => $category->getIncludeInMenu(),
            '_tags'           => array('category'),
            'popularity'      => 1,
            'product_count'   => $category->getProductCount(),
        );

        try {
            $imageUrl = $this->getThumbnailUrl($category) ?: $category->getImageUrl();
            if ($imageUrl) {
                /** @var Algolia_Algoliasearch_Helper_Image $imageHelper */
                $imageHelper = Mage::helper('algoliasearch/image');

                $data['image_url'] = $imageHelper->removeProtocol($imageUrl);
            }
        } catch (\Exception $e) {
            // no image, no default, not fatal
        }

        foreach ($this->config->getCategoryAdditionalAttributes($storeId) as $attribute) {
            $value = $category->getData($attribute['attribute']);

            $attribute_resource = $category->getResource()->getAttribute($attribute['attribute']);

            if ($attribute_resource) {
                $value = $attribute_resource->getFrontend()->getValue($category);
            }

            if (isset($data[$attribute['attribute']])) {
                $value = $data[$attribute['attribute']];
            }

            if ($value) {
                $data[$attribute['attribute']] = $value;
            }
        }

        $data = array_merge($data, $customData);

        foreach ($data as &$data0) {
            $data0 = $this->try_cast($data0);
        }

        $transport = new Varien_Object($data);
        Mage::dispatchEvent('algolia_after_create_category_object', array('category' => $transport, 'categoryObject' => $category));
        $data = $transport->getData();

        return $data;
    }

    public function getRootCategoryId()
    {
        if (-1 === self::$_rootCategoryId) {
            $collection = Mage::getResourceModel('catalog/category_collection');
            $collection->addFieldToFilter('parent_id', 0);
            $collection->getSelect()->limit(1);
            $rootCategory = $collection->getFirstItem();
            self::$_rootCategoryId = $rootCategory->getId();
        }

        return self::$_rootCategoryId;
    }

    private function getThumbnailUrl($category)
    {
        $url = false;
        if ($image = $category->getThumbnail()) {
            $url = Mage::getBaseUrl('media').'catalog/category/'.$image;
        }

        return $url;
    }
}
