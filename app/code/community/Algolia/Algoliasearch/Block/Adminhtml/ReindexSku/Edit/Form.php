<?php

class Algolia_Algoliasearch_Block_Adminhtml_ReindexSku_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {

    /**
     * @return Algolia_AlgoliaSearch_Block_Adminhtml_ReindexSku_Edit_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/reindexPost'),
            'method'  => 'post',
            // 'enctype' => 'multipart/form-data'
        ));

        $fieldset = $form->addFieldset('base_fieldset', array());

        $html = '</br></br>';
        $html .= '<p>' . __('Enter here the SKU(s) you want to reindex separated by commas or carriage returns.') . '</p>';
        $html .= '<p>' . __('You will be notified if there is any reason why your product can\'t be reindexed.') . '</p>';
        $html .= '<p>' . __('It can be :') . '</p>';
        $html .= '<ul style="list-style: disc; padding-left: 25px;">';
        $html .= '<li>' . __('Product is disabled.') . '</li>';
        $html .= '<li>' . __('Product is deleted.') . '</li>';
        $html .= '<li>' . __('Product is out of stock.') . '</li>';
        $html .= '<li>' . __('Product is not visible.') . '</li>';
        $html .= '<li>' . __('Product is not related to the store.') . '</li>';
        $html .= '</ul>';
        $html .= '<p>' . __('You can reindex up to 10 SKUs at once.') . '</p>';

        $fieldset->addField('skus', 'textarea', array(
            'name'      => 'skus',
            'label'     => Mage::helper('algoliasearch')->__('Product SKU(s)'),
            'title'     => Mage::helper('algoliasearch')->__('Product SKU(s)'),
            'required'  => true,
            'style'     => 'width:100%',
            'after_element_html' => $html
        ));

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}