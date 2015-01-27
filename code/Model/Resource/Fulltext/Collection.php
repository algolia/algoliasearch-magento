<?php

class Algolia_Algoliasearch_Model_Resource_Fulltext_Collection extends Mage_CatalogSearch_Model_Resource_Fulltext_Collection
{

    /**
     * Add search query filter without preparing result since result table causes lots of lock contention.
     *
     * @param string $query
     * @throws Exception
     * @return Mage_CatalogSearch_Model_Resource_Fulltext_Collection
     */
    public function addSearchFilter($query)
    {
        if ( ! Mage::helper('algoliasearch')->isEnabled() || Mage::helper('algoliasearch')->useResultCache()) {
            return parent::addSearchFilter($query);
        }

        // This method of filtering the product collection by the search result does not use the catalogsearch_result table
        try {
            $data = Mage::helper('algoliasearch')->getSearchResult($query, Mage::app()->getStore()->getId());
        } catch (Exception $e) {
            Mage::getSingleton('catalog/session')->addError(Mage::helper('algoliasearch')->__('Search failed. Please try again.'));
            $this->getSelect()->columns(['relevance' => new Zend_Db_Expr("e.entity_id")]);
            $this->getSelect()->where('e.entity_id = 0');
            return $this;
        }

        $sortedIds = array_reverse(array_keys($data));
        $this->getSelect()->columns(['relevance' => new Zend_Db_Expr("FIND_IN_SET(e.entity_id, '".implode(',',$sortedIds)."')")]);
        $this->getSelect()->where('e.entity_id IN (?)', $sortedIds);

        return $this;
    }

}
