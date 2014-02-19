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

  public function reindexAction() {
    // categories
    $index_categories = Mage::helper('algoliasearch')->getIndex('magento_categories');
    $index_categories->setSettings(array(
      "attributesToIndex" => array("name", "path"),
      "customRanking" => array("desc(product_count)")
    ));
    $tree =  Mage::getModel('catalog/category')->getTreeModel();
    $tree->load();
    $ids = $tree->getCollection()->getAllIds(); 
    if ($ids) { 
      foreach ($ids as $id) { 
        $cat = Mage::getModel('catalog/category'); 
        $cat->load($id);
        $path = '';
        foreach ($cat->getPathIds() as $catId) {
          if ($path != '') {
            $path .= ' / ';
          }
          $path .= Mage::getModel('catalog/category')->load($catId)->getName();
        }
        $index_categories->addObject(array(
          'objectID' => $id,
          'name' => $cat->getName(),
          'path' => $path,
          'level' => $cat->getLevel(),
          'url' => $cat->getUrl(),
          'product_count' => $cat->getProductCount(),
          //'image_url' => $cat->getImageUrl()
        ));
      } 
    }

    // products
    $index_products   = Mage::helper('algoliasearch')->getIndex('magento_products');
    $products = Mage::getModel('catalog/product')->getCollection();
    foreach ($products as $prod) {
      $product = Mage::getModel('catalog/product')->load($prod->getId());
      $categories = array();
      foreach ($product->getCategoryIds() as $catId) {
        array_push($categories, Mage::getModel('catalog/category')->load($catId)->getName());
      }
      $json = array(
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
      $index_products->addObject($json);
    }

    $this->_redirect('*/*');
  }
}
