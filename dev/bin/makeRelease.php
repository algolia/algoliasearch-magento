<?php

require_once __DIR__.'/app/Mage.php';

Mage::app();

/** @var Mage_Connect_Helper_Data $connectHelper */
$connectHelper = Mage::helper('connect');

/** @var Mage_Connect_Model_Extension $model */
$model = Mage::getModel('connect/extension');

/** @var Algolia_Algoliasearch_Helper_Config $config */
$config = Mage::helper('algoliasearch/config');

$data = $connectHelper->loadLocalPackage('algoliasearch');
$data['version'] = $config->getExtensionVersion();

$model->setData($data);

if ($model->createPackage()) {
    echo 'Release package was successfully created.';
    exit(0);
}

echo 'Release package could not be created.';
exit(1);
