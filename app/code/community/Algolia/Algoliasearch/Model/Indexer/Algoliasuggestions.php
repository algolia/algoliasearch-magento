<?php

class Algolia_Algoliasearch_Model_Indexer_Algoliasuggestions extends Algolia_Algoliasearch_Model_Indexer_Abstract
{
    const EVENT_MATCH_RESULT_KEY = 'algoliasearch_match_result';

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

    protected $_matchedEntities = array();

    protected function _getResource()
    {
        return Mage::getResourceSingleton('catalogsearch/indexer_fulltext');
    }

    public function getName()
    {
        return Mage::helper('algoliasearch')->__('Algolia Search Suggestions');
    }

    public function getDescription()
    {
        /** @var Algolia_Algoliasearch_Helper_Data $helper */
        $helper = Mage::helper('algoliasearch');
        $decription = $helper->__('Rebuild suggestions.').' '.$helper->__($this->enableQueueMsg);

        return $decription;
    }

    public function matchEvent(Mage_Index_Model_Event $event)
    {
        $result = false;

        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, $result);

        return $result;
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

        $this->engine->rebuildSuggestions();

        return $this;
    }
}
