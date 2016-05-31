<?php

class Algolia_Algoliasearch_Helper_Entity_Pagehelper extends Algolia_Algoliasearch_Helper_Entity_Helper
{
    protected function getIndexNameSuffix()
    {
        return '_pages';
    }

    public function getIndexSettings($storeId)
    {
        return [
            'attributesToIndex'   => ['slug', 'name', 'unordered(content)'],
            'attributesToSnippet' => ['content:7'],
        ];
    }

    public function getPages($storeId)
    {
        /** @var Mage_Cms_Model_Page $cmsPage */
        $cmsPage = Mage::getModel('cms/page');

        /** @var Mage_Cms_Model_Resource_Page_Collection $magento_pages */
        $magento_pages = $cmsPage->getCollection()->addStoreFilter($storeId)->addFieldToFilter('is_active', 1);

        $ids = $magento_pages->toOptionArray();

        $excluded_pages = array_values($this->config->getExcludedPages());

        foreach ($excluded_pages as &$excluded_page) {
            $excluded_page = $excluded_page['pages'];
        }

        $pages = [];

        foreach ($ids as $key => $value) {
            if (in_array($value['value'], $excluded_pages)) {
                continue;
            }

            $page_obj = [];

            $page_obj['slug'] = $value['value'];
            $page_obj['name'] = $value['label'];

            /** @var Mage_Cms_Model_Page $page */
            $page = Mage::getModel('cms/page');

            $page->setStoreId($storeId);
            $page->load($page_obj['slug'], 'identifier');

            if (!$page->getId()) {
                continue;
            }

            $content = $page->getContent();
            if ($this->config->getRenderTemplateDirectives()) {
                /** @var Mage_Cms_Helper_Data $cms_helper */
                $cms_helper = Mage::helper('cms');
                $tmplProc = $cms_helper->getPageTemplateProcessor();
                $content = $tmplProc->filter($content);
            }

            /** @var Mage_Cms_Helper_Page $cms_helper_page */
            $cms_helper_page = Mage::helper('cms/page');

            $page_obj['objectID'] = $page->getId();
            $page_obj['url'] = $cms_helper_page->getPageUrl($page->getId());
            $page_obj['content'] = $this->strip($content);

            $pages[] = $page_obj;
        }

        return $pages;
    }
}
