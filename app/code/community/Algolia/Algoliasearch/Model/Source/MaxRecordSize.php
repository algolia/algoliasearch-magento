<?php

class Algolia_Algoliasearch_Model_Source_MaxRecordSize
{
    protected $_options;

    public function toOptionArray()
    {

        if (!$this->_options) {
            $options[] = Algolia_Algoliasearch_Helper_Config::DEFAULT_MAX_RECORD_SIZE;
            $proxyHelper = Mage::helper('algoliasearch/proxyHelper');
            $clientData = $proxyHelper->getClientConfigurationData();

            if ($clientData && isset($clientData['max_record_size'])) {
                if (!in_array($clientData['max_record_size'], $options)) {
                    $options[] = $clientData['max_record_size'];
                }
            }

            rsort($options);

            $formattedOptions = array();
            foreach ($options as $option) {
                $formattedOptions[] = array(
                    'value' => $option,
                    'label' => $option,
                );
            }

            $this->_options = $formattedOptions;
        }
        return $this->_options;
    }
}
