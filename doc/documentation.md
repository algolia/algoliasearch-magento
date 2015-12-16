---
layout: page
title: Documentation
permalink: /documentation/
---

# Installation
{: .headline}

Please follow those steps to install the Algolia Search extension:

 1. Install the extension on your Magento instance.
 1. Create an **[Algolia](https://www.algolia.com)** Account.
 1. Choose the Algolia datacenter which is the closest to your datacenter.
 1. Get your Algolia credentials from the "Credentials" left-menu.
 1. Configure your credentials from the **System > Configuration > Catalog > Algolia Search** administration panel.
 1. Force the re-indexing of all products & categories with the **System > Index Management > Algolia Search Products** index.
 1. Force the re-indexing of all products & categories with the **System > Index Management > Algolia Search Categories** index.
 1. Force the re-indexing of all pages with the **System > Index Management > Algolia Search Pages** index.
 1. Force the re-indexing of all suggestions with the **System > Index Management > Algolia Search Suggestions** index.

2 screenshots

<div class="spacer100"></div>

# Indexing flow

## Queue enabled (recommended for production environments)

### With cron

If enabled, every indexing job (global re-indexing, addition/deletion/update of products) will be queued (algoliasearch_queue table).

To asynchronously process those queued indexing jobs, make sure you've configured the Magento cron:

```
*/5 * * * * php -f /absolute/path/to/magento/shell/indexer.php -- -reindex algolia_queue_runner
```

This will run n jobs every 5 minutes depending of your queue configuration


### Without cron

If you want to process the queue in via command line you can run:

```
php -f /absolute/path/to/magento/shell/indexer.php -- -reindex algolia_queue_runner
```

If you want to process the queue entirely in one time you can run:

```
EMPTY_QUEUE=1 php -f /absolute/path/to/magento/shell/indexer.php -- -reindex algolia_queue_runner
```

## Queue disabled (development & small stores purpose)

Every indexing job (global reindexing, update of products, deletion of products, update of categories, ...) will happen synchronously.
**Be careful this can cause php timeout**.

# Customize
{: .headline}

## Files to modify
If you want to customize the look and feel of the autocomplete and/or the instantsearch, the two important files that you will need to modify are:

- the topsearch.phtml (https://github.com/algolia/algoliasearch-magento/blob/master/design/frontend/template/topsearch.phtml) where there is html templates and javascript code
- the algoliasearch.css (https://github.com/algolia/algoliasearch-magento/blob/master/skin/algoliasearch.css) where there is all the css rules

## autocomplete.js and instantsearch.js

The extension is making use of autocomplete.js (https://github.com/algolia/autocomplete.js) and instantsearch.js (https://github.com/algolia/instantsearch.js).
The extension expose some feature of those libraries, for those which are not expose, you can use them but you will need to modify the topsearch.phtml to use them.
To know more about what is possible, you can refer the those librairies documentations.

## Custom instantsearch.js widget for an faceted attribute

If you want to use a instantsearch.js widget that is not expose in the admin panel for a particular faceted attribute you can configure it in the ```customAttributeFacet``` variable of the topsearch.phtml file.
For example if you want to have a toggle widget for the in_stock attribute, your ```customAttributeFacet``` variable should looks like: 


```js
var customAttributeFacet = {
	in_stock: function (facet, templates) {
		 instantsearch.widgets.toggle({
            container: facet.wrapper.appendChild(document.createElement('div')),
            attributeName: 'in_stock',
            label: 'In Stock',
            values: {
              on: 1,
              off: 0
            },
            templates: templates
          })
	},
	categories: function(facet, templates) {
		[...]
	}
};
```

<div class="spacer100"></div>

# Upgrade
{: .headline}

To upgrade from version **A** to version **B**, do the following steps:

 1. Install the new version of the extension
 1. Go to the **System > Configuration > Catalog > Algolia Search** administration panel and **save** your configuration. (even if you didn't change anything)
 1. Force the re-indexing of all indexers
 1. Follow any other Changelog guidelines
 
# Warning 
{: .headline}

 - The extension is using the default hooks of magento, if you are doing insertion and or deletion of products outside of Magento code/interface the extension won't see it and you will be out of sync. The best way to avoid that is to use Magento methods but if this not something possible you still have the possibility to call the extension indexing methods stay in sync
