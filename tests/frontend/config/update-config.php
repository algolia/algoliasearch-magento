<?php

include __DIR__.'/../../../vendor/autoload.php';

// Bootstrap Magento
include __DIR__.'/../../../../../app/Mage.php';

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

function setPersistantConfig($config)
{
    $conn = Mage::getSingleton('core/resource')->getConnection('core_write');

    foreach ($config as $path => $value) {
        $conn->query("UPDATE `core_config_data` SET `value`='$value' WHERE path='$path'");
    }
}

function getConfigToApply($param) {
    switch ($param) {
        case '--enable-autocomplete':
            return [
                'algoliasearch/credentials/is_popup_enabled' => 1,
                'algoliasearch/credentials/is_instant_enabled' => 0,
            ];
            echo "Enabled Autocomplete";
            break;
        case '--enable-instantsearch':
            return [
                'algoliasearch/credentials/is_popup_enabled' => 0,
                'algoliasearch/credentials/is_instant_enabled' => 1,
            ];
            echo "Enabled InstantSearch";
            break;
        case '--default':
        default:
            return [
                'algoliasearch/credentials/is_popup_enabled' => 1,
                'algoliasearch/credentials/is_instant_enabled' => 0,
            ];
    }
}

setPersistantConfig(getConfigToApply($argv[1]));