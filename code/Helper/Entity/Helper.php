<?php

abstract class Algolia_Algoliasearch_Helper_Entity_Helper
{
    protected $config;

    private static $_activeCategories;
    private static $_categoryNames;
    protected $_dataPrefix = 'algolia_';

    abstract protected function getIndexNameSuffix();

    public function __construct()
    {
        $this->config = new Algolia_Algoliasearch_Helper_Config();
    }

    public function getBaseIndexName($storeId = null)
    {
        return (string) $this->config->getIndexPrefix($storeId) . Mage::app()->getStore($storeId)->getCode();
    }

    public function getIndexName($storeId = null)
    {
        return (string) $this->getBaseIndexName($storeId) . $this->getIndexNameSuffix();
    }

    protected function try_cast($value)
    {
        if (is_numeric($value) && floatval($value) == floatval(intval($value)))
            return intval($value);

        if (is_numeric($value))
            return floatval($value);

        return $value;
    }

    protected function castProductObject(&$productData)
    {
        foreach ($productData as $key => &$data)
        {
            $data = $this->try_cast($data);

            if (is_array($data) === false)
            {
                $data = explode('|', $data);

                if (count($data) == 1)
                {
                    $data = $data[0];
                    $data = $this->try_cast($data);
                }
                else
                    foreach($data as &$element)
                        $element = $this->try_cast($element);
            }
        }
    }

    protected function strip($s)
    {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        $s = preg_replace('/&nbsp;/', ' ', $s);
        $s = preg_replace('!\s+!', ' ', $s);
        return trim(strip_tags($s));
    }

    public function getProductActiveCategories(Mage_Catalog_Model_Product $product, $storeId = null)
    {
        $activeCategories = array();
        foreach ($product->getCategoryIds() as $categoryId) {
            if ($this->isCategoryActive($categoryId, $storeId)) {
                $activeCategories[] = $categoryId;
            }
        }
        return $activeCategories;
    }

    public function isCategoryActive($categoryId, $storeId = null)
    {
        $storeId = intval($storeId);
        $categoryId = intval($categoryId);
        // Check whether the specified category is active
        if ($path = $this->getCategoryPath($categoryId, $storeId)) {
            // Check whether all parent categories for the current category are active
            $isActive = TRUE;
            $parentCategoryIds = explode('/', $path);
            // Exclude root category
            if (count($parentCategoryIds) <= 2) {
                return FALSE;
            }
            // Remove root category
            array_shift($parentCategoryIds);
            // Remove current category as it is already verified
            array_pop($parentCategoryIds);
            // Start from the first parent
            $parentCategoryIds = array_reverse($parentCategoryIds);
            foreach ($parentCategoryIds as $parentCategoryId) {
                if ( ! ($parentCategoryPath = $this->getCategoryPath($parentCategoryId, $storeId))) {
                    $isActive = FALSE;
                    break;
                }
            }
            if ($isActive) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function getCategoryPath($categoryId, $storeId = null)
    {
        $categories = $this->getCategories();
        $storeId = intval($storeId);
        $categoryId = intval($categoryId);
        $path = null;
        $key = $storeId.'-'.$categoryId;
        if (isset($categories[$key])) {
            $path = ($categories[$key]['value'] == 1) ? strval($categories[$key]['path']) : null;
        } elseif ($storeId !== 0) {
            $key = '0-'.$categoryId;
            if (isset($categories[$key])) {
                $path = ($categories[$key]['value'] == 1) ? strval($categories[$key]['path']) : null;
            }
        }
        return $path;
    }

    public function getCategories()
    {
        if (is_null(self::$_activeCategories)) {
            self::$_activeCategories = array();
            $resource = Mage::getResourceModel('catalog/category'); /** @var $resource Mage_Catalog_Model_Resource_Category */
            if ($attribute = $resource->getAttribute('is_active')) {
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read'); /** @var $connection Varien_Db_Adapter_Pdo_Mysql */
                $select = $connection->select()
                    ->from(array('backend' => $attribute->getBackendTable()), array('key' => new Zend_Db_Expr("CONCAT(backend.store_id, '-', backend.entity_id)"), 'category.path', 'backend.value'))
                    ->join(array('category' => $resource->getTable('catalog/category')), 'backend.entity_id = category.entity_id', array())
                    ->where('backend.entity_type_id = ?', $attribute->getEntityTypeId())
                    ->where('backend.attribute_id = ?', $attribute->getAttributeId())
                    ->order('backend.store_id')
                    ->order('backend.entity_id');
                self::$_activeCategories = $connection->fetchAssoc($select);
            }
        }
        return self::$_activeCategories;
    }

    public function getCategoryName($categoryId, $storeId = null)
    {
        if ($categoryId instanceof Mage_Catalog_Model_Category) {
            $categoryId = $categoryId->getId();
        }
        if ($storeId instanceof Mage_Core_Model_Store) {
            $storeId = $storeId->getId();
        }
        $categoryId = intval($categoryId);
        $storeId = intval($storeId);

        if (is_null(self::$_categoryNames)) {
            self::$_categoryNames = array();
            $resource = Mage::getResourceModel('catalog/category'); /** @var $resource Mage_Catalog_Model_Resource_Category */
            if ($attribute = $resource->getAttribute('name')) {
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read'); /** @var $connection Varien_Db_Adapter_Pdo_Mysql */
                $select = $connection->select()
                    ->from(array('backend' => $attribute->getBackendTable()), array(new Zend_Db_Expr("CONCAT(backend.store_id, '-', backend.entity_id)"), 'backend.value'))
                    ->join(array('category' => $resource->getTable('catalog/category')), 'backend.entity_id = category.entity_id', array())
                    ->where('backend.entity_type_id = ?', $attribute->getEntityTypeId())
                    ->where('backend.attribute_id = ?', $attribute->getAttributeId())
                    ->where('category.level > ?', 1);
                self::$_categoryNames = $connection->fetchPairs($select);
            }
        }

        $categoryName = null;
        $key = $storeId.'-'.$categoryId;
        if (isset(self::$_categoryNames[$key])) { // Check whether the category name is present for the specified store
            $categoryName = strval(self::$_categoryNames[$key]);
        } elseif ($storeId != 0) { // Check whether the category name is present for the default store
            $key = '0-'.$categoryId;
            if (isset(self::$_categoryNames[$key])) {
                $categoryName = strval(self::$_categoryNames[$key]);
            }
        }

        return $categoryName;
    }

}