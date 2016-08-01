<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_ExcludedPages extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
{
    public function __construct()
    {
        $this->settings = array(
            'columns' => array(
                'pages' => array(
                    'label'   => 'Pages',
                    'options' => function () {
                        $options = array();

                        /** @var Mage_Cms_Model_Resource_Page_Collection $magento_pages */
                        $magento_pages = Mage::getModel('cms/page')->getCollection()->addFieldToFilter('is_active', 1);

                        $ids = $magento_pages->toOptionArray();
                        foreach ($ids as $id) {
                            $options[$id['value']] = $id['value'];
                        }

                        return $options;
                    },
                    'rowMethod' => 'getPages',
                    'width'     => 230,
                ),
            ),
            'buttonLabel' => 'Add Excluded Page',
            'addAfter'    => false,
        );

        parent::__construct();
    }
}
