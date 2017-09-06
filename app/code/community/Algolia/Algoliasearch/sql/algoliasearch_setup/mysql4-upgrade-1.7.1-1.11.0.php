<?php

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('algoliasearch/queue');
$installer->run("ALTER TABLE `{$tableName}` ADD `created` DATETIME AFTER `job_id`;");

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
