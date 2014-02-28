<?php

class Algolia_Algoliasearch_Model_Resource_Fulltext extends Mage_CatalogSearch_Model_Resource_Fulltext
{
    /**
     * Prepare results for query
     *
     * @param Mage_CatalogSearch_Model_Fulltext $object
     * @param string $queryText
     * @param Mage_CatalogSearch_Model_Query $query
     * @return Mage_CatalogSearch_Model_Resource_Fulltext
     */
    public function prepareResult($object, $queryText, $query)
    {
        $adapter = $this->_getWriteAdapter();

        if (!$query->getIsProcessed() || true) {
            $answer = Mage::helper('algoliasearch')->query('magento_products', $queryText,
                array('hitsPerPage' => 1000, 'attributesToRetrieve' => array())
            );

            $i = 0;
            foreach ($answer['hits'] as $hit) {
                $sql = sprintf("INSERT INTO `" . $this->getTable('catalogsearch/result') . "`"
                    . " (`query_id`, `product_id`, `relevance`) VALUES"
                    . " (%d, %d, %d)"
                    . " ON DUPLICATE KEY UPDATE `relevance`=VALUES(`relevance`)",
                $query->getId(),
                $hit['objectID'],
                1000 - $i
                );
                $adapter->query($sql);
                ++$i;
            }

            $query->setIsProcessed(1);
        }

        return $this;
    }
}
