<?php

require 'algoliasearch.php';

class Algolia_Algoliasearch_Helper_Data extends Mage_Core_Helper_Abstract {

  public function getIndex($name) {
    return $this->getClient()->initIndex($name);
  }

  public function listIndexes() {
    return $this->getClient()->listIndexes();
  }

  public function query($index, $q, $params) {
    return $this->getClient()->initIndex($index)->search($q, $params);
  }

  public function getProductJSON($prod) {
    $product = Mage::getModel('catalog/product')->load($prod->getId());
    $categories = array();
    foreach ($product->getCategoryIds() as $catId) {
      array_push($categories, Mage::getModel('catalog/category')->load($catId)->getName());
    }
    return array(
      'objectID' => $product->getId(),
      'name' => $product->getName(),
      'categories' => $categories,
      'description' => $product->getDescription(),
      'price' => $product->getPrice(),
      'final_price' => $product->getFinalPrice(),
      'url' => $product->getUrlInStore(),
      //'thumbnail_url' => $product->getThumbnailUrl(),
      //'image_url' => $product->getImageUrl()
    );
  }

  public function getCategoryJSON($id) {
    $cat = Mage::getModel('catalog/category'); 
    $cat->load($id);
    $path = '';
    foreach ($cat->getPathIds() as $catId) {
      if ($path != '') {
        $path .= ' / ';
      }
      $path .= Mage::getModel('catalog/category')->load($catId)->getName();
    }
    return array(
      'objectID' => $id,
      'name' => $cat->getName(),
      'path' => $path,
      'level' => $cat->getLevel(),
      'url' => $cat->getUrl(),
      'product_count' => $cat->getProductCount(),
      //'image_url' => $cat->getImageUrl()
    );
  }

  private function getClient() {
    return new \AlgoliaSearch\Client($this->getApplicationID(), $this->getAPIKey());
  }

  public function getApplicationID() {
    return Mage::getStoreConfig('algoliasearch/settings/application_id');
  }

  public function getAPIKey() {
    return Mage::getStoreConfig('algoliasearch/settings/api_key');
  }

  public function getSearchOnlyAPIKey() {
    return Mage::getStoreConfig('algoliasearch/settings/search_only_api_key');
  }

}
