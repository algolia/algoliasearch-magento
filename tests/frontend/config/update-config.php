<?php

include __DIR__.'/../../../vendor/autoload.php';

// Bootstrap Magento
include __DIR__.'/../../../../../app/Mage.php';

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

function setPersistantConfig($config)
{
    $conn = Mage::getSingleton('core/resource')->getConnection('core_write');

    foreach ($config as $path => $value) {
        $sql = "INSERT INTO core_config_data (scope, scope_id, path, value)
                    VALUES ('default', 0, '$path', '$value')
                    ON DUPLICATE KEY UPDATE value='$value'"
        ;
        $conn->query($sql);
    }
}

function getConfigToApply($param) {
    switch ($param) {
        case '--enable-autocomplete':
            echo "Enabled Autocomplete";
            return [
                'algoliasearch/credentials/is_popup_enabled' => 1,
                'algoliasearch/credentials/is_instant_enabled' => 0,
            ];
        case '--enable-instantsearch':
            echo "Enabled InstantSearch";
            return [
                'algoliasearch/credentials/is_popup_enabled' => 0,
                'algoliasearch/credentials/is_instant_enabled' => 1,
            ];
        case '--default':
        default:
            return [
                'algoliasearch/credentials/is_popup_enabled' => 1,
                'algoliasearch/credentials/is_instant_enabled' => 0,
            ];
    }
}

setPersistantConfig(getConfigToApply($argv[1]));