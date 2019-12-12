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
        setConfig('algoliasearch/categories/category_additional_attributes2', \Zend_Serializer::serialize(array()));

        $indexer = new Algolia_Algoliasearch_Model_Indexer_Algoliacategories();
        $indexer->reindexSpecificCategories(24);

        $this->algoliaHelper->waitLastTask();

        $results = $this->algoliaHelper->getObjects($this->indexPrefix.'default_categories', array('24'));
        $hit = reset($results['results']);

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
        );

        foreach ($defaultAttributes as $key => $attribute) {
            $this->assertTrue(isset($hit[$attribute]), 'Category attribute "'.$attribute.'" should be indexed but it is not"');
            unset($hit[$attribute]);
        }

        $extraAttributes = implode(', ', array_keys($hit));
        $this->assertTrue(empty($hit), 'Extra category attributes ('.$extraAttributes.') are indexed and should not be.');
    }
}
