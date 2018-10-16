<?php

class Algolia_Algoliasearch_Block_Adminhtml_IndexingQueue extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Initialize Grid Container
     */
    public function __construct()
    {
        $this->_blockGroup = 'algoliasearch';
        $this->_controller = 'adminhtml_indexingqueue';

        parent::__construct();

        $this->_removeButton('add');
        $this->_addButton('clear_queue', array(
            'label' => Mage::helper('algoliasearch')->__('Clear Queue'),
            'onclick' => "location.href='" . $this->getUrl('*/*/clear') . "'",
            'class' => 'cancel',
        ));
    }

    /**
     * Get header text.
     *
     * @return string
     */
    public function getHeaderText()
    {
        return Mage::helper('algoliasearch')->__('Algolia Search - Indexing Queue');
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