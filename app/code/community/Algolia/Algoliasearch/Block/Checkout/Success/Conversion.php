<?php

class Algolia_Algoliasearch_Block_Checkout_Success_Conversion extends Mage_Core_Block_Template
{
    /** @var Mage_Sales_Model_Order */
    protected $_order;

    protected function _construct()
    {
        parent::_construct();

        if ($orderId = Mage::getSingleton('checkout/session')->getLastOrderId()) {
            $this->_order = Mage::getModel('sales/order')->load($orderId);
        }
    }

    /**
     * @return string
     */
    public function getOrderItemsConversionJson()
    {
        $orderItemsData = array();
        $orderItems = $this->_order->getAllVisibleItems();

        /** @var Item $item */
        foreach ($orderItems as $item) {
            if ($item->getData('algoliasearch_query_param') !== '') {
                $orderItemsData[$item->getProductId()] = json_decode($item->getData('algoliasearch_query_param'));
            }
        }

        return Mage::helper('core')->jsonEncode($orderItemsData);
    }

    public function _toHtml()
    {
        if ($this->_order
            && Mage::helper('algoliasearch/config')->isClickConversionAnalyticsEnabled($this->_order->getStoreId())
            && Mage::helper('algoliasearch/config')->getConversionAnalyticsMode($this->_order->getStoreId()) === 'place_order'
        ) {
            return parent::_toHtml();
        }

        return '';
    }
}
