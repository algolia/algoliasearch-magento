<?php

abstract class Algolia_Algoliasearch_Helper_Entity_Helper
{
    protected $config;

    abstract protected function getIndexNameSuffix();

    public function __construct()
    {
        $this->config = new Algolia_Algoliasearch_Helper_Config();
    }

    public function getBaseIndexName($storeId = null)
    {
        return (string) $this->config->getIndexPrefix($storeId) . Mage::app()->getStore($storeId)->getCode();
    }

    public function getIndexName($storeId = null)
    {
        return (string) $this->getBaseIndexName($storeId) . $this->getIndexNameSuffix();
    }

    protected function try_cast($value)
    {
        if (is_numeric($value) && floatval($value) == floatval(intval($value)))
            return intval($value);

        if (is_numeric($value))
            return floatval($value);

        return $value;
    }

    protected function castProductObject(&$productData)
    {
        foreach ($productData as $key => &$data)
        {
            $data = $this->try_cast($data);

            if (is_array($data) === false)
            {
                $data = explode('|', $data);

                if (count($data) == 1)
                {
                    $data = $data[0];
                    $data = $this->try_cast($data);
                }
                else
                    foreach($data as &$element)
                        $element = $this->try_cast($element);
            }
        }
    }

    protected function strip($s)
    {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        $s = preg_replace('/&nbsp;/', ' ', $s);
        $s = preg_replace('!\s+!', ' ', $s);
        return trim(strip_tags($s));
    }
}