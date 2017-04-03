<?php

class AbstractIndexingTestCase extends AbstractTestCase
{
    /** @var Algolia_Algoliasearch_Helper_Algoliahelper */
    protected $algoliaHelper;

    public function setUp()
    {
        parent::setUp();

        $extraSettingsSections = array('products', 'categories', 'pages', 'suggestions');
        foreach ($extraSettingsSections as $section) {
            setConfig('algoliasearch/advanced_settings/'.$section.'_extra_settings', '');
        }

        $this->algoliaHelper = Mage::helper('algoliasearch/algoliahelper');
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
}
