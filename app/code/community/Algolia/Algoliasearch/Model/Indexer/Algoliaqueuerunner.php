<?php

class Algolia_Algoliasearch_Model_Indexer_Algoliaqueuerunner extends Mage_Index_Model_Indexer_Abstract
{
    const EVENT_MATCH_RESULT_KEY = 'algoliasearch_match_result';

    /** @var Algolia_Algoliasearch_Helper_Config */
    protected $config;

    /** @var Algolia_Algoliasearch_Model_Queue */
    protected $queue;

    public function __construct()
    {
        parent::__construct();
        $this->config = Mage::helper('algoliasearch/config');
        $this->queue = Mage::getSingleton('algoliasearch/queue');
    }

    protected $_matchedEntities = array();

    protected function _getResource()
    {
        return Mage::getResourceSingleton('catalogsearch/indexer_fulltext');
    }

    public function getName()
    {
        return Mage::helper('algoliasearch')->__('Algolia Search Queue Runner');
    }

    public function getDescription()
    {
        return Mage::helper('algoliasearch')->__('Process the queue if enabled. This allow to run jobs in the queue');
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
        if (!$this->config->getApplicationID() || !$this->config->getAPIKey() || !$this->config->getSearchOnlyAPIKey()) {
            /** @var Mage_Adminhtml_Model_Session $session */
            $session = Mage::getSingleton('adminhtml/session');
            $session->addError('Algolia reindexing failed: You need to configure your Algolia credentials in System > Configuration > Algolia Search.');

            return $this;
        }

        $this->queue->runCron();

        return $this;
    }
}
