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

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $productIndexer->reindexAll();

        $personalizationIndexer = new Algolia_Algoliasearch_Model_Indexer_Algoliapersonalization();
        $personalizationIndexer->reindexAll();

        /** @var Algolia_Algoliasearch_Helper_Algoliahelper $algoliaHelper */
        $algoliaHelper = Mage::helper('algoliasearch/algoliahelper');
        $algoliaHelper->waitLastTask();

        $index = $algoliaHelper->getIndex($indexPrefix.'default_products');

        $settings = $index->getSettings();
        $this->assertTrue(in_array('personalization_user_id', $settings['attributesForFaceting'], true), 'Attribute "personalization_user_id" should be set as attribute for faceting, but its not.');

        $record = $index->getObject(558);
        $this->assertTrue(isset($record['personalization_user_id']), 'Attribute "personalization_user_id" should be set to the product, but its not.');
        $this->assertEquals(array(135), $record['personalization_user_id']);
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

    public function testOutputOfDsiabledIndexer()
    {
        setConfig('algoliasearch/personalization/enable_personalization', '0');

        ob_start();

        $indexer = new Algolia_Algoliasearch_Model_Indexer_Algoliapersonalization();
        $indexer->reindexAll();

        $contents = trim(ob_get_clean());

        $this->assertEquals("[ALGOLIA] INDEXING IS DISABLED FOR 1 (English)
[ALGOLIA] INDEXING IS DISABLED FOR 2 (French)
[ALGOLIA] INDEXING IS DISABLED FOR 3 (German)", $contents);

        /** @var Mage_Adminhtml_Model_Session $session */
        $session = Mage::getSingleton('adminhtml/session');

        /** @var Mage_Core_Model_Message_Warning[] $messages */
        $messages = $session->getMessages()->getItemsByType('warning');

        $this->assertEquals('[ALGOLIA] INDEXING IS DISABLED FOR 1 (English)', $messages[0]->getCode());
        $this->assertEquals('[ALGOLIA] INDEXING IS DISABLED FOR 2 (French)', $messages[1]->getCode());
        $this->assertEquals('[ALGOLIA] INDEXING IS DISABLED FOR 3 (German)', $messages[2]->getCode());
    }
}
