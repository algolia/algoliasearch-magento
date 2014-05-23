<?php  

class Algolia_Algoliasearch_Block_Adminhtml_Algoliasearchbackend extends Mage_Adminhtml_Block_Template
{
    protected $_nbEntitiesIndexed = 0;

    protected function _construct()
    {
        parent::_construct();
        $this->init();
    }

    public function init()
    {
        $this->_nbEntitiesIndexed = 0;
        $indexes = Mage::helper('algoliasearch')->listIndexes();
        foreach ($indexes['items'] as $index) {
            $this->_nbEntitiesIndexed += intval($index['entries']);
        }
    }

    public function getNbEntitiesIndexed()
    {
        return (int) $this->_nbEntitiesIndexed;
    }

    public function getApplicationId()
    {
        return Mage::helper('algoliasearch')->getApplicationID();
    }
}
