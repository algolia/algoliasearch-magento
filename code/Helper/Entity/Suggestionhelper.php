<?php

class Algolia_Algoliasearch_Helper_Entity_Suggestionhelper extends Algolia_Algoliasearch_Helper_Entity_Helper
{
    protected function getIndexNameSuffix()
    {
        return '_suggestions';
    }

    public function getIndexSettings($storeId)
    {
        return array(
            'attributesToIndex'         => array('query'),
            'customRanking'             => array('desc(popularity)', 'desc(number_of_results)', 'asc(date)'),
            'typoTolerance'             => false,
            'attributesToRetrieve'      => array('query')
        );
    }

    public function getObject(Mage_CatalogSearch_Model_Query $suggestion)
    {
        $suggestion_obj = array(
            'objectID'              => $suggestion->getData('query_id'),
            'query'                 => $suggestion->getData('query_text'),
            'number_of_results'     => (int) $suggestion->getData('num_results'),
            'popularity'            => (int) $suggestion->getData('popularity'),
            'updated_at'            => (int) strtotime($suggestion->getData('updated_at')),
        );

        return $suggestion_obj;
    }

    public function getSuggestionCollectionQuery($storeId)
    {
        $collection = Mage::getResourceModel('catalogsearch/query_collection')
                            ->addStoreFilter($storeId)
                            ->setStoreId($storeId);

        return $collection;
    }
}