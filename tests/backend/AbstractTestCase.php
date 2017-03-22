<?php

use PHPUnit\Framework\TestCase;

class AbstractTestCase extends TestCase
{
    protected $indexPrefix;

    /**
     * @var array Default value of general store settings (not related to algolia's extention)
     */
    protected $defaultConfig = array(
        'cataloginventory/options/show_out_of_stock' => '0',
    );

    public function setUp()
    {
        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');
        $this->indexPrefix = $config->getIndexPrefix();

        foreach ($this->defaultConfig as $name => $value) {
            setConfig($name, $value);
        }

        resetConfigs();
    }
}