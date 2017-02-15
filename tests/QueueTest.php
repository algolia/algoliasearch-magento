<?php

use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    /** @var Varien_Db_Adapter_Interface */
    private $readConnection;

    /** @var Varien_Db_Adapter_Interface */
    private $writeConnection;

    public function setUp()
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');

        $this->readConnection = $resource->getConnection('core_read');
        $this->writeConnection = $resource->getConnection('core_write');
    }

    public function testFill()
    {
        setConfig('algoliasearch/queue/active', '1');

        $this->writeConnection->query('TRUNCATE TABLE algoliasearch_queue');

        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $productIndexer->reindexAll();

        $rows = $this->readConnection->query('SELECT * FROM algoliasearch_queue')->fetchAll();

        $this->assertEquals(7, count($rows));

        $first = true;
        foreach ($rows as $row) {
            $this->assertEquals('algoliasearch/observer', $row['class']);

            if ($first === true) {
                $this->assertEquals('saveSettings', $row['method']);
                $this->assertEquals(1, $row['data_size']);

                $first = false;

                continue;
            }

            if ($row['job_id'] % 2 === 0) {
                $this->assertEquals('rebuildProductIndex', $row['method']);
                $this->assertEquals(100, $row['data_size']);
            } else {
                $this->assertEquals('moveProductsTmpIndex', $row['method']);
                $this->assertEquals(1, $row['data_size']);
            }
        }
    }

    /** @depends testFill */
    public function testExecute()
    {
        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');
        $indexPrefix = $config->getIndexPrefix();

        /** @var Algolia_Algoliasearch_Helper_Algoliahelper $algoliaHelper */
        $algoliaHelper = Mage::helper('algoliasearch/algoliahelper');

        $queue = new Algolia_Algoliasearch_Model_Queue();

        // Run the first four jobs - saveSettings, batch, move, batch
        $queue->run(4);

        $algoliaHelper->waitLastTask();

        $indices = $algoliaHelper->listIndexes();

        $existsDefaultProdIndex = false;
        $existsDefaultTmpIndex = false;
        $existsFrenchTmpIndex = false;
        foreach ($indices['items'] as $index) {
            if ($index['name'] === $indexPrefix.'default_products') {
                $existsDefaultProdIndex = true;
            }

            if ($index['name'] === $indexPrefix.'default_products_tmp') {
                $existsDefaultTmpIndex = true;
            }

            if ($index['name'] === $indexPrefix.'french_products_tmp') {
                $existsFrenchTmpIndex = true;
            }
        }

        $this->assertTrue($existsDefaultProdIndex, 'Default products production index does not exists and it shoud'); // Was moved from TMP index
        $this->assertFalse($existsDefaultTmpIndex, 'Default product TMP index exists and it should not'); // Was already moved
        $this->assertTrue($existsFrenchTmpIndex, 'French products TMP index does not exists and it should'); // Wasn't moved

        // Run the second three jobs - move, batch, move
        $queue->run(3);

        $algoliaHelper->waitLastTask();

        $indices = $algoliaHelper->listIndexes();

        $existsDefaultProdIndex = false;
        $existsFrenchProdIndex = false;
        $existsGermanProdIndex = false;
        $existsDefaultTmpIndex = false;
        $existsFrenchTmpIndex = false;
        $existsGermanTmpIndex = false;
        foreach ($indices['items'] as $index) {
            if ($index['name'] === $indexPrefix.'default_products') {
                $existsDefaultProdIndex = true;
            }

            if ($index['name'] === $indexPrefix.'french_products') {
                $existsFrenchProdIndex = true;
            }

            if ($index['name'] === $indexPrefix.'german_products') {
                $existsGermanProdIndex = true;
            }

            if ($index['name'] === $indexPrefix.'default_products_tmp') {
                $existsDefaultTmpIndex = true;
            }

            if ($index['name'] === $indexPrefix.'french_products_tmp') {
                $existsFrenchTmpIndex = true;
            }

            if ($index['name'] === $indexPrefix.'german_products_tmp') {
                $existsGermanTmpIndex = true;
            }
        }

        $this->assertFalse($existsDefaultTmpIndex, 'Default product TMP index exists and it should not'); // Was already moved
        $this->assertFalse($existsFrenchTmpIndex, 'French products TMP index exists and it should not'); // Was already moved
        $this->assertFalse($existsGermanTmpIndex, 'German products TMP index exists and it should not'); // Was already moved

        $this->assertTrue($existsDefaultProdIndex, 'Default product production index does not exists and it should');
        $this->assertTrue($existsFrenchProdIndex, 'French products production index does not exists and it should');
        $this->assertTrue($existsGermanProdIndex, 'German products production index does not exists and it should');
    }

    public function testSettings()
    {
        setConfig('algoliasearch/queue/active', '1');

        resetConfigs(array(
            'instant/facets',
            'products/product_additional_attributes',
        ));

        $this->writeConnection->query('TRUNCATE TABLE algoliasearch_queue');

        // Reindex products multiple times
        $productIndexer = new Algolia_Algoliasearch_Model_Indexer_Algolia();
        $productIndexer->reindexAll();
        $productIndexer->reindexAll();
        $productIndexer->reindexAll();

        $rows = $this->readConnection->query('SELECT * FROM algoliasearch_queue')->fetchAll();
        $this->assertEquals(21, count($rows));

        // Process the whole queue
        $queueRunner = new Algolia_Algoliasearch_Model_Indexer_Algoliaqueuerunner();
        $queueRunner->reindexAll();
        $queueRunner->reindexAll();
        $queueRunner->reindexAll();

        $rows = $this->readConnection->query('SELECT * FROM algoliasearch_queue')->fetchAll();
        $this->assertEquals(0, count($rows));

        /** @var Algolia_Algoliasearch_Helper_Algoliahelper $algoliaHelper */
        $algoliaHelper = Mage::helper('algoliasearch/algoliahelper');
        $algoliaHelper->waitLastTask();

        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');
        $indexPrefix = $config->getIndexPrefix();

        $settings = $algoliaHelper->getIndex($indexPrefix.'default_products')->getSettings();
        $this->assertFalse(empty($settings['attributesForFaceting']), 'AttributesForFacetting should be set, but they are not.');
        $this->assertFalse(empty($settings['searchableAttributes']), 'SearchableAttributes should be set, but they are not.');

        $settings = $algoliaHelper->getIndex($indexPrefix.'french_products')->getSettings();
        $this->assertFalse(empty($settings['attributesForFaceting']), 'AttributesForFacetting should be set, but they are not.');
        $this->assertFalse(empty($settings['searchableAttributes']), 'SearchableAttributes should be set, but they are not.');

        $settings = $algoliaHelper->getIndex($indexPrefix.'german_products')->getSettings();
        $this->assertFalse(empty($settings['attributesForFaceting']), 'AttributesForFacetting should be set, but they are not.');
        $this->assertFalse(empty($settings['searchableAttributes']), 'SearchableAttributes should be set, but they are not.');
    }

    public function testMerging()
    {
        $this->writeConnection->query('TRUNCATE TABLE algoliasearch_queue');
        $this->writeConnection->query('INSERT INTO `algoliasearch_queue` (`job_id`, `pid`, `class`, `method`, `data`, `max_retries`, `retries`, `error_log`, `data_size`) VALUES
            (1, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"1","category_ids":["9","22"]}\', 3, 0, \'\', 2),
            (2, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"2","category_ids":["9","22"]}\', 3, 0, \'\', 2),
            (3, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"3","category_ids":["9","22"]}\', 3, 0, \'\', 2),
            (4, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"1","product_ids":["448"]}\', 3, 0, \'\', 1),
            (5, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"2","product_ids":["448"]}\', 3, 0, \'\', 1),
            (6, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"3","product_ids":["448"]}\', 3, 0, \'\', 1),
            (7, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"1","category_ids":["40"]}\', 3, 0, \'\', 1),
            (8, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"2","category_ids":["40"]}\', 3, 0, \'\', 1),
            (9, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"3","category_ids":["40"]}\', 3, 0, \'\', 1),
            (10, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"1","product_ids":["405"]}\', 3, 0, \'\', 1),
            (11, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"2","product_ids":["405"]}\', 3, 0, \'\', 1),
            (12, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"3","product_ids":["405"]}\', 3, 0, \'\', 1);');

        $queue = new Algolia_Algoliasearch_Model_Queue();

        $jobs = $this->readConnection->query('SELECT * FROM algoliasearch_queue')->fetchAll();

        $jobs = invokeMethod($queue, 'prepareJobs', array('jobs' => $jobs));
        $mergedJobs = invokeMethod($queue, 'sortAndMergeJob', array('jobs' => $jobs));
        $this->assertEquals(6, count($mergedJobs));

        $expectedCategoryJob = array(
            'job_id' => '1',
            'pid' => NULL,
            'class' => 'algoliasearch/observer',
            'method' => 'rebuildCategoryIndex',
            'data' => array(
                'store_id' => '1',
                'category_ids' => array(
                    0 => '9',
                    1 => '22',
                    2 => '40',
                ),
            ),
            'max_retries' => '3',
            'retries' => '0',
            'error_log' => '',
            'data_size' => '2',
            'store_id' => '1',
        );

        $this->assertEquals($expectedCategoryJob, $mergedJobs[0]);

        $expectedProductJob = array(
            'job_id' => '4',
            'pid' => NULL,
            'class' => 'algoliasearch/observer',
            'method' => 'rebuildProductIndex',
            'data' => array(
                'store_id' => '1',
                'product_ids' => array(
                    0 => '448',
                    1 => '405',
                ),
            ),
            'max_retries' => '3',
            'retries' => '0',
            'error_log' => '',
            'data_size' => '1',
            'store_id' => '1',
        );

        $this->assertEquals($expectedProductJob, $mergedJobs[3]);
    }

    public function testMergingWithStaticMethods()
    {
        $this->writeConnection->query('TRUNCATE TABLE algoliasearch_queue');
        $this->writeConnection->query('INSERT INTO `algoliasearch_queue` (`job_id`, `pid`, `class`, `method`, `data`, `max_retries`, `retries`, `error_log`, `data_size`) VALUES
            (1, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"1","category_ids":["9","22"]}\', 3, 0, \'\', 2),
            (2, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"2","category_ids":["9","22"]}\', 3, 0, \'\', 2),
            (3, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"3","category_ids":["9","22"]}\', 3, 0, \'\', 2),
            (4, NULL, \'algoliasearch/observer\', \'removeCategories\', \'{"store_id":"1","product_ids":["448"]}\', 3, 0, \'\', 1),
            (5, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"2","product_ids":["448"]}\', 3, 0, \'\', 1),
            (6, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"3","product_ids":["448"]}\', 3, 0, \'\', 1),
            (7, NULL, \'algoliasearch/observer\', \'saveSettings\', \'{"store_id":"1","category_ids":["40"]}\', 3, 0, \'\', 1),
            (8, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"2","category_ids":["40"]}\', 3, 0, \'\', 1),
            (9, NULL, \'algoliasearch/observer\', \'moveProductsTmpIndex\', \'{"store_id":"3","category_ids":["40"]}\', 3, 0, \'\', 1),
            (10, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"1","product_ids":["405"]}\', 3, 0, \'\', 1),
            (11, NULL, \'algoliasearch/observer\', \'moveStoreSuggestionIndex\', \'{"store_id":"2","product_ids":["405"]}\', 3, 0, \'\', 1),
            (12, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"3","product_ids":["405"]}\', 3, 0, \'\', 1);');

        $queue = new Algolia_Algoliasearch_Model_Queue();

        $jobs = $this->readConnection->query('SELECT * FROM algoliasearch_queue')->fetchAll();

        $jobs = invokeMethod($queue, 'prepareJobs', array('jobs' => $jobs));
        $mergedJobs = invokeMethod($queue, 'sortAndMergeJob', array('jobs' => $jobs));
        $this->assertEquals(12, count($mergedJobs));

        $this->assertEquals('rebuildCategoryIndex', $jobs[0]['method']);
        $this->assertEquals('rebuildCategoryIndex', $jobs[1]['method']);
        $this->assertEquals('rebuildCategoryIndex', $jobs[2]['method']);
        $this->assertEquals('removeCategories', $jobs[3]['method']);
        $this->assertEquals('rebuildProductIndex', $jobs[4]['method']);
        $this->assertEquals('rebuildProductIndex', $jobs[5]['method']);
        $this->assertEquals('saveSettings', $jobs[6]['method']);
        $this->assertEquals('rebuildCategoryIndex', $jobs[7]['method']);
        $this->assertEquals('moveProductsTmpIndex', $jobs[8]['method']);
        $this->assertEquals('rebuildProductIndex', $jobs[9]['method']);
        $this->assertEquals('moveStoreSuggestionIndex', $jobs[10]['method']);
        $this->assertEquals('rebuildProductIndex', $jobs[11]['method']);
    }
}
