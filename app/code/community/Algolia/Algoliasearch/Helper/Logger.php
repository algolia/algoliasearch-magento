<?php

class Algolia_Algoliasearch_Helper_Logger extends Mage_Core_Helper_Abstract
{
    /** @var Algolia_Algoliasearch_Helper_Config */
    protected $config;

    protected $enabled;
    protected $timers = array();
    protected $stores = array();

    public function __construct()
    {
        $this->config = Mage::helper('algoliasearch/config');
        $this->enabled = $this->config->isLoggingEnabled();

        /** @var Mage_Core_Model_Store $store */
        foreach (Mage::app()->getStores() as $store) {
            $this->stores[$store->getId()] = $store->getName();
        }
    }

    public function isEnable()
    {
        return $this->enabled;
    }

    public function getStoreName($storeId)
    {
        if ($storeId === null) {
            return 'undefined store';
        }

        return $storeId.' ('.$this->stores[$storeId].')';
    }

    public function start($action)
    {
        if ($this->enabled == false) {
            return;
        }

        $this->log('');
        $this->log('');
        $this->log('>>>>> BEGIN '.$action);
        $this->timers[$action] = microtime(true);
    }

    public function stop($action)
    {
        if ($this->enabled == false) {
            return;
        }

        if (false === isset($this->timers[$action])) {
            throw new Exception('Algolia Logger => non existing action');
        }

        $this->log('<<<<< END '.$action.' ('.$this->formatTime($this->timers[$action], microtime(true)).')');
    }

    public function log($message, $forceLog = false)
    {
        if ($this->config->isLoggingEnabled() || $forceLog) {
            Mage::log($message, null, 'algolia.log');
        }
    }

    protected function formatTime($begin, $end)
    {
        return ($end - $begin).'sec';
    }
}
