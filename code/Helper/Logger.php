<?php

class Algolia_Algoliasearch_Helper_Logger extends Mage_Core_Helper_Abstract
{
    protected $enabled;
    protected $config;
    protected $timers = array();
    protected $stores = array();

    public function __construct()
    {
        $this->config = Mage::helper('algoliasearch/config');
        $this->enabled = $this->config->isLoggingEnabled();

        foreach (Mage::app()->getStores() as $store)
            $this->stores[$store->getId()] = $store->getName();
    }

    public function isEnable()
    {
        return $this->enabled;
    }

    public function getStoreName($storeId)
    {
        if ($storeId === null)
            return 'undefined store';

        return $storeId . ' (' . $this->stores[$storeId] . ')';
    }

    public function start($action)
    {
        if ($this->enabled == false)
            return;

        $this->log('');
        $this->log('');
        $this->log('>>>>> BEGIN '.$action);
        $this->timers[$action] = microtime(true);
    }

    public function stop($action)
    {
        if ($this->enabled == false)
            return;

        if (false === isset($this->timers[$action]))
            throw new Exception("Algolia Logger => non existing action");

        $this->log('<<<<< END ' .$action. ' (' . $this->formatTime($this->timers[$action], microtime(true)) . ')');
    }

    public function log($message)
    {
        if ($this->config->isLoggingEnabled()) {
            Mage::log($message, null, 'algolia.log');
        }
    }

    protected function formatTime($begin, $end)
    {
        return ($end - $begin).'sec';
    }
}
