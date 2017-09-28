<?php

// This migration is the same to 1.11.1
// That one didn't work when migrating from newer versions then 1.7.1

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('algoliasearch/queue');

$installer->getConnection()->addColumn($tableName, 'created', array(
    'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
    'after' => 'job_id',
    'nullable' => true,
    'comment' => 'Time of job creation',
));

$installer->run("
CREATE TABLE IF NOT EXISTS `{$tableName}_log` (
  `id` INT(20) NOT NULL auto_increment,
  `started` DATETIME NOT NULL,
  `duration` INT(20) NOT NULL,
  `processed_jobs` INT NOT NULL,
  `with_empty_queue` INT(1) NOT NULL, 
  PRIMARY KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 AUTO_INCREMENT=1;
");

$installer->endSetup();
