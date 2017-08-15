<?php

abstract class Algolia_Algoliasearch_Helper_Entity_Helper
{
    /** @var Algolia_Algoliasearch_Helper_Config */
    protected $config;

    /** @var Algolia_Algoliasearch_Helper_Logger */
    protected $logger;

    /** @var Algolia_Algoliasearch_Helper_Algoliahelper */
    protected $algolia_helper;

    protected static $_activeCategories;
    protected static $_categoryNames;

    abstract protected function getIndexNameSuffix();

    public function __construct()
    {
        $this->config = Mage::helper('algoliasearch/config');
        $this->algolia_helper = Mage::helper('algoliasearch/algoliahelper');
        $this->logger = Mage::helper('algoliasearch/logger');
    }

    public function getBaseIndexName($storeId = null)
    {
        return (string) $this->config->getIndexPrefix($storeId).Mage::app()->getStore($storeId)->getCode();
    }

    public function getIndexName($storeId = null, $getTmpIndexName = false)
    {
        $indexName = (string) $this->getBaseIndexName($storeId).$this->getIndexNameSuffix();

        if ($getTmpIndexName === true) {
            $indexName .= '_tmp';
        }

        return $indexName;
    }

    protected function try_cast($value)
    {
        if (is_numeric($value) && floatval($value) == floatval(intval($value))) {
            return intval($value);
        }

        if (is_numeric($value)) {
            return floatval($value);
        }

        return $value;
    }

    protected function castProductObject(&$productData)
    {
        $nonCastableAttributes = array('sku', 'name', 'description');

        foreach ($productData as $key => &$data) {
            if (in_array($key, $nonCastableAttributes, true) === true) {
                continue;
            }

            $data = $this->try_cast($data);

            if (is_array($data) === false) {
                $data = explode('|', $data);

                if (count($data) == 1) {
                    $data = $data[0];
                    $data = $this->try_cast($data);
                } else {
                    foreach ($data as &$element) {
                        $element = $this->try_cast($element);
                    }
                }
            }
        }
    }

    protected function strip($s, $completeRemoveTags = array())
    {
        if (!empty($completeRemoveTags)) {
            $dom = new DOMDocument();
            if (@$dom->loadHTML($s)) {
                $toRemove = array();
                foreach ($completeRemoveTags as $tag) {
                    $removeTags = $dom->getElementsByTagName($tag);

                    foreach ($removeTags as $item) {
                        $toRemove[] = $item;
                    }
                }

                foreach ($toRemove as $item) {
                    $item->parentNode->removeChild($item);
                }

                $s = $dom->saveHTML();
            }
        }

        $s = trim(preg_replace('/\s+/', ' ', $s));
        $s = preg_replace('/&nbsp;/', ' ', $s);
        $s = preg_replace('!\s+!', ' ', $s);
        $s = preg_replace('/\{\{[^}]+\}\}/', ' ', $s);
        $s = strip_tags($s);
        $s = trim($s);

        return $s;
    }

    public function isCategoryActive($categoryId, $storeId = null)
    {
        $storeId = intval($storeId);
        $categoryId = intval($categoryId);

        if ($path = $this->getCategoryPath($categoryId, $storeId)) {
            // Check whether the specified category is active

            $isActive = true; // Check whether all parent categories for the current category are active
            $parentCategoryIds = explode('/', $path);

            if (count($parentCategoryIds) <= 2) {
                // Exclude root category

                return false;
            }

            array_shift($parentCategoryIds); // Remove root category

            array_pop($parentCategoryIds); // Remove current category as it is already verified

            $parentCategoryIds = array_reverse($parentCategoryIds); // Start from the first parent

            foreach ($parentCategoryIds as $parentCategoryId) {
                if (!($parentCategoryPath = $this->getCategoryPath($parentCategoryId, $storeId))) {
                    $isActive = false;
                    break;
                }
            }

            if ($isActive) {
                return true;
            }
        }

        return false;
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

            /** @var Mage_Catalog_Model_Resource_Category $resource */
            $resource = Mage::getResourceModel('catalog/category');

            if ($attribute = $resource->getAttribute('is_active')) {
                /** @var Mage_Core_Model_Resource $coreResource */
                $coreResource = Mage::getSingleton('core/resource');
                $connection = $coreResource->getConnection('core_read');

                $select = $connection->select()->from(array('backend' => $attribute->getBackendTable()), array(
                        'key' => new Zend_Db_Expr("CONCAT(backend.store_id, '-', backend.entity_id)"),
                        'category.path',
                        'backend.value',
                    ))->join(array('category' => $resource->getTable('catalog/category')),
                        'backend.entity_id = category.entity_id', array())
                                     ->where('backend.entity_type_id = ?', $attribute->getEntityTypeId())
                                     ->where('backend.attribute_id = ?', $attribute->getAttributeId())
                                     ->order('backend.store_id')->order('backend.entity_id');

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

            $resource = Mage::getResourceModel('catalog/category');
            /** @var $resource Mage_Catalog_Model_Resource_Category */
            if ($attribute = $resource->getAttribute('name')) {
                /** @var Mage_Core_Model_Resource $coreResource */
                $coreResource = Mage::getSingleton('core/resource');
                $connection = $coreResource->getConnection('core_read');

                $select = $connection->select()->from(array('backend' => $attribute->getBackendTable()),
                        array(new Zend_Db_Expr("CONCAT(backend.store_id, '-', backend.entity_id)"), 'backend.value'))
                                     ->join(array('category' => $resource->getTable('catalog/category')),
                                         'backend.entity_id = category.entity_id', array())
                                     ->where('backend.entity_type_id = ?', $attribute->getEntityTypeId())
                                     ->where('backend.attribute_id = ?', $attribute->getAttributeId())
                                     ->where('category.level > ?', 1);

                self::$_categoryNames = $connection->fetchPairs($select);
            }
        }

        $categoryName = null;

        $key = $storeId.'-'.$categoryId;

        if (isset(self::$_categoryNames[$key])) {
            // Check whether the category name is present for the specified store

            $categoryName = strval(self::$_categoryNames[$key]);
        } elseif ($storeId != 0) {
            // Check whether the category name is present for the default store

            $key = '0-'.$categoryId;

            if (isset(self::$_categoryNames[$key])) {
                $categoryName = strval(self::$_categoryNames[$key]);
            }
        }

        return $categoryName;
    }

    public static function getStores($store_id)
    {
        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');
        $store_ids = array();

        if ($store_id == null) {
            /** @var Mage_Core_Model_Store $store */
            foreach (Mage::app()->getStores() as $store) {
                if ($config->isEnabledBackend($store->getId()) === false) {
                    continue;
                }

                if ($store->getIsActive()) {
                    $store_ids[] = $store->getId();
                }
            }
        } elseif (is_array($store_id)) {
            return $store_id;
        } else {
            $store_ids = array($store_id);
        }

        return $store_ids;
    }
}
