<?php

$installer = $this; /** @var $installer Mage_Eav_Model_Entity_Setup */

$installer->startSetup();

$attributeName = 'exclude_from_search';

if (!$installer->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeName)) {
    $installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'exclude_from_search', array(
        'type' => 'int',
        'input' => 'select',
        'label' => 'Exclude From Search',
        'required' => false,
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
        'source' => 'eav/entity_attribute_source_boolean'
    ));
}

$installer->endSetup();