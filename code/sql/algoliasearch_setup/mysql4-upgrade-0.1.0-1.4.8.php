<?php

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$table = Mage::getConfig()->getTablePrefix().'sales_flat_order_item';
$installer->run("ALTER TABLE `{$table}` ADD INDEX `IDX_ALGOLIA_SALES_FLAT_ORDER_ITEM_PRODUCT_ID` (`product_id`);");

$table = Mage::getConfig()->getTablePrefix().'review_entity_summary';
$installer->run("ALTER TABLE `{$table}` ADD INDEX `IDX_ALGOLIA_REVIEW_ENTITY_SUMMARY_ENTITY_PK_VALUE_STORE_ID` (`store_id`, `entity_pk_value`);");

$installer->endSetup();
