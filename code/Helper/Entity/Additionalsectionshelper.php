<?php

class Algolia_Algoliasearch_Helper_Entity_Additionalsectionshelper extends Algolia_Algoliasearch_Helper_Entity_Helper
{
    protected function getIndexNameSuffix()
    {
        return '_section';
    }

    public function getIndexSettings($storeId)
    {
        return [
            'attributesToIndex' => ['value'],
        ];
    }

    public function getAttributeValues($storeId, $section)
    {
        /** @var Mage_Catalog_Model_Product_Visibility $catalogProductVisibility */
        $catalogProductVisibility = Mage::getSingleton('catalog/product_visibility');

        $attributeCode = $section['name'];

        /** @var Mage_Catalog_Model_Resource_Product_Collection $products */
        $products = Mage::getResourceModel('catalog/product_collection')->addStoreFilter($storeId)
                        ->addAttributeToFilter('visibility',
                            ['in' => $catalogProductVisibility->getVisibleInSearchIds()])
                        ->addAttributeToFilter('status',
                            ['eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED])
                        ->addAttributeToFilter($attributeCode, ['notnull' => true])
                        ->addAttributeToFilter($attributeCode, ['neq' => ''])
                        ->addAttributeToSelect($attributeCode);

        $usedAttributeValues = array_keys(array_flip(// array unique
            explode(',', implode(',', $products->getColumnValues($attributeCode)))));

        /** @var Mage_Eav_Model_Config $eavConfig */
        $eavConfig = Mage::getSingleton('eav/config');

        /** @var Mage_Eav_Model_Attribute $attributeModel */
        $attributeModel = $eavConfig->getAttribute('catalog_product', $attributeCode);
        $attributeModel->setStoreId($storeId);

        $values = $attributeModel->getSource()->getOptionText(implode(',', $usedAttributeValues));

        if (!$values || count($values) == 0) {
            $values = array_unique($products->getColumnValues($attributeCode));
        }

        if ($values && is_array($values) == false) {
            $values = [$values];
        }

        $values = array_map(function ($value) use ($section, $storeId) {

            $record = [
                'objectID' => $value,
                'value'    => $value,
            ];

            $transport = new Varien_Object($record);

            Mage::dispatchEvent('algolia_additional_section_item_index_before',
                ['section' => $section, 'record' => $transport, 'store_id' => $storeId]);

            $record = $transport->getData();

            return $record;
        }, $values);

        return $values;
    }
}
