<?php

use PHPUnit\Framework\TestCase;

class AbstractIndexingTestCase extends TestCase
{
    /** @var Algolia_Algoliasearch_Helper_Algoliahelper */
    protected $algoliaHelper;

    public function setUp()
    {
        $this->algoliaHelper = Mage::helper('algoliasearch/algoliahelper');

        setConfig('algoliasearch/queue/active', '0');
    }

    protected function processTest(Algolia_Algoliasearch_Model_Indexer_Abstract $indexer, $indexSuffix, $expectedNbHits, $expectedNbHitsFrench = null, $expectedNbHitsGerman = null)
    {
        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');
        $indexPrefix = $config->getIndexPrefix();

        $this->algoliaHelper->clearIndex($indexPrefix.'default_'.$indexSuffix);
        $this->algoliaHelper->clearIndex($indexPrefix.'french_'.$indexSuffix);
        $this->algoliaHelper->clearIndex($indexPrefix.'german_'.$indexSuffix);

        $indexer->reindexAll();

        $this->algoliaHelper->waitLastTask();

        $resultsDefault = $this->algoliaHelper->query($indexPrefix.'default_'.$indexSuffix, '', array());
        $resultsFrench = $this->algoliaHelper->query($indexPrefix.'french_'.$indexSuffix, '', array());
        $resultsGerman = $this->algoliaHelper->query($indexPrefix.'german_'.$indexSuffix, '', array());

        $expectedNbHitsFrench = $expectedNbHitsFrench ?: $expectedNbHits;
        $expectedNbHitsGerman = $expectedNbHitsGerman ?: $expectedNbHits;

        $this->assertEquals($expectedNbHits, $resultsDefault['nbHits']);
        $this->assertEquals($expectedNbHitsFrench, $resultsFrench['nbHits']);
        $this->assertEquals($expectedNbHitsGerman, $resultsGerman['nbHits']);
    }
}
