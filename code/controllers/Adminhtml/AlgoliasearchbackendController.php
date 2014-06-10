<?php

class Algolia_Algoliasearch_Adminhtml_AlgoliasearchbackendController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_title($this->__('Algolia | Reindex catalog'));
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
