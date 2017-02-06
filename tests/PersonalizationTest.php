<?php

use PHPUnit\Framework\TestCase;

class PersonalizationTest extends TestCase
{
    public function testPersonalization()
    {
        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');
        $indexPrefix = $config->getIndexPrefix();

        setConfig('algoliasearch/personalization/enable_personalization', '1');

        $indexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $indexer->reindexAll();

        /** @var Algolia_Algoliasearch_Helper_Algoliahelper $algoliaHelper */
        $algoliaHelper = Mage::helper('algoliasearch/algoliahelper');
        $algoliaHelper->waitLastTask();

        $index = $algoliaHelper->getIndex($indexPrefix.'default_products');

        $settings = $index->getSettings();
        $this->assertTrue(in_array('personalization_user_id', $settings['attributesForFaceting'], true), 'Attribute "personalization_user_id" should be set as attribute for faceting, but its not.');

        $record = $index->getObject(558);
        $this->assertTrue(isset($record['personalization_user_id']), 'Attribute "personalization_user_id" should be set to the product, but its not.');
        $this->assertEquals(array('135'), $record['personalization_user_id']);
    }

    public function testNonPersonalization()
    {
        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');
        $indexPrefix = $config->getIndexPrefix();

        setConfig('algoliasearch/personalization/enable_personalization', '0');

        $indexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $indexer->reindexAll();

        /** @var Algolia_Algoliasearch_Helper_Algoliahelper $algoliaHelper */
        $algoliaHelper = Mage::helper('algoliasearch/algoliahelper');
        $algoliaHelper->waitLastTask();

        $index = $algoliaHelper->getIndex($indexPrefix.'default_products');

        $settings = $index->getSettings();
        $this->assertFalse(in_array('personalization_user_id', $settings['attributesForFaceting'], true), 'Attribute "personalization_user_id" should not be set as attribute for faceting, but it is.');

        $record = $index->getObject(558);
        $this->assertFalse(isset($record['personalization_user_id']), 'Attribute "personalization_user_id" should not be set to the product, but it is.');
    }
}
