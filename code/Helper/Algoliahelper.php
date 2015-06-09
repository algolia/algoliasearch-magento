<?php

if (class_exists('AlgoliaSearch\Client', false) == false)
{
    require_once 'AlgoliaSearch/Version.php';
    require_once 'AlgoliaSearch/AlgoliaException.php';
    require_once 'AlgoliaSearch/ClientContext.php';
    require_once 'AlgoliaSearch/Client.php';
    require_once 'AlgoliaSearch/Index.php';
}

class Algolia_Algoliasearch_Helper_Algoliahelper extends Mage_Core_Helper_Abstract
{
    private $client;

    public function __construct()
    {
        $config = new Algolia_Algoliasearch_Helper_Config();

        if ($config->getApplicationID() && $config->getAPIKey())
            $this->client = new \AlgoliaSearch\Client($config->getApplicationID(), $config->getAPIKey());
    }

    public function getIndex($name)
    {
        return $this->client->initIndex($name);
    }

    public function listIndexes()
    {
        return $this->client->listIndexes();
    }

    public function query($index_name, $q, $params)
    {
        return $this->client->initIndex($index_name)->search($q, $params);
    }

    public function setSettings($indexName, $settings)
    {
        $index = $this->getIndex($indexName);

        $index->setSettings($settings);
    }

    public function deleteIndex($index_name)
    {
        $this->client->deleteIndex($index_name);
    }

    public function deleteObjects($ids, $index_name)
    {
        $index = $this->getIndex($index_name);

        $index->deleteObjects($ids);
    }

    public function moveIndex($index_name_tmp, $index_name)
    {
        $this->client->moveIndex($index_name_tmp, $index_name);
    }

    public function mergeSettings($index_name, $settings)
    {
        $onlineSettings = array();

        try
        {
            $onlineSettings = $this->getIndex($index_name)->getSettings();
        }
        catch(\Exception $e)
        {
        }

        $removes = array('slaves');

        foreach ($removes as $remove)
            if (isset($onlineSettings[$remove]))
                unset($onlineSettings[$remove]);

        foreach ($settings as $key => $value)
            $onlineSettings[$key] = $value;

        return $onlineSettings;
    }

    public function addObjects($objects, $index_name)
    {
        $index = $this->getIndex($index_name);

        $index->addObjects($objects);
    }
}