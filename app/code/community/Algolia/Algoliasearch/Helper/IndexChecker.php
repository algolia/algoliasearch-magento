<?php

class Algolia_Algoliasearch_Helper_IndexChecker extends Mage_Core_Helper_Abstract
{
    /** @var Algolia_Algoliasearch_Helper_Config */
    private $configHelper;
    
    /** @var Varien_Db_Adapter_Interface */
    private $dbConnection;
    
    /** @var array */
    private $pendingProductIds;

    public function __construct()
    {
        /** @var Algolia_Algoliasearch_Helper_Config configHelper */
        $this->configHelper = Mage::helper('algoliasearch/config');
    }

    /**
     * @param $storeId
     * @param $productIds
     * @throws Exception
     */
    public function checkIndexers($storeId, $productIds)
    {
        if ($this->configHelper->isQueueActive($storeId) === false) {
            return;
        }

        if (!is_array($productIds)) {
            $productIds = array($productIds);
        }

        if (empty($productIds)) {
            return;
        }

        $pendingProductIds = $this->getPendingProductIds($storeId);

        if (empty($pendingProductIds)) {
            return;
        }

        foreach ($productIds as $id) {
            if (isset($this->pendingProductIds[$id])) {
                // Throw an exception - this exception is caught in Queue class and it'll retry next time Algolia is processed.
                throw new Algolia_Algoliasearch_Model_Exception_IndexPendingException('Reindexing (price / stock) is still pending for entity ID ' . $id);
            }
        }
    }

    private function getPendingProductIds($storeId)
    {
        if (isset($this->pendingProductIds) === false) {
            $priceIndexes = $this->pendingPriceIndex($storeId);
            $stockIndexes = $this->pendingStockIndex($storeId);

            $pendingProductIds = array_unique(array_merge($priceIndexes, $stockIndexes));
            $this->pendingProductIds = array_flip($pendingProductIds);
        }

        return $this->pendingProductIds;
    }

    /**
     * Find all productId's pending a price reindex.
     *
     * @param $storeId
     * @return array
     */
    private function pendingPriceIndex($storeId)
    {
        $returnArray = array();

        if ($this->configHelper->shouldCheckPriceIndex($storeId)) {
            $maxVersion = $this->findLatestVersion('catalog_product_index_price_cl');
            $returnArray = $this->findProductIdsPending('catalog_product_index_price_cl', 'entity_id', $maxVersion);
        }

        return $returnArray;
    }

    /**
     * Find all productId's pending a stock reindex.
     *
     * @param $storeId
     * @return array
     */
    private function pendingStockIndex($storeId)
    {
        $returnArray = array();

        if ($this->configHelper->shouldCheckStockIndex($storeId)) {
            $maxVersion = $this->findLatestVersion('cataloginventory_stock_status_cl');
            $returnArray = $this->findProductIdsPending('cataloginventory_stock_status_cl', 'product_id', $maxVersion);
        }

        return $returnArray;
    }

    /**
     * Find all product ID's who have a version ID greater then the maxVersion.
     *
     * @param $changeLogTable
     * @param $idColumn
     * @param $maxVersion
     *
     * @return mixed - array of ID's
     */
    private function findProductIdsPending($changeLogTable, $idColumn, $maxVersion)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
                             ->distinct()
                             ->from($changeLogTable, $idColumn)
                             ->join(array('catalog_product_entity' => 'catalog_product_entity'), "$changeLogTable.$idColumn = catalog_product_entity.entity_id", array())
                             ->where('version_id > ?', $maxVersion);

        return $connection->fetchCol($select);
    }

    /**
     * Find the latest version ID for a particular changeLog
     *
     * @param $changeLogTable
     * @return mixed
     */
    private function findLatestVersion($changeLogTable)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
                             ->from('enterprise_mview_metadata', 'version_id')
                             ->where('changelog_name = ?', $changeLogTable);

        return $connection->fetchOne($select);
    }

    private function getConnection()
    {
        if (isset($this->dbConnection) === false) {
            /** @var Mage_Core_Model_Resource $coreResource */
            $coreResource = Mage::getSingleton('core/resource');
            $this->dbConnection = $coreResource->getConnection('core_read');
        }

        return $this->dbConnection;
    }
}
