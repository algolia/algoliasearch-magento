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
