<?php

require_once dirname(getenv('PWD')).'../../../../../Mage.php';

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

@unlink(Mage::getBaseDir('var') . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'algolia_dump.log');

/** @var Algolia_Algoliasearch_Helper_Config $config */
$config = Mage::helper('algoliasearch/config');
$configReflection = new ReflectionClass(get_class($config));

$allMethods = $configReflection->getMethods(ReflectionMethod::IS_PUBLIC);

$configMethods = array();
foreach ($allMethods as $method) {
    if ($method->getDeclaringClass()->getName() == get_class($config)) {
        $parameters = $method->getParameters();
        $firstParamter = reset($parameters);
        if ($method->getNumberOfParameters() === 1 && $firstParamter->getName() === 'storeId') {
            $configMethods[] = $method->getName();
        }
    }
}

/** @var Mage_Core_Model_Resource $coreResource */
$coreResource = Mage::getSingleton('core/resource');
$db = $coreResource->getConnection('core_read');

$configTableName = $coreResource->getTableName('core/config_data');

$configRows = $db->query('SELECT path FROM '.$configTableName.' WHERE path LIKE "algolia%"')->fetchAll();

/** @var Mage_Core_Model_Store $store */
foreach (Mage::app()->getStores() as $store) {
    $storeId = $store->getId();

    algolia_dump_log('-- Dump config for store ID '.$storeId.' --');

    algolia_dump_log('-- Computed values --');
    foreach ($configMethods as $configMethod) {
        $result = $config->{$configMethod}($storeId);
        algolia_dump_log('$config->'.$configMethod.'('.$storeId.') === '.var_export($result, true));
    }

    algolia_dump_log('-- Raw values --');
    foreach ($configRows as $row) {
        algolia_dump_log($row['path'].' === '.var_export(Mage::getStoreConfig($row['path'], $storeId), true));
    }
}

echo "Dump file was successfully created.\n";

function algolia_dump_log($message)
{
    Mage::log($message, null, 'algolia_dump.log', true);
}
