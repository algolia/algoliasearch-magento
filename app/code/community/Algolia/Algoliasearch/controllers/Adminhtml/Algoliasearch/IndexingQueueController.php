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
        $this->_title($this->__('System'))
            ->_title($this->__('Algolia Search'))
            ->_title($this->__('Indexing Queue'));

        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('algoliasearch')->__('Indexing Queue Job ID is not set.'));
            $this->_redirect('*/*/');
            return;
        }

        $job = Mage::getModel('algoliasearch/job')->load($id);
        if (!$job->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('algoliasearch')->__('This indexing queue job no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }

        Mage::register('algoliasearch_indexingqueue_job', $job);

        $this->loadLayout();
        $this->_setActiveMenu('system/algolia/indexing_queue');
        $this->renderLayout();
    }

    public function clearAction()
    {
        try {
            /** @var Algolia_Algoliasearch_Model_Queue $queue */
            $queue = Mage::getModel('algoliasearch/queue');
            $queue->clearQueue(true);

            Mage::getSingleton('adminhtml/session')->addSuccess('Indexing Queue has been cleared.');
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
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
