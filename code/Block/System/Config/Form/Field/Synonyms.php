<?php

class Algolia_Algoliasearch_Block_System_Config_Form_Field_Synonyms extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
{
    public function __construct()
    {
        $this->settings = [
            'columns' => [
                'synonyms' => [
                    'label' => 'Synonyms (comma-separated)',
                    'style' => 'width: 550px;',
                ],
            ],
            'buttonLabel' => 'Add Synonyms',
            'addAfter'    => false,
        ];

        parent::__construct();
    }
}
