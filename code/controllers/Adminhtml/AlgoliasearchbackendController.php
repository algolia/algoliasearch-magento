<?php

class Algolia_Algoliasearch_Adminhtml_AlgoliasearchbackendController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_title($this->__('Algolia | Reindex Catalog'));
        $this->renderLayout();
    }

    public function reindexAction()
    {
        try {
            Mage::helper('algoliasearch')->reindexAll();
            $this->_getSession()->addSuccess($this->__('The catalog has been successfully reindexed.'));
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('An error occurred while reindexing the catalog: "%s"', $e->getMessage()));
        }

        $this->_redirect('*/*');
    }
}
