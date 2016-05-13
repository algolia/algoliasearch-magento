<?php

class Algolia_Algoliasearch_Model_Resource_Fulltext_Collection extends Mage_CatalogSearch_Model_Resource_Fulltext_Collection
{
    /**
     * Intercept query.
     */
    public function addSearchFilter($query)
    {
        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');

        $storeId = Mage::app()->getStore()->getId();

        if (!$config->getApplicationID() || !$config->getAPIKey() || $config->isEnabledFrontEnd($storeId) === false) {
            return parent::addSearchFilter($query);
        }

        $data = [];

        if ($config->isInstantEnabled($storeId) === false || $config->makeSeoRequest($storeId)) {
            $algolia_query = $query !== '__empty__' ? $query : '';

            try {
                /** @var Algolia_Algoliasearch_Helper_Data $helper */
                $helper = Mage::helper('algoliasearch');
                $data = $helper->getSearchResult($algolia_query, $storeId);
            } catch (\Exception $e) {
                /** @var Algolia_Algoliasearch_Helper_Logger $logger */
                $logger = Mage::helper('algoliasearch/logger');

                $logger->log($e->getMessage(), true);
                $logger->log($e->getTraceAsString(), true);

                return parent::addSearchFilter($query);
            }
        }

        $sortedIds = array_reverse(array_keys($data));

        $this->getSelect()->columns([
            'relevance' => new Zend_Db_Expr("FIND_IN_SET(e.entity_id, '" . implode(',', $sortedIds) . "')"),
        ]);

        $this->getSelect()->where('e.entity_id IN (?)', $sortedIds);

        return $this;
    }
}
