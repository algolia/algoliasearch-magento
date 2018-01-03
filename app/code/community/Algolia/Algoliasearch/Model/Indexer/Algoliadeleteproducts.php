<?php

class Algolia_Algoliasearch_Model_Indexer_Algoliadeleteproducts extends Algolia_Algoliasearch_Model_Indexer_Abstract
{
    /** @var Algolia_Algoliasearch_Model_Resource_Engine */
    protected $engine;

    /** @var Algolia_Algoliasearch_Helper_Config */
    protected $config;

    public function __construct()
    {
        parent::__construct();

        $this->engine = new Algolia_Algoliasearch_Model_Resource_Engine();
        $this->config = Mage::helper('algoliasearch/config');
    }

    public function getName()
    {
        return Mage::helper('algoliasearch')->__('Algolia Search - Delete inactive products');
    }

    public function getDescription()
    {
        /** @var Algolia_Algoliasearch_Helper_Data $helper */
        $helper = Mage::helper('algoliasearch');
        $decription = $helper->__('Run this indexer only when you want to remove inactive / deleted products from Algolia.');

        return $decription;
    }

    public function matchEvent(Mage_Index_Model_Event $event)
    {
        return false;
    }

    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        return $this;
    }

    protected function _registerCatalogProductEvent(Mage_Index_Model_Event $event)
    {
        return $this;
    }

    protected function _registerCatalogCategoryEvent(Mage_Index_Model_Event $event)
    {
        return $this;
    }

    protected function _processEvent(Mage_Index_Model_Event $event)
    {
    }

    /**
     * Rebuild all index data.
     */
    public function reindexAll()
    {
        if ($this->config->isModuleOutputEnabled() === false) {
            return $this;
        }

        if (!$this->config->getApplicationID() || !$this->config->getAPIKey() || !$this->config->getSearchOnlyAPIKey()) {
            /** @var Mage_Adminhtml_Model_Session $session */
            $session = Mage::getSingleton('adminhtml/session');
            $session->addError('Algolia reindexing failed: You need to configure your Algolia credentials in System > Configuration > Algolia Search.');

            return $this;
        }

        $this->engine->deleteInactiveProducts();

        return $this;
    }
}
