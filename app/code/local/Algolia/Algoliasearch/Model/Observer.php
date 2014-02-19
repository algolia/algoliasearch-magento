<?php
class Algolia_Algoliasearch_Model_Observer {

			public function saveProduct(Varien_Event_Observer $observer) {
				$product = $observer->getProduct();
				$index = Mage::helper('algoliasearch')->getIndex('magento_products');
				$index->addObject(Mage::helper('algoliasearch')->getProductJSON($product));
			}
		
			public function saveCategory(Varien_Event_Observer $observer) {
				$category = $observer->getCategory();
				$index = Mage::helper('algoliasearch')->getIndex('magento_categories');
				$index->addObject(Mage::helper('algoliasearch')->getCategoryJSON($category->getId()));
			}

}
