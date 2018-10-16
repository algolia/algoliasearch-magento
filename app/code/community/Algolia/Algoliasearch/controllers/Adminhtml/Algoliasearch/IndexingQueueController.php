<?php

class Algolia_Algoliasearch_Adminhtml_Algoliasearch_IndexingQueueController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Controller predispatch method
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    public function preDispatch()
    {
        $this->_checkQueueIsActivated();
        return parent::preDispatch();
    }

    public function indexAction()
    {
        $this->_title($this->__('System'))
            ->_title($this->__('Algolia Search'))
            ->_title($this->__('Indexing Queue'));

        $this->loadLayout();
        $this->_setActiveMenu('system/algolia/indexing_queue');
        $this->renderLayout();
    }

    public function viewAction()
    {

    }

    public function clearAction()
    {

    }

    protected function _checkQueueIsActivated()
    {
        if (!Mage::helper('algoliasearch/config')->isQueueActive()) {
            Mage::getSingleton('adminhtml/session')->addWarning(
                $this->__('The indexing queue is not activated. Please activate it in the <a href="%s">Algolia configuration</a>.',
                    $this->getUrl('adminhtml/system_config/edit/section/algoliasearch')));
        }
    }

    /**
     * Check ACL permissions.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/algoliasearch/indexing_queue');
    }
}