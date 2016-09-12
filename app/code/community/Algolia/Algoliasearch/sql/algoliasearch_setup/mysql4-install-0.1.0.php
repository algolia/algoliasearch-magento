<?php

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS `{$installer->getTable('algoliasearch/queue')}` (
  `job_id` int(20) NOT NULL auto_increment,
  `pid` int(20) NULL,
  `class` varchar(50) NOT NULL,
  `method` varchar(50) NOT NULL,
  `data` varchar(5000) NOT NULL,
  `max_retries` int(11) NOT NULL DEFAULT 3,
  `retries` int(11) NOT NULL DEFAULT 0,
  `error_log` text NOT NULL DEFAULT '',
  PRIMARY KEY `job_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 AUTO_INCREMENT=1;
");

$installer->endSetup();
