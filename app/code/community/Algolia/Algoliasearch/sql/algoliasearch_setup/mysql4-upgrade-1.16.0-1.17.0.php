<?php
/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

$setup = new Mage_Sales_Model_Resource_Setup('core_setup');

$setup->addAttribute(
    'quote_item',
    'algoliasearch_query_param',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'grid' => false,
        'comment' => 'AlgoliaSearch Conversion Query Parameters'
    )
);

$setup->addAttribute(
    'order_item',
    'algoliasearch_query_param',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'grid' => false,
        'comment' => 'AlgoliaSearch Conversion Query Parameters'
    )
);

$installer->endSetup();
