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

    /** @var int */
    protected $maxRecordSize = 20000;

    /** @var array */
    protected $potentiallyLongAttributes = array('description', 'short_description', 'meta_description', 'content');

    /** @var string */
    private $lastUsedIndexName;

    /** @var int */
    private $lastTaskId;

    public function __construct()
    {
        $this->config = Mage::helper('algoliasearch/config');
        $this->resetCredentialsFromConfig();

        $version = $this->config->getExtensionVersion();

        \AlgoliaSearch\Version::addPrefixUserAgentSegment('Magento integration', $version);
        \AlgoliaSearch\Version::addSuffixUserAgentSegment('PHP', phpversion());
        \AlgoliaSearch\Version::addSuffixUserAgentSegment('Magento', Mage::getVersion());

        if (method_exists('Mage', 'getEdition')) {
            \AlgoliaSearch\Version::addSuffixUserAgentSegment('Edition', Mage::getEdition());
        }
    }

    public function resetCredentialsFromConfig()
    {
        if ($this->config->getApplicationID() && $this->config->getAPIKey()) {
            $this->client = new \AlgoliaSearch\Client($this->config->getApplicationID(), $this->config->getAPIKey());
        }
    }

    public function generateSearchSecuredApiKey($key, $params = array())
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

    public function query($indexName, $q, $params)
    {
        return $this->client->initIndex($indexName)->search($q, $params);
    }

    public function getObjects($indexName, $objectIds)
    {
        return $this->getIndex($indexName)->getObjects($objectIds);
    }

    public function setSettings($indexName, $settings, $forwardToReplicas = false)
    {
        $index = $this->getIndex($indexName);

        $res = $index->setSettings($settings, $forwardToReplicas);

        $this->lastUsedIndexName = $indexName;
        $this->lastTaskId = $res['taskID'];
    }

    public function clearIndex($indexName)
    {
        $res =$this->getIndex($indexName)->clearIndex();

        $this->lastUsedIndexName = $indexName;
        $this->lastTaskId = $res['taskID'];
    }

    public function deleteIndex($indexName)
    {
        $res = $this->client->deleteIndex($indexName);

        $this->lastUsedIndexName = $indexName;
        $this->lastTaskId = $res['taskID'];
    }

    public function deleteObjects($ids, $indexName)
    {
        $index = $this->getIndex($indexName);

        $res = $index->deleteObjects($ids);

        $this->lastUsedIndexName = $indexName;
        $this->lastTaskId = $res['taskID'];
    }

    public function moveIndex($tmpIndexName, $indexName)
    {
        $res = $this->client->moveIndex($tmpIndexName, $indexName);

        $this->lastUsedIndexName = $indexName;
        $this->lastTaskId = $res['taskID'];
    }

    public function getSettings($indexName)
    {
        return $this->getIndex($indexName)->getSettings();
    }

    public function mergeSettings($indexName, $settings)
    {
        $onlineSettings = array();

        try {
            $onlineSettings = $this->getSettings();
        } catch (\Exception $e) {
        }

        if (isset($settings['attributesToIndex'])) {
            $settings['searchableAttributes'] = $settings['attributesToIndex'];
            unset($settings['attributesToIndex']);
        }

        if (isset($onlineSettings['attributesToIndex'])) {
            $onlineSettings['searchableAttributes'] = $onlineSettings['attributesToIndex'];
            unset($onlineSettings['attributesToIndex']);
        }

        $removes = array('slaves', 'replicas');

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

    public function addObjects($objects, $indexName)
    {
        $this->prepareRecords($objects, $indexName);

        $index = $this->getIndex($indexName);

        if ($this->config->isPartialUpdateEnabled()) {
            $res = $index->partialUpdateObjects($objects);
        } else {
            $res = $index->addObjects($objects);
        }

        $this->lastUsedIndexName = $indexName;
        $this->lastTaskId = $res['taskID'];
    }

    public function setSynonyms($indexName, $synonyms)
    {
        $index = $this->getIndex($indexName);

        /*
         * Placeholders and alternative corrections are handled directly in Algolia dashboard.
         * To keep it works, we need to merge it before setting synonyms to Algolia indices.
         */
        $hitsPerPage = 100;
        $page = 0;
        do {
            $complexSynonyms = $index->searchSynonyms('', array('altCorrection1', 'altCorrection2', 'placeholder'), $page, $hitsPerPage);
            foreach ($complexSynonyms['hits'] as $hit) {
                unset($hit['_highlightResult']);

                $synonyms[] = $hit;
            }

            $page++;
        } while (($page * $hitsPerPage) < $complexSynonyms['nbHits']);

        if (empty($synonyms)) {
            $res = $index->clearSynonyms(true);
        } else {
            $res = $index->batchSynonyms($synonyms, true, true);
        }

        $this->lastUsedIndexName = $indexName;
        $this->lastTaskId = $res['taskID'];
    }

    public function copySynonyms($fromIndexName, $toIndexName)
    {
        $fromIndex = $this->getIndex($fromIndexName);
        $toIndex = $this->getIndex($toIndexName);

        $synonymsToSet = array();

        $hitsPerPage = 100;
        $page = 0;
        do {
            $fetchedSynonyms = $fromIndex->searchSynonyms('', array(), $page, $hitsPerPage);
            foreach ($fetchedSynonyms['hits'] as $hit) {
                unset($hit['_highlightResult']);

                $synonymsToSet[] = $hit;
            }

            $page++;
        } while (($page * $hitsPerPage) < $fetchedSynonyms['nbHits']);

        if (empty($synonymsToSet)) {
            $res = $toIndex->clearSynonyms(true);
        } else {
            $res = $toIndex->batchSynonyms($synonymsToSet, true, true);
        }

        $this->lastUsedIndexName = $toIndex;
        $this->lastTaskId = $res['taskID'];
    }

    public function copyQueryRules($fromIndexName, $toIndexName)
    {
        $fromIndex = $this->getIndex($fromIndexName);
        $toIndex = $this->getIndex($toIndexName);

        $queryRulesToSet = array();

        $hitsPerPage = 100;
        $page = 0;
        do {
            $fetchedQueryRules = $fromIndex->searchRules(array(
                'page' => $page,
                'hitsPerPage' => $hitsPerPage,
            ));

            foreach ($fetchedQueryRules['hits'] as $hit) {
                unset($hit['_highlightResult']);

                $queryRulesToSet[] = $hit;
            }

            $page++;
        } while (($page * $hitsPerPage) < $fetchedQueryRules['nbHits']);

        if (empty($queryRulesToSet)) {
            $res = $toIndex->clearRules(true);
        } else {
            $res = $toIndex->batchRules($queryRulesToSet, true, true);
        }

        $this->lastUsedIndexName = $toIndex;
        $this->lastTaskId = $res['taskID'];
    }

    public function waitLastTask($lastUsedIndexName = null, $lastTaskId = null)
    {
        if ($lastUsedIndexName === null && isset($this->lastUsedIndexName)) {
            $lastUsedIndexName = $this->lastUsedIndexName;
        }

        if ($lastTaskId === null && isset($this->lastTaskId)) {
            $lastTaskId = $this->lastTaskId;
        }
        if (!$lastUsedIndexName || !$lastTaskId) {
            return;
        }

        $this->client->initIndex($lastUsedIndexName)->waitTask($lastTaskId);
    }

    private function prepareRecords(&$objects, $indexName)
    {
        $currentCET = new DateTime('now', new DateTimeZone('Europe/Paris'));
        $currentCET = $currentCET->format('Y-m-d H:i:s');

        $modifiedIds = array();

        foreach ($objects as $key => &$object) {
            $object['algoliaLastUpdateAtCET'] = $currentCET;

            $previousObject = $object;

            $object = $this->handleTooBigRecord($object);

            if ($object === false) {
                $longestAttribute = $this->getLongestAttribute($previousObject);
                $modifiedIds[] = $indexName.' - ID '.$previousObject['objectID'].' - skipped - longest attribute: '.$longestAttribute;

                unset($objects[$key]);
            } elseif ($previousObject !== $object) {
                $modifiedIds[] = $indexName.' - ID '.$previousObject['objectID'].' - truncated';
            }
        }

        if (!empty($modifiedIds)) {
            $separator = php_sapi_name() === 'cli' ? "\n" : '<br>';

            $errorMessage = 'Algolia reindexing: You have some records which are too big to be indexed in Algolia. They have either been truncated (removed attributes: '.implode(', ', $this->potentiallyLongAttributes).') or skipped completely: '.$separator.implode($separator, $modifiedIds);

            /** @var Mage_Adminhtml_Model_Session $session */
            $session = Mage::getSingleton('adminhtml/session');
            $session->addWarning($errorMessage);

            if (php_sapi_name() === 'cli') {
                echo $errorMessage . "\n";
            }
        }
    }

    public function handleTooBigRecord($object)
    {
        $size = mb_strlen(json_encode($object));

        if ($size > $this->maxRecordSize) {
            foreach ($this->potentiallyLongAttributes as $attribute) {
                if (isset($object[$attribute])) {
                    unset($object[$attribute]);
                }
            }

            $size = mb_strlen(json_encode($object));

            if ($size > $this->maxRecordSize) {
                $object = false;
            }
        }

        return $object;
    }

    private function getLongestAttribute($object)
    {
        $maxLength = 0;
        $longestAttribute = '';

        foreach ($object as $attribute => $value) {
            $attributeLenght = mb_strlen(json_encode($value));

            if ($attributeLenght > $maxLength) {
                $longestAttribute = $attribute;

                $maxLength = $attributeLenght;
            }
        }

        return $longestAttribute;
    }

    public function getLastIndexName()
    {
        return $this->lastUsedIndexName;
    }

    public function getLastTaskId()
    {
        return $this->lastTaskId;
    }
}
