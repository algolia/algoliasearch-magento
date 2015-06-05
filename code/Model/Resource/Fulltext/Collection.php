<?php

class Algolia_Algoliasearch_Model_Resource_Fulltext_Collection extends Mage_CatalogSearch_Model_Resource_Fulltext_Collection
{
    /**
     * Intercept query
     */
    public function addSearchFilter($query)
    {
        $config = new Algolia_Algoliasearch_Helper_Config();

        if ($config->isInstantEnabled())
        {
            $product_helper = new Algolia_Algoliasearch_Helper_Entity_Producthelper();

            $url = Mage::getBaseUrl().'#q='.$query.'&page=0&refinements=%5B%5D&numerics_refinements=%7B%7D&index_name=%22'.$product_helper->getIndexName().'%22';

            header('Location: '.$url);

            die();
        }

        $data = Mage::helper('algoliasearch')->getSearchResult($query, Mage::app()->getStore()->getId());

        $sortedIds = array_reverse(array_keys($data));

        $this->getSelect()->columns(array('relevance' => new Zend_Db_Expr("FIND_IN_SET(e.entity_id, '".implode(',',$sortedIds)."')")));
        $this->getSelect()->where('e.entity_id IN (?)', $sortedIds);

        return $this;
    }
}