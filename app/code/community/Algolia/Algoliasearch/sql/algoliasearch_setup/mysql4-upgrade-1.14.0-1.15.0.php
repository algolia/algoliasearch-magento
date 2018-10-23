<?php
/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('algoliasearch/queue_archive');

$installer->run("
CREATE TABLE IF NOT EXISTS `{$tableName}` (
  `pid` int(11) DEFAULT NULL COMMENT 'Pid',
  `class` varchar(50) NOT NULL COMMENT 'Class',
  `method` varchar(50) NOT NULL COMMENT 'Method',
  `data` text NOT NULL COMMENT 'Data',
  `error_log` text NOT NULL COMMENT 'Error Log',
  `data_size` int(11) DEFAULT NULL COMMENT 'Data Size',
  `created_at` datetime NOT NULL COMMENT 'Created At'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
