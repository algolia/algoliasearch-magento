<?php

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

// Need to transform "removeProducts" to "rebuildProductIndex" as re-indexing was refactored and "removeProducts" do not exists anymore
$installer->run("DELETE FROM `{$installer->getTable('algoliasearch/queue')}`");

$installer->endSetup();
