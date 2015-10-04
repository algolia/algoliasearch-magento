<?php

class Algolia_Algoliasearch_Model_Resource_Fulltext_Collection extends Mage_CatalogSearch_Model_Resource_Fulltext_Collection
{
    /**
     * Intercept query
     */
    public function addSearchFilter($query)
    {
        $storeId = Mage::app()->getStore()->getId();
        $config = Mage::helper('algoliasearch/config');

        if ($config->isEnabledFrontEnd($storeId) === false)
            return parent::addSearchFilter($query);

        $data = array();

        if ($config->isInstantEnabled($storeId) === false || $config->makeSeoRequest($storeId))
        {
            $algolia_query = $query !== '__empty__' ? $query : '';
            $data = Mage::helper('algoliasearch')->getSearchResult($algolia_query, $storeId);
        }


        $sortedIds = array_reverse(array_keys($data));

        $this->getSelect()->columns(array('relevance' => new Zend_Db_Expr("FIND_IN_SET(e.entity_id, '".implode(',',$sortedIds)."')")));
        $this->getSelect()->where('e.entity_id IN (?)', $sortedIds);

        return $this;
    }
}