<?php

class Algolia_Algoliasearch_Block_Adminhtml_Indexingqueue_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * @return Algolia_Algoliasearch_Block_Adminhtml_IndexingQueue_Edit_Form
     */
    protected function _prepareForm()
    {
        $model = Mage::registry('algoliasearch_current_job');

        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/updatePost'),
            'method' => 'post',
        ));

        $fieldset = $form->addFieldset('base_fieldset', array());
        $readOnlyStyle = 'border: 0; background: none;';

        $fieldset->addField('job_id', 'text', array(
            'name' => 'job_id',
            'label' => Mage::helper('algoliasearch')->__('Job ID'),
            'title' => Mage::helper('algoliasearch')->__('Job ID'),
            'readonly' => true,
            'style' => $readOnlyStyle,
        ));

        $fieldset->addField('created', 'text', array(
            'name' => 'created',
            'label' => Mage::helper('algoliasearch')->__('Created'),
            'title' => Mage::helper('algoliasearch')->__('Created'),
            'readonly' => true,
            'style' => $readOnlyStyle,
        ));

        $fieldset->addField('status', 'text', array(
            'name' => 'status',
            'label' => Mage::helper('algoliasearch')->__('Status'),
            'title' => Mage::helper('algoliasearch')->__('Status'),
            'readonly' => true,
            'style' => $readOnlyStyle,
        ));

        $fieldset->addField('pid', 'text', array(
            'name' => 'pid',
            'label' => Mage::helper('algoliasearch')->__('PID'),
            'title' => Mage::helper('algoliasearch')->__('PID'),
            'readonly' => true,
            'style' => $readOnlyStyle,
        ));

        $fieldset->addField('class', 'text', array(
            'name' => 'class',
            'label' => Mage::helper('algoliasearch')->__('Class'),
            'title' => Mage::helper('algoliasearch')->__('Class'),
            'readonly' => true,
            'style' => $readOnlyStyle,
        ));

        $fieldset->addField('method', 'text', array(
            'name' => 'method',
            'label' => Mage::helper('algoliasearch')->__('Method'),
            'title' => Mage::helper('algoliasearch')->__('Method'),
            'readonly' => true,
            'style' => $readOnlyStyle,
        ));

        $fieldset->addField('data', 'textarea', array(
            'name' => 'data',
            'label' => Mage::helper('algoliasearch')->__('Data'),
            'title' => Mage::helper('algoliasearch')->__('Data'),
            'readonly' => true,
        ));

        $fieldset->addField('max_retries', 'text', array(
            'name' => 'max_retries',
            'label' => Mage::helper('algoliasearch')->__('Max Retries'),
            'title' => Mage::helper('algoliasearch')->__('Max Retries'),
            'readonly' => true,
            'style' => $readOnlyStyle,
        ));

        $fieldset->addField('retries', 'text', array(
            'name' => 'retries',
            'label' => Mage::helper('algoliasearch')->__('Retries'),
            'title' => Mage::helper('algoliasearch')->__('Retries'),
            'readonly' => true,
            'style' => $readOnlyStyle,
        ));

        $fieldset->addField('data_size', 'text', array(
            'name' => 'data_size',
            'label' => Mage::helper('algoliasearch')->__('Data Size'),
            'title' => Mage::helper('algoliasearch')->__('Data Size'),
            'readonly' => true,
            'style' => $readOnlyStyle,
        ));

        $fieldset->addField('error_log', 'textarea', array(
            'name' => 'error_log',
            'label' => Mage::helper('algoliasearch')->__('Error Log'),
            'title' => Mage::helper('algoliasearch')->__('Error Log'),
            'readonly' => true,
        ));


        $form->setValues($model->getData());
        $form->addValues(array(
            'status' => $model->getStatusLabel()
        ));
        $form->setUseContainer(true);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
