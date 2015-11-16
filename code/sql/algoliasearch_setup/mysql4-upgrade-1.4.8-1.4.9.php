<?php

$installer = $this; /** @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

/** Need a truncate since now everything is json_encoded and not serialized */
$installer->run("TRUNCATE TABLE `{$installer->getTable('algoliasearch/queue')}`");
$installer->run("ALTER TABLE `{$installer->getTable('algoliasearch/queue')}` ADD data_size INT(11);");

$installer->endSetup();