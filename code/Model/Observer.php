<?php
class Algolia_Algoliasearch_Model_Observer
{
	public function saveProduct(Varien_Event_Observer $observer)
	{
		$product = $observer->getProduct();
		$index = Mage::helper('algoliasearch')->getIndex('magento_products');
		foreach ($product->getStoreIds() as $storeId) {
			$prod = Mage::getModel('catalog/product')->setStoreId($storeId)->load($product->getId());
			$index->addObject(Mage::helper('algoliasearch')->getProductJSON($prod));
		}
	}

	public function saveCategory(Varien_Event_Observer $observer)
	{
		$category = $observer->getCategory();
		$index = Mage::helper('algoliasearch')->getIndex('magento_categories');
		foreach ($category->getStoreIds() as $storeId) {
			$cat = Mage::getModel('catalog/category')->setStoreId($storeId)->load($category->getId());
			$index->addObject(Mage::helper('algoliasearch')->getCategoryJSON($cat));
		}
	}
}
