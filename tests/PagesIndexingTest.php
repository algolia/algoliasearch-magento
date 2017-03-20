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

        foreach (array('default', 'french', 'german') as $store) {
            $results = $this->algoliaHelper->query($this->indexPrefix.$store.'_pages', '', array());

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
        $indexer = new Algolia_Algoliasearch_Model_Indexer_Algoliapages();
        $indexer->reindexAll();

        $this->algoliaHelper->waitLastTask();

        $results = $this->algoliaHelper->query($this->indexPrefix.'default_pages', '', array('hitsPerPage' => 1));
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

    public function testStripTags()
    {
        $testPageData = array(
            'title' => 'Test CMS Page Title',
            'root_template' => 'one_column',
            'meta_keywords' => 'meta,keywords',
            'meta_description' => '',
            'identifier' => 'this-is-the-page-url-'.rand(0,99999999),
            'content_heading' => 'content heading',
            'stores' => array(0), //available for all store views
            'content' => 'Hello Im a test CMS page with script tags and style tags. <script>alert("Foo");</script> <style>.bar { font-weight: bold; }</style>',
        );

        /** @var Mage_Cms_Model_Page $model */
        $model = Mage::getModel('cms/page');
        $model->setData($testPageData);

        /** @var Mage_Cms_Model_Page $page */
        $testPage = $model->save();
        $testPageId = $testPage->getId();

        /** @var Algolia_Algoliasearch_Helper_Entity_Pagehelper $pagesHelper */
        $pagesHelper = Mage::helper('algoliasearch/entity_pagehelper');

        $pages = $pagesHelper->getPages(1);
        foreach ($pages as $page) {
            if ($page['objectID'] == $testPageId) {
                $content = $page['content'];

                $this->assertNotContains('<script>', $content);
                $this->assertNotContains('alert("Foo");', $content);

                $this->assertNotContains('<style>', $content);
                $this->assertNotContains('.bar { font-weight: bold; }', $content);
            }
        }

        $testPage->delete();
    }
}
