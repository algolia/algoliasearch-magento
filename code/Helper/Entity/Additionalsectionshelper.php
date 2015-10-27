<?php

class Algolia_Algoliasearch_Helper_Entity_Additionalsectionshelper extends Algolia_Algoliasearch_Helper_Entity_Helper
{
    protected function getIndexNameSuffix()
    {
        return '_section';
    }

    public function getIndexSettings($storeId)
    {
        return array(
            'attributesToIndex'         => array('value'),
        );
    }

    public function getAttributeValues($storeId, $section)
    {
        $attributeCode = $section['name'];

        $products = Mage::getResourceModel('catalog/product_collection')
            ->addStoreFilter($storeId)
            ->addAttributeToFilter('visibility', array('in' => Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds()))
            ->addAttributeToFilter($attributeCode, array('notnull' => true))
            ->addAttributeToFilter($attributeCode, array('neq' => ''))
            ->addAttributeToSelect($attributeCode);

        $usedAttributeValues = array_unique($products->getColumnValues($attributeCode));

        $attributeModel = Mage::getSingleton('eav/config')
            ->getAttribute('catalog_product', $attributeCode)
            ->setStoreId($storeId);

        $values = $attributeModel->getSource()->getOptionText(
            implode(',', $usedAttributeValues)
        );

        if (! $values || count($values) == 0)
        {
            $values = array_unique($products->getColumnValues($attributeCode));
        }

        if ($values && is_array($values) == false)
        {
            $values = array($values);
        }

        $values = array_map(function ($value) use ($section) {

            $record = array(
                'objectID'  => $value,
                'value'     => $value
            );

            $transport = new Varien_Object($record);

            Mage::dispatchEvent('algolia_additional_section_item_index_before', array('section' => $section, 'record' => $transport));

            $record = $transport->getData();

            return $record;
        }, $values);

        return $values;
    }
}