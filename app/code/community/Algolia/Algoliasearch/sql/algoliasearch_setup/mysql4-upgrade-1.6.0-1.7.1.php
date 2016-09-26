<?php

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

$tableName = Mage::getSingleton('core/resource')->getTableName('algoliasearch/queue');

$installer->run('ALTER TABLE ' . $tableName . ' MODIFY data LONGTEXT NOT NULL');

$installer->endSetup();
