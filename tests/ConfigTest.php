<?php

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /** @var Algolia_Algoliasearch_Helper_Config */
    private $config;

    /** @var Algolia_Algoliasearch_Model_Observer */
    private $observer;

    /** @var Algolia_Algoliasearch_Helper_Algoliahelper */
    private $algoliaHelper;

    /** @var string */
    private $indexPrefix;

    public function setUp()
    {
        $this->config = Mage::helper('algoliasearch/config');
        $this->observer = Mage::getSingleton('algoliasearch/observer');
        $this->algoliaHelper = Mage::helper('algoliasearch/algoliahelper');
        $this->indexPrefix = $this->config->getIndexPrefix();

        setConfig('algoliasearch/personalization/enable_personalization', '0');
    }

    public function testFacets()
    {
        $facets = $this->config->getFacets();
        $this->observer->saveSettings();

        $this->algoliaHelper->waitLastTask();

        $indexSettings = $this->algoliaHelper->getIndex($this->indexPrefix.'default_products')->getSettings();

        $this->assertEquals(count($facets), count($indexSettings['attributesForFaceting']));

        $attributesMatched = 0;
        foreach ($facets as $facet) {
            foreach ($indexSettings['attributesForFaceting'] as $indexFacet) {
                if ($facet['attribute'] === 'price' && strpos($indexFacet, 'price.') === 0) {
                    $attributesMatched++;
                } elseif ($facet['attribute'] === $indexFacet) {
                    $attributesMatched++;
                }
            }
        }

        $this->assertEquals(count($facets), $attributesMatched);
    }

    public function testAutomaticalSetOfCategoriesFacet()
    {
        // Remove categories from facets
        $facets = $this->config->getFacets();
        foreach ($facets as $key => $facet) {
            if($facet['attribute'] === 'categories') {
                unset($facets[$key]);
                break;
            }
        }

        setConfig('algoliasearch/instant/facets', serialize($facets));

        // Set don't replace category pages with Algolia - categories attribute shouldn't be included in facets
        setConfig('algoliasearch/instant/replace_categories', '0');
        $this->observer->saveSettings();

        $this->algoliaHelper->waitLastTask();

        $indexSettings = $this->algoliaHelper->getIndex($this->indexPrefix.'default_products')->getSettings();

        $this->assertEquals(2, count($indexSettings['attributesForFaceting']));

        $categoriesAttributeIsIncluded = false;
        foreach ($indexSettings['attributesForFaceting'] as $attribute) {
            if ($attribute === 'categories') {
                $categoriesAttributeIsIncluded = true;
                break;
            }
        }
        $this->assertFalse($categoriesAttributeIsIncluded, 'Categories attribute should not be included in facets, but it is');

        // Set replace category pages with Algolia - categories attribute should be included in facets
        setConfig('algoliasearch/instant/replace_categories', '1');
        $this->observer->saveSettings();

        $this->algoliaHelper->waitLastTask();

        $indexSettings = $this->algoliaHelper->getIndex($this->indexPrefix.'default_products')->getSettings();

        $this->assertEquals(3, count($indexSettings['attributesForFaceting']));

        $categoriesAttributeIsIncluded = false;
        foreach ($indexSettings['attributesForFaceting'] as $attribute) {
            if ($attribute === 'categories') {
                $categoriesAttributeIsIncluded = true;
                break;
            }
        }
        $this->assertTrue($categoriesAttributeIsIncluded, 'Categories attribute should be included in facets, but it is not');
    }
}
