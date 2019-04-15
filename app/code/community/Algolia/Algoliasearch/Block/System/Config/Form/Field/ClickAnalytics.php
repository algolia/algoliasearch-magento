<?php

class Algolia_Algoliasearch_Block_System_Config_Form_Field_ClickAnalytics extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_showUpsell = false;

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        if (!$this->isClickAnalyticsEnabled($element)) {
            $element->setDisabled(true);
            $this->_showUpsell = true;
        }

        return parent::_getElementHtml($element);
    }

    /**
     * @return bool
     */
    public function isClickAnalyticsEnabled()
    {
        $proxyHelper = Mage::helper('algoliasearch/proxyHelper');
        $info = $proxyHelper->getClientConfigurationData();

        return isset($info['click_analytics']) && $info['click_analytics'] == 1;
    }

    protected function _decorateRowHtml($element, $html)
    {
        if (!$this->_showUpsell) {
            return parent::_decorateRowHtml($element, $html);
        }

        $additionalRow = '<tr class="algoliasearch-messages"><td colspan="3"><div class="algoliasearch-config-info icon-stars">';
        $additionalRow .= $this->__('To get access to this Algolia feature, please consider <a href="%s" target="_blank">upgrading to a higher plan.</a>',
            'https://www.algolia.com/pricing/');
        $additionalRow .= '</div></td></tr>';

        return '<tr id="row_' . $element->getHtmlId() . '">' . $html . '</tr>' . $additionalRow;
    }
}
