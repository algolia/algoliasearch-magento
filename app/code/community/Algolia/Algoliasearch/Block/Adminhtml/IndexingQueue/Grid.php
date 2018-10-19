<?php

class Algolia_Algoliasearch_Block_Adminhtml_IndexingQueue_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Initialize Grid Properties
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('job_id');
        $this->setDefaultSort('job_id');
        $this->setDefaultDir('desc');
    }

    /**
     * Prepare Search Report collection for grid
     *
     * @return Mage_Adminhtml_Block_Report_Search_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('algoliasearch/job_collection');
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare Grid columns
     *
     * @return Mage_Adminhtml_Block_Report_Search_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn('job_id', array(
            'header' => Mage::helper('algoliasearch')->__('Job ID'),
            'width' => '50px',
            'filter' => false,
            'index' => 'job_id',
            'type' => 'number'
        ));

        $this->addColumn('created', array(
            'header' => Mage::helper('algoliasearch')->__('Created'),
            'index' => 'created',
            'type' => 'datetime',
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('algoliasearch')->__('Status'),
            'index' => 'status',
            'getter' => 'getStatusLabel',
            'filter' => false,
        ));

        $this->addColumn('method', array(
            'header' => Mage::helper('algoliasearch')->__('Method'),
            'index' => 'method',
            'type' => 'options',
            'options' => Mage::getModel('algoliasearch/source_jobMethods')->getMethods(),
        ));

        $this->addColumn('data', array(
            'header' => Mage::helper('algoliasearch')->__('Data'),
            'index' => 'data',
            'renderer' => 'Algolia_Algoliasearch_Block_Adminhtml_IndexingQueue_Grid_Renderer_Json'
        ));

        $this->addColumn('max_retries', array(
            'header' => Mage::helper('algoliasearch')->__('Max Retries'),
            'width' => '40px',
            'filter' => false,
            'index' => 'max_retries',
            'type' => 'number'
        ));

        $this->addColumn('retries', array(
            'header' => Mage::helper('algoliasearch')->__('Retries'),
            'width' => '40px',
            'filter' => false,
            'index' => 'retries',
            'type' => 'number'
        ));

        return parent::_prepareColumns();
    }

    /**
     * Retrieve Row Click callback URL
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/view', array('id' => $row->getJobId()));
    }
}
