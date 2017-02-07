<?php

class Algolia_Algoliasearch_Model_Indexer_Algoliapersonalization extends Mage_Index_Model_Indexer_Abstract
{
    const EVENT_MATCH_RESULT_KEY = 'algoliasearch_match_result';

    /** @var Algolia_Algoliasearch_Helper_Config */
    private $configHelper;

    /** @var Algolia_Algoliasearch_Helper_Personalization */
    private $personalizationHelper;

    public function __construct()
    {
        parent::__construct();

        $this->configHelper = Mage::helper('algoliasearch/config');
        $this->personalizationHelper = Mage::helper('algoliasearch/personalization');
    }

    protected $_matchedEntities = array();

    protected function _getResource()
    {
        return Mage::getResourceSingleton('catalogsearch/indexer_fulltext');
    }

    public function getName()
    {
        return Mage::helper('algoliasearch')->__('Algolia Personalization');
    }

    public function getDescription()
    {
        return Mage::helper('algoliasearch')->__('Enterprise-only indexer - use this indexer only when you are on Algolia Enterprise plan. For more information about Enterprise plan, please drop us a line at enterprise@algolia.com');
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
        if (!$this->configHelper->getApplicationID() || !$this->configHelper->getAPIKey() || !$this->configHelper->getSearchOnlyAPIKey()) {
            /** @var Mage_Adminhtml_Model_Session $session */
            $session = Mage::getSingleton('adminhtml/session');
            $session->addError('Algolia reindexing failed: You need to configure your Algolia credentials in System > Configuration > Algolia Search.');

            return $this;
        }

        $this->personalizationHelper->reindex();

        return $this;
    }
}
