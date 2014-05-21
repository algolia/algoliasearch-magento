<?php

class Algolia_Algoliasearch_Adminhtml_AlgoliasearchbackendController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_title($this->__('Algolia | Reindex catalog'));
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

    public function reindexAction()
    {
        try {
            Mage::helper('algoliasearch')->reindexAll();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $this->_redirect('*/*');
    }
}
