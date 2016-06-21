<?php

if (class_exists('AlgoliaSearch\Client', false) == false) {
    require_once Mage::getBaseDir('lib').'/AlgoliaSearch/loader.php';
}

class Algolia_Algoliasearch_Helper_Algoliahelper extends Mage_Core_Helper_Abstract
{
    /** @var \AlgoliaSearch\Client */
    protected $client;

    /** @var Algolia_Algoliasearch_Helper_Config */
    protected $config;

    public function __construct()
    {
        $this->config = Mage::helper('algoliasearch/config');
        $this->resetCredentialsFromConfig();

        $version = $this->config->getExtensionVersion();
        \AlgoliaSearch\Version::$custom_value = ' Magento ('.$version.')';
    }

    public function resetCredentialsFromConfig()
    {
        if ($this->config->getApplicationID() && $this->config->getAPIKey()) {
            $this->client = new \AlgoliaSearch\Client($this->config->getApplicationID(), $this->config->getAPIKey());
        }
    }

    public function generateSearchSecuredApiKey($key, $params = [])
    {
        return $this->client->generateSecuredApiKey($key, $params);
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
        $onlineSettings = [];

        try {
            $onlineSettings = $this->getIndex($index_name)->getSettings();
        } catch (\Exception $e) {
        }

        $removes = ['slaves'];

        foreach ($removes as $remove) {
            if (isset($onlineSettings[$remove])) {
                unset($onlineSettings[$remove]);
            }
        }

        foreach ($settings as $key => $value) {
            $onlineSettings[$key] = $value;
        }

        return $onlineSettings;
    }

    public function handleTooBigRecords(&$objects, $index_name)
    {
        $long_attributes = ['description', 'short_description', 'meta_description', 'content'];

        $good_size = true;

        $ids = [];

        foreach ($objects as $key => &$object) {
            $size = mb_strlen(json_encode($object));

            if ($size > 20000) {
                $good_size = false;

                foreach ($long_attributes as $attribute) {
                    if (isset($object[$attribute])) {
                        unset($object[$attribute]);
                        $ids[$index_name.' objectID('.$object['objectID'].')'] = true;
                    }
                }

                $size = mb_strlen(json_encode($object));

                if ($size > 20000) {
                    unset($objects[$key]);
                }
            }
        }

        if (count($objects) <= 0) {
            return;
        }

        if ($good_size === false) {
            /** @var Mage_Adminhtml_Model_Session $session */
            $session = Mage::getSingleton('adminhtml/session');
            $session->addError('Algolia reindexing : You have some records ('.implode(',',
                        array_keys($ids)).') that are too big. They have either been truncated or skipped');
        }
    }

    public function addObjects($objects, $index_name)
    {
        $this->handleTooBigRecords($objects, $index_name);

        $index = $this->getIndex($index_name);

        if ($this->config->isPartialUpdateEnabled()) {
            $index->partialUpdateObjects($objects);
        } else {
            $index->addObjects($objects);
        }
    }

    public function setSynonyms($index_name, $synonyms)
    {
        if (empty($synonyms)) {
            return;
        }

        $index = $this->getIndex($index_name);

        /**
         * Placeholders and alternative corrections are handled directly in Algolia dashboard.
         * To keep it works, we need to merge it before setting synonyms to Algolia indices.
         */
        $hitsPerPage = 100;
        $page = 0;
        do {
            $complexSynonyms = $index->searchSynonyms('', ['altCorrection1', 'altCorrection2', 'placeholder'], $page, $hitsPerPage);
            foreach ($complexSynonyms['hits'] as $hit) {
                unset($hit['_highlightResult']);

                $synonyms[] = $hit;
            }

            $page++;
        } while (($page * $hitsPerPage) < $complexSynonyms['nbHits']);

        $index->batchSynonyms($synonyms, true, true);
    }
}
