<?php

class Algolia_Algoliasearch_Adminhtml_AlgoliasearchbackendController extends Mage_Adminhtml_Controller_Action {
	public function indexAction() {
    $this->loadLayout();
    $this->_title($this->__("Algolia | Reindex catalog"));
    $nb_products_indexed = 0;
    $nb_categories_indexed = 0;
    $indexes = Mage::helper('algoliasearch')->listIndexes();
    foreach ($indexes['items'] as $index) {
      if ($index['name'] == 'magento_products') {
        $nb_products_indexed = $index['entries'];
      } else if ($index['name'] == 'magento_categories') {
        $nb_categories_indexed = $index['entries'];
      }
    }
    $block = $this->getLayout()->getBlock('algoliasearchbackend');
    $block->setData('nb_products_indexed', $nb_products_indexed);
    $block->setData('nb_categories_indexed', $nb_categories_indexed);
    $block->setData('application_id', Mage::helper('algoliasearch')->getApplicationID());
    $this->renderLayout();
  }

  const BATCH_SIZE = 100;

  public function reindexAction() {
    // categories
    $index_categories = Mage::helper('algoliasearch')->getIndex('magento_categories');
    $index_categories->setSettings(array(
      "attributesToIndex" => array("name", "path"),
      "customRanking" => array("desc(product_count)")
    ));

    // products
    $index_products = Mage::helper('algoliasearch')->getIndex('magento_products');
    $index_products->setSettings(array(
      "attributesToIndex" => array('name', 'categories', 'unordered(description)')
    ));

    foreach (Mage::app()->getStores() as $store) {
      $tree =  Mage::getModel('catalog/category')->setStoreId($store->getId())->getTreeModel();
      $tree->load();
      $ids = $tree->getCollection()->getAllIds(); 
      if ($ids) { 
        $categories = array();
        foreach ($ids as $id) { 
          $cat = Mage::getModel('catalog/category')->setStoreId($store->getId())->load($id);
          array_push($categories, Mage::helper('algoliasearch')->getCategoryJSON($cat));
          if (count($categories) >= self::BATCH_SIZE) {
            $index_categories->addObjects($categories);
            $categories = array();
          }
        }
        if (count($categories) > 0) {
          $index_categories->addObjects($categories);
        }
      }

      $collection = Mage::getModel('catalog/product')->setStoreId($store->getId())->getCollection();
      $products = array();
      foreach ($collection as $prod) {
        array_push($products, Mage::helper('algoliasearch')->getProductJSON(Mage::getModel('catalog/product')->setStoreId($store->getId())->load($prod->getId())));
        if (count($products) >= self::BATCH_SIZE) {
          $index_products->addObjects($products);
          $products = array();
        }
      }
      if (count($products) > 0) {
        $index_products->addObjects($products);
      }
    }

    $this->_redirect('*/*');
  }
}
