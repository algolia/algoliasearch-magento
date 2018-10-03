<?php

class Algolia_Algoliasearch_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
    public function getConfigurationUrl()
    {
        return $this->getUrl('adminhtml/system_config/edit/section/algoliasearch');
    }

    public function showNotification()
    {
        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');

        return $config->showQueueNotificiation();
    }

    public function getQueueInfo()
    {
        /** @var Algolia_Algoliasearch_Helper_Config $config */
        $config = Mage::helper('algoliasearch/config');

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        $tableName = $resource->getTableName('algoliasearch/queue');

        $readConnection = $resource->getConnection('core_read');

        $size = (int) $readConnection->query('SELECT COUNT(*) as total_count FROM '.$tableName)->fetchColumn(0);
        $maxJobsPerSingleRun = $config->getNumberOfJobToRun();

        $etaMinutes = ceil($size / $maxJobsPerSingleRun) * 5; // 5 - assuming the queue runner runs every 5 minutes

        $eta = $etaMinutes.' minutes';
        if ($etaMinutes > 60) {
            $hours = floor($etaMinutes / 60);
            $restMinutes = $etaMinutes % 60;

            $eta = $hours.' hours '.$restMinutes.' minutes';
        }

        $queueInfo = array(
            'isEnabled' => $config->isQueueActive(),
            'currentSize' => $size,
            'eta' => $eta,
        );

        return $queueInfo;
    }

    /**
     * Show notification based on condition
     *
     * @return bool
     */
    protected function _toHtml()
    {
        $queueInfo = $this->getQueueInfo();
        if ($this->showNotification()
            && $queueInfo['isEnabled'] === true
            && $queueInfo['currentSize'] > 0) {

            return parent::_toHtml();
        }

        return '';
    }

}
