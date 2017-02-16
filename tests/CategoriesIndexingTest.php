<?php

class CategoriesIndexingTest extends AbstractIndexingTestCase
{
    public function testCategories()
    {
        $categoriesIndexer = new Algolia_Algoliasearch_Model_Indexer_Algoliacategories();
        $this->processTest($categoriesIndexer, 'categories', 25);
    }

    public function testDefaultIndexableAttributes()
    {
        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');
        $indexPrefix = $config->getIndexPrefix();

        setConfig('algoliasearch/categories/category_additional_attributes2', serialize(array()));

        $indexer = new Algolia_Algoliasearch_Model_Indexer_Algoliacategories();
        $indexer->reindexAll();

        $this->algoliaHelper->waitLastTask();

        $results = $this->algoliaHelper->query($indexPrefix.'default_categories', '', array('hitsPerPage' => 1));
        $hit = reset($results['hits']);

        $defaultAttributes = array(
            'objectID',
            'name',
            'url',
            'path',
            'level',
            'include_in_menu',
            '_tags',
            'popularity',
            'product_count',
            'algoliaLastUpdateAtCET',
            '_highlightResult',
        );

        foreach ($defaultAttributes as $key => $attribute) {
            $this->assertTrue(isset($hit[$attribute]), 'Category attribute "'.$attribute.'" should be indexed but it is not"');
            unset($hit[$attribute]);
        }

        $extraAttributes = implode(', ', array_keys($hit));
        $this->assertTrue(empty($hit), 'Extra category attributes ('.$extraAttributes.') are indexed and should not be.');
    }
}
