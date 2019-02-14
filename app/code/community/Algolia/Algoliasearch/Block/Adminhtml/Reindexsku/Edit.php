<?php

class Algolia_Algoliasearch_Block_Adminhtml_Reindexsku_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Internal constructor.
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_objectId = 'sku';
        $this->_blockGroup = 'algoliasearch';
        $this->_controller = 'adminhtml_reindexsku';
    }

    /**
     * Get header text.
     *
     * @return string
     */
    public function getHeaderText()
    {
        return Mage::helper('algoliasearch')->__('Algolia Search - Reindex SKU(s)');
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
