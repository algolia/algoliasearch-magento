<?php

class Algolia_Algoliasearch_Block_Adminhtml_Indexingqueue_Grid_Renderer_Json extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $html = '';
        if ($json = $row->getData('data')) {
            $json = json_decode($json, true);

            foreach ($json as $var => $value) {
                $html .= $var . ': ' . (is_array($value) ? implode(',', $value) : $value) . '<br/>';
            }
        }
        return $html;
    }
}
