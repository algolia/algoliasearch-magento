<?php

class Algolia_Algoliasearch_Model_System_Config_Backend_EnableClickAnalytics extends Mage_Core_Model_Config_Data
{
    protected function _beforeSave()
    {
        $value = trim($this->getValue());

        if ($value !== '1') {
            return parent::_beforeSave();
        }

        $context = Mage::helper('algoliasearch/algoliahelper')->getClient()->getContext();

        $ch = curl_init();

        $headers = array();
        $headers[] = 'X-Algolia-Api-Key: '.$context->apiKey;
        $headers[] = 'X-Algolia-Application-Id: '.$context->applicationID;
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $postFields = json_encode(array(
            'timestamp' => time(),
            'queryID' => 'a',
            'objectID' => 'non_existent_object_id',
            'position' => 1,
        ));

        curl_setopt($ch, CURLOPT_URL, "https://insights.algolia.io/1/searches/click");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_POST, 1);

        $result = curl_exec($ch);
        curl_close($ch);

        if ($result) {
            $result = json_decode($result);
            if ($result->status === 401 && $result->message === 'Feature not available') {
                Mage::throwException(
                    Mage::helper('algoliasearch')->__('Click & Conversion analytics are not supported on your current plan. Please refer to <a target="_blank" href="https://www.algolia.com/pricing/">Algolia\'s pricing page</a> for more details.')
                );
            }
        }

        return parent::_beforeSave();
    }
}
