<?php

class Algolia_Algoliasearch_Model_Observer_Conversion
{
    protected $_analyticsParams = array(
        'queryID',
        'indexName',
        'objectID',
    );

    /**
     * @param null $storeId
     * @return bool
     */
    protected function _isOrderConversionTrackingEnabled($storeId = null)
    {
        return Mage::helper('algoliasearch/config')->isClickConversionAnalyticsEnabled($storeId)
            && Mage::helper('algoliasearch/config')->getConversionAnalyticsMode($storeId) === 'place_order';
    }

    /**
     * @event catalog_controller_product_init_before
     */
    public function setAlgoliaParamsToSession(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Controller_Front_Action $controllerAction */
        $controllerAction = $observer->getEvent()->getControllerAction();
        $params = $controllerAction->getRequest()->getParams();

        $checkoutSession = Mage::getSingleton('checkout/session');
        if (!$this->_isOrderConversionTrackingEnabled($checkoutSession->getQuote()->getStoreId())) {
            return;
        }

        if (isset($params['queryID'])) {
            $conversionData = array(
                'queryID' => $params['queryID'],
                'indexName' => $params['index'],
                'objectID' => $params['objectID'],
            );

            $session = Mage::getSingleton('core/session', array('name' => 'frontend'));
            $session->setData('algolia_conversion_parameters', Mage::helper('core')->jsonEncode($conversionData));
        }
    }

    /**
     * @event checkout_cart_product_add_after
     */
    public function saveAlgoliaParamToQuoteItem(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();

        if ($this->_isOrderConversionTrackingEnabled($product->getStoreId())) {
            $session = Mage::getSingleton('core/session');
            $quoteItem->setData('algoliasearch_query_param', $session->getData('algolia_conversion_parameters'));
            $session->unsetData('algolia_conversion_parameters');
        }
    }
}
