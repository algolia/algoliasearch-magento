<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_AdditionalSections extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
{
    public function __construct()
    {
        $this->settings = [
            'columns' => [
                'name' => [
                    'label' => 'Section',
                    'renderer' => 'name',
                    'rowMethod' => 'getName',
                ],
            ],
            'buttonLabel' => 'Add Section',
            'addAfter' => false,
        ];

        parent::__construct();

        $this->addColumn('label', [
            'label' => Mage::helper('adminhtml')->__('Label'),
            'style' => 'width: 100px;',
        ]);

        $this->addColumn('hitsPerPage', [
            'label' => Mage::helper('adminhtml')->__('Hits per page'),
            'style' => 'width: 100px;',
            'class' => 'required-entry input-text validate-number',
        ]);
    }
}
