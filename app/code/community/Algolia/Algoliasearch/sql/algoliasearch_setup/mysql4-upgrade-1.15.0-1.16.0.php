<?php
/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('algoliasearch/queue');

$installer->getConnection()->addColumn($tableName, 'locked_at', array(
    'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
    'after' => 'job_id',
    'nullable' => true,
    'comment' => 'Time of job creation',
));

$installer->endSetup();
