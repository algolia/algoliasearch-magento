<?php

class Algolia_Algoliasearch_Helper_Entity_Pagehelper extends Algolia_Algoliasearch_Helper_Entity_Helper
{
    protected function getIndexNameSuffix()
    {
        return '_pages';
    }

    public function getIndexSettings($storeId)
    {
        return array(
            'attributesToIndex'         => array('slug', 'name', 'unordered(content)'),
            'attributesToSnippet'       => array('content:7')
        );
    }

    public function getPages($storeId)
    {
        $magento_pages = Mage::getModel('cms/page')->getCollection()->addFieldToFilter('is_active',1);

        $ids = $magento_pages->toOptionArray();

        $excluded_pages = array_values($this->config->getExcludedPages());

        foreach ($excluded_pages as &$excluded_page)
            $excluded_page = $excluded_page['pages'];

        $pages = array();

        foreach ($ids as $key => $value)
        {
            if (in_array($value['value'], $excluded_pages))
                continue;

            $page_obj = array();

            $page_obj['slug'] = $value['value'];
            $page_obj['name'] = $value['label'];

            $page = Mage::getModel('cms/page');
            $page->setStoreId($storeId);
            $page->load($page_obj['slug'], 'identifier');

            if (! $page->getId())
                continue;

            $page_obj['objectID'] = $page->getId();

            $page_obj['url'] = Mage::helper('cms/page')->getPageUrl($page->getId());
            $page_obj['content'] = $this->strip($page->getContent());

            $pages[] = $page_obj;
        }

        return $pages;
    }
}