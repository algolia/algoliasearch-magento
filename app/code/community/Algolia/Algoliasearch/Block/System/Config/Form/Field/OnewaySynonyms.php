<?php

class Algolia_Algoliasearch_Block_System_Config_Form_Field_OnewaySynonyms extends Algolia_Algoliasearch_Block_System_Config_Form_Field_AbstractField
{
    public function __construct()
    {
        $this->settings = array(
            'columns' => array(
                'input' => array(
                    'label' => 'Input',
                    'style' => 'width: 100px;',
                ),
                'synonyms' => array(
                    'label' => 'Synonyms (comma-separated)',
                    'style' => 'width: 435px;',
                ),
            ),
            'buttonLabel' => 'Add One-way Synonyms',
            'addAfter'    => false,
        );

        parent::__construct();
    }
}
