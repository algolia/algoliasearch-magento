<?php

class ProductsIndexingTest extends AbstractIndexingTestCase
{
    public function testProductsAllVisible()
    {
        setConfig('algoliasearch/products/index_visibility', 'all');
        setConfig('cataloginventory/options/show_out_of_stock', '0');

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', 86);
    }

    public function testProductsOnlySearchVisible()
    {
        setConfig('algoliasearch/products/index_visibility', 'only_search');
        setConfig('cataloginventory/options/show_out_of_stock', '0');

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', 85);
    }

    public function testProductsOnlyCatalogVisible()
    {
        setConfig('algoliasearch/products/index_visibility', 'only_catalog');
        setConfig('cataloginventory/options/show_out_of_stock', '0');

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', 86);
    }

    public function testProductsOnInStock()
    {
        setConfig('algoliasearch/products/index_visibility', 'all');
        setConfig('cataloginventory/options/show_out_of_stock', '0');

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', 86);
    }

    public function testProductsIncludingOutOfStock()
    {
        setConfig('algoliasearch/products/index_visibility', 'all');
        setConfig('cataloginventory/options/show_out_of_stock', '1');

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', 93);
    }

    public function testDefaultIndexableAttributes()
    {
        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');
        $indexPrefix = $config->getIndexPrefix();

        setConfig('algoliasearch/products/product_additional_attributes', serialize(array()));
        setConfig('algoliasearch/instant/facets', serialize(array()));
        setConfig('algoliasearch/instant/sorts', serialize(array()));
        setConfig('algoliasearch/products/custom_ranking_product_attributes', serialize(array()));

        $indexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $indexer->reindexAll();

        $this->algoliaHelper->waitLastTask();

        $results = $this->algoliaHelper->query($indexPrefix.'default_products', '', array('hitsPerPage' => 1));
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
        // Turn off logging to avoid messages between PHPUnit dots
        setConfig('algoliasearch/credentials/debug', '0');

        setConfig('algoliasearch/credentials/enable_backend', '0');

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', 0);

        setConfig('algoliasearch/credentials/enable_backend', '1');

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $this->processTest($productIndexer, 'products', 86);
    }

    public function testProductAreSearchableIfIndexingIsDisabled()
    {
        // Turn off logging to avoid messages between PHPUnit dots
        setConfig('algoliasearch/credentials/debug', '0');

        setConfig('algoliasearch/credentials/enable_backend', '0');
        $this->processQueryOneProduct();

        setConfig('algoliasearch/credentials/debug', '1');
    }
}
