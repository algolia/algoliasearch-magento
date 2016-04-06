<?php

class Algolia_Algoliasearch_Helper_Entity_Suggestionhelper extends Algolia_Algoliasearch_Helper_Entity_Helper
{
    protected $_popularQueries = null;
    protected $_popularQueriesCacheId = "algoliasearch_popular_queries_cache_tag";

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

    public function getPopularQueries($storeId)
    {
        if ($this->_popularQueries == null) {

            // load from cache if we can
            $cachedPopularQueries = Mage::app()->loadCache($this->_popularQueriesCacheId);
            if ($cachedPopularQueries) {
                $this->_popularQueries = unserialize($cachedPopularQueries);
            } else {
                $collection = Mage::getResourceModel('catalogsearch/query_collection');
                $collection->getSelect()->where('num_results >= ' . $this->config->getMinNumberOfResults() . ' AND popularity >= ' . $this->config->getMinPopularity() . ' AND query_text != "__empty__"');
                $collection->getSelect()->limit(12);
                $collection->setOrder('popularity', 'DESC');
                $collection->setOrder('num_results', 'DESC');
                $collection->setOrder('updated_at', 'ASC');

                if ($storeId) {
                    $collection->getSelect()->where('store_id = ?', (int)$storeId);
                }

                $collection->load();

                $suggestions = array();

                /** @var $suggestion Mage_Catalog_Model_Category */
                foreach ($collection as $suggestion)
                    if (strlen($suggestion['query_text']) >= 3)
                        $suggestions[] = $suggestion['query_text'];

                $this->_popularQueries = array_slice($suggestions, 0, 9);
                try { //save to cache
                    $cacheContent = serialize($this->_popularQueries);
                    $tags = array(
                        Mage_CatalogSearch_Model_Query::CACHE_TAG
                    );

                    Mage::app()->saveCache($cacheContent, $this->_popularQueriesCacheId, $tags, 604800);

                } catch (Exception $e) {
                    // Exception = no caching
                    Mage::logException($e);
                }
            }
        }

        return $this->_popularQueries;
    }

    public function getSuggestionCollectionQuery($storeId)
    {
        $collection = Mage::getResourceModel('catalogsearch/query_collection')
                            ->addStoreFilter($storeId)
                            ->setStoreId($storeId);

        $collection->getSelect()->where('num_results >= '.$this->config->getMinNumberOfResults().' AND popularity >= ' . $this->config->getMinPopularity() .' AND query_text != "__empty__"');

        return $collection;
    }
}