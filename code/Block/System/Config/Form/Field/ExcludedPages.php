<?php

/**
 * Algolia custom sort order field.
 */
class Algolia_Algoliasearch_Block_System_Config_Form_Field_ExcludedPages extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
{
    public function __construct()
    {
        $this->settings = [
            'columns' => [
                'pages' => [
                    'label' => 'Pages',
                    'renderer' => 'pages',
                    'rowMethod' => 'getPages',
                ],
            ],
            'buttonLabel' => 'Add Excluded Page',
            'addAfter' => false,
        ];

        parent::__construct();
    }
}
