<?php

class Algolia_Algoliasearch_Model_Catalog_Resource_Eav_Mysql4_Product_Collection extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{
    public function addItem(Varien_Object $item)
    {
        $itemId = $this->_getItemId($item);

        if (!is_null($itemId)) {
            if (isset($this->_items[$itemId])) {
                // Fail silently
                return $this;
            }

            $this->_items[$itemId] = $item;
        } else {
            $this->_addItem($item);
        }

        return $this;
    }
}
