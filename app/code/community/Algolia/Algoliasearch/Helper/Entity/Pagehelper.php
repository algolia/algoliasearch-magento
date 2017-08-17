<?php

class Algolia_Algoliasearch_Helper_Entity_Pagehelper extends Algolia_Algoliasearch_Helper_Entity_Helper
{
    protected function getIndexNameSuffix()
    {
        return '_pages';
    }

    public function getIndexSettings($storeId)
    {
        $indexSettings = array(
            'searchableAttributes' => array('unordered(slug)', 'unordered(name)', 'unordered(content)'),
            'attributesToSnippet'  => array('content:7'),
        );

        $transport = new Varien_Object($indexSettings);
        Mage::dispatchEvent('algolia_pages_index_before_set_settings', array('store_id' => $storeId, 'index_settings' => $transport));
        $indexSettings = $transport->getData();

        return $indexSettings;
    }

    public function getPages($storeId, $pageIds = null)
    {
        /** @var Mage_Cms_Model_Page $cmsPage */
        $cmsPage = Mage::getModel('cms/page');

        /** @var Mage_Cms_Model_Resource_Page_Collection $pages */
        $pages = $cmsPage->getCollection()->addStoreFilter($storeId)->addFieldToFilter('is_active', 1);

        if ($pageIds && count($pageIds) > 0) {
            $pages = $pages->addFieldToFilter('page_id', array('in' => $pageIds));
        }

        Mage::dispatchEvent('algolia_after_pages_collection_build', array('store' => $storeId, 'collection' => $pages));

        $ids = $pages->toOptionArray();

        $exludedPages = array_values($this->config->getExcludedPages());

        foreach ($exludedPages as &$excludedPage) {
            $excludedPage = $excludedPage['pages'];
        }

        $pages = array();

        foreach ($ids as $key => $value) {
            if (in_array($value['value'], $exludedPages)) {
                continue;
            }

            $pageObject = array();

            $pageObject['slug'] = $value['value'];
            $pageObject['name'] = $value['label'];

            /** @var Mage_Cms_Model_Page $page */
            $page = Mage::getModel('cms/page');

            $page->setStoreId($storeId);
            $page->load($pageObject['slug'], 'identifier');

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

            /** @var Mage_Cms_Helper_Page $cmsPageHelper */
            $cmsPageHelper = Mage::helper('cms/page');

            $pageObject['objectID'] = $page->getId();
            $pageObject['url'] = $cmsPageHelper->getPageUrl($page->getId());
            $pageObject['content'] = $this->strip($content, array('script', 'style'));

            $transport = new Varien_Object($pageObject);
            Mage::dispatchEvent('algolia_after_create_page_object', array('page' => $transport, 'pageObject' => $page));
            $pageObject = $transport->getData();

            $pages[] = $pageObject;
        }

        return $pages;
    }

    public function shouldIndexPages($storeId)
    {
        $autocompleteSections = $this->config->getAutocompleteSections($storeId);

        foreach ($autocompleteSections as $section) {
            if ($section['name'] === 'pages') {
                return true;
            }
        }

        return false;
    }
}
