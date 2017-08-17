<?php

class QueueTest extends AbstractTestCase
{
    /** @var Varien_Db_Adapter_Interface */
    private $readConnection;

    /** @var Varien_Db_Adapter_Interface */
    private $writeConnection;

    public function setUp()
    {
        parent::setUp();

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
            if ($index['name'] === $this->indexPrefix.'default_products') {
                $existsDefaultProdIndex = true;
            }

            if ($index['name'] === $this->indexPrefix.'default_products_tmp') {
                $existsDefaultTmpIndex = true;
            }

            if ($index['name'] === $this->indexPrefix.'french_products_tmp') {
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
            if ($index['name'] === $this->indexPrefix.'default_products') {
                $existsDefaultProdIndex = true;
            }

            if ($index['name'] === $this->indexPrefix.'french_products') {
                $existsFrenchProdIndex = true;
            }

            if ($index['name'] === $this->indexPrefix.'german_products') {
                $existsGermanProdIndex = true;
            }

            if ($index['name'] === $this->indexPrefix.'default_products_tmp') {
                $existsDefaultTmpIndex = true;
            }

            if ($index['name'] === $this->indexPrefix.'french_products_tmp') {
                $existsFrenchTmpIndex = true;
            }

            if ($index['name'] === $this->indexPrefix.'german_products_tmp') {
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

        $settings = $algoliaHelper->getIndex($this->indexPrefix.'default_products')->getSettings();
        $this->assertFalse(empty($settings['attributesForFaceting']), 'AttributesForFacetting should be set, but they are not.');
        $this->assertFalse(empty($settings['searchableAttributes']), 'SearchableAttributes should be set, but they are not.');

        $settings = $algoliaHelper->getIndex($this->indexPrefix.'french_products')->getSettings();
        $this->assertFalse(empty($settings['attributesForFaceting']), 'AttributesForFacetting should be set, but they are not.');
        $this->assertFalse(empty($settings['searchableAttributes']), 'SearchableAttributes should be set, but they are not.');

        $settings = $algoliaHelper->getIndex($this->indexPrefix.'german_products')->getSettings();
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
        $mergedJobs = invokeMethod($queue, 'mergeJobs', array('jobs' => $jobs));
        $this->assertEquals(6, count($mergedJobs));

        $expectedCategoryJob = array(
            'job_id' => 7,
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
            'data_size' => 3,
            'store_id' => '1',
            'merged_ids' => array('1', '7'),
        );

        $this->assertEquals($expectedCategoryJob, $mergedJobs[0]);

        $expectedProductJob = array(
            'job_id' => 10,
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
            'data_size' => 2,
            'store_id' => '1',
            'merged_ids' => array('4', '10'),
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
        $mergedJobs = invokeMethod($queue, 'mergeJobs', array('jobs' => $jobs));
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

    public function testGetJobs()
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

        $pid = getmypid();
        $jobs = invokeMethod($queue, 'getJobs', array('maxJobs' => 10, 'pid' => $pid));
        $this->assertEquals(6, count($jobs));

        $expectedFirstJob = array(
            'job_id' => 7,
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
            'data_size' => 3,
            'store_id' => '1',
            'merged_ids' => array('1', '7'),
        );

        $expectedLastJob = array(
            'job_id' => 12,
            'pid' => NULL,
            'class' => 'algoliasearch/observer',
            'method' => 'rebuildProductIndex',
            'data' => array(
                'store_id' => '3',
                'product_ids' => array(
                    0 => '448',
                    1 => '405',
                ),
            ),
            'max_retries' => '3',
            'retries' => '0',
            'error_log' => '',
            'data_size' => 2,
            'store_id' => '3',
            'merged_ids' => array('6', '12'),
        );

        $this->assertEquals($expectedFirstJob, reset($jobs));
        $this->assertEquals($expectedLastJob, end($jobs));

        $dbJobs = $this->readConnection->query('SELECT * FROM algoliasearch_queue')->fetchAll();

        $this->assertEquals(12, count($dbJobs));

        foreach ($dbJobs as $dbJob) {
            $this->assertEquals($pid, $dbJob['pid']);
        }
    }

    public function testHugeJob()
    {
        // Default value - maxBatchSize = 1000
        setConfig('algoliasearch/queue/number_of_job_to_run', 10);
        setConfig('algoliasearch/queue/number_of_element_by_page', 100);

        $productIds = range(1, 5000);
        $jsonProductIds = json_encode($productIds);

        $this->writeConnection->query('TRUNCATE TABLE algoliasearch_queue');
        $this->writeConnection->query('INSERT INTO `algoliasearch_queue` (`job_id`, `pid`, `class`, `method`, `data`, `max_retries`, `retries`, `error_log`, `data_size`) VALUES
            (1, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"1","product_ids":'.$jsonProductIds.'}\', 3, 0, \'\', 5000),
            (2, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"2","product_ids":["9","22"]}\', 3, 0, \'\', 2);');

        $queue = new Algolia_Algoliasearch_Model_Queue();

        $pid = getmypid();
        $jobs = invokeMethod($queue, 'getJobs', array('maxJobs' => 10, 'pid' => $pid));

        $this->assertEquals(1, count($jobs));

        $job = reset($jobs);
        $this->assertEquals(5000, $job['data_size']);
        $this->assertEquals(5000, count($job['data']['product_ids']));

        $dbJobs = $this->readConnection->query('SELECT * FROM algoliasearch_queue')->fetchAll();

        $this->assertEquals(2, count($dbJobs));

        $firstJob = reset($dbJobs);
        $lastJob = end($dbJobs);

        $this->assertEquals($pid, $firstJob['pid']);
        $this->assertNull($lastJob['pid']);
    }

    public function testMaxSingleJobSize()
    {
        // Default values - maxSingleJobSize = 100
        setConfig('algoliasearch/queue/number_of_job_to_run', 10);
        setConfig('algoliasearch/queue/number_of_element_by_page', 100);

        $productIds = range(1, 99);
        $jsonProductIds = json_encode($productIds);

        $this->writeConnection->query('TRUNCATE TABLE algoliasearch_queue');
        $this->writeConnection->query('INSERT INTO `algoliasearch_queue` (`job_id`, `pid`, `class`, `method`, `data`, `max_retries`, `retries`, `error_log`, `data_size`) VALUES
            (1, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"1","product_ids":'.$jsonProductIds.'}\', 3, 0, \'\', 99),
            (2, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"2","product_ids":["9","22"]}\', 3, 0, \'\', 2);');

        $queue = new Algolia_Algoliasearch_Model_Queue();

        $pid = getmypid();
        $jobs = invokeMethod($queue, 'getJobs', array('maxJobs' => 10, 'pid' => $pid));

        $this->assertEquals(2, count($jobs));

        $firstJob = reset($jobs);
        $lastJob = end($jobs);

        $this->assertEquals(99, $firstJob['data_size']);
        $this->assertEquals(99, count($firstJob['data']['product_ids']));

        $this->assertEquals(2, $lastJob['data_size']);
        $this->assertEquals(2, count($lastJob['data']['product_ids']));

        $dbJobs = $this->readConnection->query('SELECT * FROM algoliasearch_queue')->fetchAll();

        $this->assertEquals(2, count($dbJobs));

        $firstJob = reset($dbJobs);
        $lastJob = end($dbJobs);

        $this->assertEquals($pid, $firstJob['pid']);
        $this->assertEquals($pid, $lastJob['pid']);
    }

    public function testFaildedJob()
    {
        setConfig('algoliasearch/queue/number_of_retries', 3);

        $this->writeConnection->query('TRUNCATE TABLE algoliasearch_queue');

        // Setting "not-existing-store" as store_id throws exception during processing the job
        $this->writeConnection->query('INSERT INTO `algoliasearch_queue` (`job_id`, `pid`, `class`, `method`, `data`, `max_retries`, `retries`, `error_log`, `data_size`) VALUES
            (1, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"not-existing-store","category_ids":["9","22"]}\', 3, 0, \'\', 2),
            (2, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"2","category_ids":["9","22"]}\', 3, 0, \'\', 2),
            (3, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"not-existing-store","product_ids":["448"]}\', 3, 0, \'\', 1),
            (4, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"2","product_ids":["448"]}\', 3, 0, \'\', 1),
            (5, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"not-existing-store","category_ids":["40"]}\', 3, 0, \'\', 1),
            (6, NULL, \'algoliasearch/observer\', \'rebuildCategoryIndex\', \'{"store_id":"2","category_ids":["40"]}\', 3, 0, \'\', 1),
            (7, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"not-existing-store","product_ids":["405"]}\', 3, 0, \'\', 1),
            (8, NULL, \'algoliasearch/observer\', \'rebuildProductIndex\', \'{"store_id":"2","product_ids":["405"]}\', 3, 0, \'\', 1)');

        $queue = new Algolia_Algoliasearch_Model_Queue();

        $pid = getmypid();
        $jobs = invokeMethod($queue, 'getJobs', array('maxJobs' => 10, 'pid' => $pid));

        // Check is jobs are correctly merged
        $this->assertEquals(4, count($jobs));

        // Reset pid
        $this->writeConnection->query('UPDATE algoliasearch_queue SET pid = NULL');

        $queue->run(10);

        $jobs = $this->readConnection->query('SELECT * FROM algoliasearch_queue')->fetchAll();
        $this->assertEquals(4, count($jobs));

        foreach ($jobs as $job) {
            $this->assertNull($job['pid']);
            $this->assertEquals('1', $job['retries']);
        }

        $queue->run(10);

        $jobs = $this->readConnection->query('SELECT * FROM algoliasearch_queue')->fetchAll();
        $this->assertEquals(4, count($jobs));

        foreach ($jobs as $job) {
            $this->assertNull($job['pid']);
            $this->assertEquals('2', $job['retries']);
        }

        // 3rd run, 3rd retry - retries are maxed out
        $queue->run(10);

        // 4th run - should clean the table from maxed out jobs
        $queue->run(10);

        $jobs = $this->readConnection->query('SELECT * FROM algoliasearch_queue')->fetchAll();
        $this->assertEquals(0, count($jobs));
    }
}
