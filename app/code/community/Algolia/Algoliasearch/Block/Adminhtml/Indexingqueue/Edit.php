<?php

class Algolia_Algoliasearch_Block_Adminhtml_Indexingqueue_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Internal constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'job_id';
        $this->_blockGroup = 'algoliasearch';
        $this->_controller = 'adminhtml_indexingqueue';

        $this->_removeButton('save');
        $this->_removeButton('reset');
        $this->_removeButton('delete');
    }

    /**
     * Get header text.
     *
     * @return string
     */
    public function getHeaderText()
    {
        return Mage::helper('algoliasearch')->__('Algolia Search - Indexing Queue Job #%s',
            Mage::registry('algoliasearch_current_job')->getJobId());
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
