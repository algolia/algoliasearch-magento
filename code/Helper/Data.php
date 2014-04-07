<?php

require 'algoliasearch.php';

class Algolia_Algoliasearch_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getTopSearchTemplate()
    {
        return 'algoliasearch/topsearch.phtml';
    }

    public function getIndex($name)
    {
        return $this->getClient()->initIndex($name);
    }

    public function listIndexes()
    {
        return $this->getClient()->listIndexes();
    }

    public function query($index, $q, $params)
    {
        return $this->getClient()->initIndex($index)->search($q, $params);
    }

    public function getProductJSON($product)
    {
        $categories = array();
        foreach ($product->getCategoryIds() as $catId) {
            array_push($categories, Mage::getModel('catalog/category')->setStoreId($product->getStoreId())->load($catId)->getName());
        }
        $imageUrl = null;
        $thumbnailUrl = null;
        try {
            $thumbnailUrl = $product->getThumbnailUrl();
        } catch (Exception $e) { /* no thumbnail, no default: not fatal */ }
        try {
            $imageUrl = $product->getImageUrl();
        } catch (Exception $e) { /* no image, no default: not fatal */ }
        return array(
            'objectID' => $product->getStoreId() . '_' . $product->getId(),
            'name' => $product->getName(),
            'categories' => $categories,
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'url' => $product->getUrlInStore(),
            '_tags' => array("store_" . $product->getStoreId()),
            'thumbnail_url' => $thumbnailUrl,
            'image_url' => $imageUrl
        );
    }

    public function getCategoryJSON($cat)
    {
        $path = '';
        foreach ($cat->getPathIds() as $catId) {
            if ($path != '') {
                $path .= ' / ';
            }
            $path .= Mage::getModel('catalog/category')->setStoreId($cat->getStoreId())->load($catId)->getName();
        }
        $imageUrl = null;
        try {
            $imageUrl = $cat->getImageUrl();
        } catch (Exception $e) { /* no image, no default: not fatal */ }
        return array(
            'objectID' => $cat->getStoreId() . '_' . $cat->getId(),
            'name' => $cat->getName(),
            'path' => $path,
            'level' => $cat->getLevel(),
            'url' => $cat->getUrl(),
            'product_count' => $cat->getProductCount(),
            '_tags' => array("store_" . $cat->getStoreId()),
            'image_url' => $imageUrl
        );
    }

    private function getClient()
    {
        return new \AlgoliaSearch\Client($this->getApplicationID(), $this->getAPIKey());
    }

    public function getApplicationID()
    {
        return Mage::getStoreConfig('algoliasearch/settings/application_id');
    }

    public function getAPIKey()
    {
        return Mage::getStoreConfig('algoliasearch/settings/api_key');
    }

    public function getSearchOnlyAPIKey()
    {
        return Mage::getStoreConfig('algoliasearch/settings/search_only_api_key');
    }

    public function getNbProductSuggestions()
    {
        return Mage::getStoreConfig('algoliasearch/ui/number_sugggestions_product');
    }

    public function getNbCategorySuggestions()
    {
        return Mage::getStoreConfig('algoliasearch/ui/number_sugggestions_category');
    }

}
