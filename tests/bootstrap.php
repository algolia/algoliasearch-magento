<?php

include __DIR__.'/../vendor/autoload.php';

// Bootstrap Magento
include __DIR__.'/../../../app/Mage.php';

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

setConfig('dev/log/active', '1');

// Set Magento's base URLs
setConfig('web/secure/base_url', getenv('BASE_URL'));
setConfig('web/unsecure/base_url', getenv('BASE_URL'));

// Set Algolia API credentials
setConfig('algoliasearch/credentials/application_id', getenv('APPLICATION_ID'));
setConfig('algoliasearch/credentials/search_only_api_key', getenv('SEARCH_ONLY_API_KEY'));
setConfig('algoliasearch/credentials/api_key', Mage::helper('core')->encrypt(getenv('API_KEY')));

setConfig('algoliasearch/credentials/index_prefix', getenv('INDEX_PREFIX'));

/**
 * @param array $configs
 */
function resetConfigs($configs = array())
{
    $configXmlFile = __DIR__.'/../app/code/community/Algolia/Algoliasearch/etc/config.xml';

    $xml = simplexml_load_file($configXmlFile);

    foreach ($xml->default->algoliasearch->children() as $section => $subsections) {
        foreach ($subsections as $subsectionName => $subsection) {
            $shortcut = $section.'/'.$subsectionName;

            if (!empty($configs) && !in_array($shortcut, $configs, true)) {
                continue;
            }

            $sectionName = 'algoliasearch/'.$shortcut;
            $sectionValue = (string) $subsection;

            setConfig($sectionName, $sectionValue);
        }
    }
}

function setConfig($path, $value, $storeId = null)
{
    if ($storeId === null) {
        Mage::app()->getStore()->setConfig($path, $value);
    }

    for ($i = 1; $i <= 3; $i++) {
        if ($storeId !== null && $i !== $storeId) {
            continue;
        }

        Mage::app()->getStore($i)->setConfig($path, $value);
    }
}
