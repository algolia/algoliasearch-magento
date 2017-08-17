<?php

class Algolia_Algoliasearch_Adminhtml_QueueController extends Mage_Adminhtml_Controller_Action
{
    public function _isAllowed()
    {
        return true;
    }

    public function indexAction()
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

        $eta = $etaMinutes . ' minutes';
        if ($etaMinutes > 60) {
            $hours = floor($etaMinutes / 60);
            $restMinutes = $etaMinutes % 60;

            $eta = $hours . ' hours ' . $restMinutes . ' minutes';
        }

        $queueInfo = array(
            'isEnabled' => $config->isQueueActive(),
            'currentSize' => $size,
            'eta' => $eta,
        );

        $this->sendResponse($queueInfo);
    }

    public function truncateAction()
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        $tableName = $resource->getTableName('algoliasearch/queue');

        try {
            $writeConnection = $resource->getConnection('core_write');
            $writeConnection->query('TRUNCATE TABLE '.$tableName);

            $status = array('status' => 'ok');
        } catch (\Exception $e) {
            $status = array('status' => 'ko', 'message' => $e->getMessage());
        }

        $this->sendResponse($status);
    }

    private function sendResponse($data)
    {
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode($data));
    }
}
