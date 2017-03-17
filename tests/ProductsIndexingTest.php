<?php

class ProductsIndexingTest extends AbstractIndexingTestCase
{
    const DEFAULT_PRODUCT_COUNT = 86;

    public function testProductsAllVisible()
    {
        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', self::DEFAULT_PRODUCT_COUNT);
    }

    public function testProductsOnlySearchVisible()
    {
        setConfig('algoliasearch/products/index_visibility', 'only_search');

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', 85);
    }

    public function testProductsOnlyCatalogVisible()
    {
        setConfig('algoliasearch/products/index_visibility', 'only_catalog');

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', self::DEFAULT_PRODUCT_COUNT);
    }

    public function testProductsOnInStock()
    {
        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', self::DEFAULT_PRODUCT_COUNT);
    }

    public function testProductsIncludingOutOfStock()
    {
        setConfig('cataloginventory/options/show_out_of_stock', '1');

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', 93);
    }

    public function testDefaultIndexableAttributes()
    {
        setConfig('algoliasearch/products/product_additional_attributes', serialize(array()));
        setConfig('algoliasearch/instant/facets', serialize(array()));
        setConfig('algoliasearch/instant/sorts', serialize(array()));
        setConfig('algoliasearch/products/custom_ranking_product_attributes', serialize(array()));

        $indexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $indexer->reindexAll();

        $this->algoliaHelper->waitLastTask();

        $results = $this->algoliaHelper->query($this->indexPrefix.'default_products', '', array('hitsPerPage' => 1));
        $hit = reset($results['hits']);

        $defaultAttributes = array(
            'objectID',
            'name',
            'url',
            'visibility_search',
            'visibility_catalog',
            'categories',
            'categories_without_path',
            'thumbnail_url',
            'image_url',
            'in_stock',
            'price',
            'type_id',
            'algoliaLastUpdateAtCET',
            '_highlightResult',
        );

        foreach ($defaultAttributes as $key => $attribute) {
            $this->assertTrue(isset($hit[$attribute]), 'Products attribute "'.$attribute.'" should be indexed but it is not"');
            unset($hit[$attribute]);
        }

        $extraAttributes = implode(', ', array_keys($hit));
        $this->assertTrue(empty($hit), 'Extra products attributes ('.$extraAttributes.') are indexed and should not be.');
    }

    public function testIfIndexingCanBeEnabledAndDisabled()
    {
        setConfig('algoliasearch/credentials/enable_backend', '0');

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', 0);

        setConfig('algoliasearch/credentials/enable_backend', '1');

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', self::DEFAULT_PRODUCT_COUNT);
    }

    public function testProductAreSearchableIfIndexingIsDisabled()
    {
        setConfig('algoliasearch/credentials/enable_backend', '0');

        $resultsDefault = $this->algoliaHelper->query($this->indexPrefix.'default_products', 'lemon flower', array());

        $this->assertEquals(1, $resultsDefault['nbHits']);
    }
}
