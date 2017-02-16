<?php

class PagesIndexingTest extends AbstractIndexingTestCase
{
    public function testPages()
    {
        $indexer = new Algolia_Algoliasearch_Model_Indexer_Algoliapages();
        $this->processTest($indexer, 'pages', 10, 9, 9);
    }

    public function testExcludedPages()
    {
        $excludedPages = array(
            array(
                'pages' => 'no-route',
            ),
            array(
                'pages' => 'home',
            ),
        );

        setConfig('algoliasearch/autocomplete/excluded_pages', serialize($excludedPages));

        $indexer = new Algolia_Algoliasearch_Model_Indexer_Algoliapages();
        $this->processTest($indexer, 'pages', 8, 7, 7);


        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');
        $indexPrefix = $config->getIndexPrefix();

        foreach (array('default', 'french', 'german') as $store) {
            $results = $this->algoliaHelper->query($indexPrefix.$store.'_pages', '', array());

            $noRoutePageExists = false;
            $homePageExists = false;
            foreach ($results['hits'] as $hit) {
                if ($hit['slug'] === 'no-route') {
                    $noRoutePageExists = true;
                    continue;
                }

                if ($hit['slug'] === 'home') {
                    $homePageExists = true;
                    continue;
                }
            }

            $this->assertFalse($noRoutePageExists, 'no-route page exists in "'.$store.'" page index and it should not');
            $this->assertFalse($homePageExists, 'home page exists in "'.$store.'" page index and it should not');
        }
    }

    public function testDefaultIndexableAttributes()
    {
        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');
        $indexPrefix = $config->getIndexPrefix();

        $indexer = new Algolia_Algoliasearch_Model_Indexer_Algoliapages();
        $indexer->reindexAll();

        $this->algoliaHelper->waitLastTask();

        $results = $this->algoliaHelper->query($indexPrefix.'default_pages', '', array('hitsPerPage' => 1));
        $hit = reset($results['hits']);

        $defaultAttributes = array(
            'objectID',
            'name',
            'url',
            'slug',
            'content',
            'algoliaLastUpdateAtCET',
            '_highlightResult',
            '_snippetResult',
        );

        foreach ($defaultAttributes as $key => $attribute) {
            $this->assertTrue(isset($hit[$attribute]), 'Pages attribute "'.$attribute.'" should be indexed but it is not"');
            unset($hit[$attribute]);
        }

        $extraAttributes = implode(', ', array_keys($hit));
        $this->assertTrue(empty($hit), 'Extra pages attributes ('.$extraAttributes.') are indexed and should not be.');
    }
}
