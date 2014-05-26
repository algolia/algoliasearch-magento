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
            $answer = Mage::helper('algoliasearch')->query(Mage::helper('algoliasearch')->getIndexName(Mage::app()->getStore()->getId()), $queryText, array(
                'hitsPerPage' => 1000, // retrieve all the hits (hard limit is 1000)
                'attributesToRetrieve' => array(),
                'tagFilters' => 'product'
            ));

            $i = 0;
            foreach ($answer['hits'] as $hit) {
                $objectIdParts = preg_split('/_/', $hit['objectID']);
                $productId = isset($objectIdParts[2]) ? $objectIdParts[2] : NULL;
                if ($productId) {
                    $sql = sprintf("INSERT INTO `" . $this->getTable('catalogsearch/result') . "`"
                        . " (`query_id`, `product_id`, `relevance`) VALUES"
                        . " (%d, %d, %d)"
                        . " ON DUPLICATE KEY UPDATE `relevance`=VALUES(`relevance`)",
                        $query->getId(),
                        $productId,
                        1000 - $i // relevance based on position
                    );
                    $adapter->query($sql);
                    ++$i;
                }
            }

            $query->setIsProcessed(1);
        }

        return $this;
    }
}
