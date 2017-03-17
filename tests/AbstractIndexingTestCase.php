<?php

use PHPUnit\Framework\TestCase;

class AbstractIndexingTestCase extends TestCase
{
    /** @var Algolia_Algoliasearch_Helper_Algoliahelper */
    protected $algoliaHelper;

    protected $indexPrefix;

    public function setUp()
    {
        $this->algoliaHelper = Mage::helper('algoliasearch/algoliahelper');

        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');
        $this->indexPrefix = $config->getIndexPrefix();

        setConfig('algoliasearch/queue/active', '0');

        $extraSettingsSections = array('products', 'categories', 'pages', 'suggestions');
        foreach ($extraSettingsSections as $section) {
            setConfig('algoliasearch/advanced_settings/'.$section.'_extra_settings', '');
        }
    }

    protected function processTest(Algolia_Algoliasearch_Model_Indexer_Abstract $indexer, $indexSuffix, $expectedNbHits, $expectedNbHitsFrench = null, $expectedNbHitsGerman = null)
    {
        $this->algoliaHelper->clearIndex($this->indexPrefix.'default_'.$indexSuffix);
        $this->algoliaHelper->clearIndex($this->indexPrefix.'french_'.$indexSuffix);
        $this->algoliaHelper->clearIndex($this->indexPrefix.'german_'.$indexSuffix);

        $indexer->reindexAll();

        $this->algoliaHelper->waitLastTask();

        $resultsDefault = $this->algoliaHelper->query($this->indexPrefix.'default_'.$indexSuffix, '', array());
        $resultsFrench = $this->algoliaHelper->query($this->indexPrefix.'french_'.$indexSuffix, '', array());
        $resultsGerman = $this->algoliaHelper->query($this->indexPrefix.'german_'.$indexSuffix, '', array());

        $expectedNbHitsFrench = $expectedNbHitsFrench ?: $expectedNbHits;
        $expectedNbHitsGerman = $expectedNbHitsGerman ?: $expectedNbHits;

        $this->assertEquals($expectedNbHits, $resultsDefault['nbHits']);
        $this->assertEquals($expectedNbHitsFrench, $resultsFrench['nbHits']);
        $this->assertEquals($expectedNbHitsGerman, $resultsGerman['nbHits']);
    }

    public function processQueryOneProduct()
    {
        $resultsDefault = $this->algoliaHelper->query($this->indexPrefix.'default_products', 'lemon flower', array());

        $this->assertEquals(1, $resultsDefault['nbHits']);
    }
}
