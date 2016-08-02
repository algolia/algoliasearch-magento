<?php
/**
 * Source model for select image type.
 */
class Algolia_Algoliasearch_Model_System_Imagetype
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'image',         'label' => Mage::helper('core')->__('Base Image')),
            array('value' => 'small_image',   'label' => Mage::helper('core')->__('Small Image')),
            array('value' => 'thumbnail',     'label' => Mage::helper('core')->__('Thumbnail')),
        );
    }
}
