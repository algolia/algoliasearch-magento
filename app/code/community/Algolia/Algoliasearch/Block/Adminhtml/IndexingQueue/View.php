<?php

class Algolia_Algoliasearch_Block_Adminhtml_IndexingQueue_View extends Mage_Adminhtml_Block_Widget_View_Container
{
    /**
     * Internal constructor.
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_blockGroup = 'algoliasearch';
        $this->_controller = 'adminhtml_indexingqueue';

        $this->_removeButton('edit');
    }

    /**
     * Get header text.
     *
     * @return string
     */
    public function getHeaderText()
    {
        return Mage::helper('algoliasearch')->__('Algolia Search - Indexing Queue Job #%s',
            Mage::registry('algoliasearch_indexingqueue_job')->getId());
    }

    /**
     * Set custom Algolia icon class.
     *
     * @return string
     */
    public function getHeaderCssClass()
    {
        return 'icon-head algoliasearch-head-icon';
    }
}