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

## Cron enabled (recommended for production environments)

If enabled, every indexing job (global re-indexing, addition/deletion/update of products) will be queued (algoliasearch_queue table).

To asynchronously process those queued indexing jobs, make sure you've configured the Magento cron:

```
*/5 * * * * php -f /absolute/path/to/magento/shell/indexer.php -- -reindex algolia_queue_runner
```

This will run n jobs every 5 minutes depending of your queue configuration

If you want to proce

If you want to empty the queue in one time you can run:

```
EMPTY_QUEUE=1 php -f /absolute/path/to/magento/shell/indexer.php -- -reindex algolia_queue_runner
```

## Cron disabled (development & small stores purpose)

Every indexing job (global reindexing, update of products, deletion of products, update of categories, ...) will happen synchronously. **Be careful this can cause php timeout**.

# Customize
{: .headline}

ccs/
phtml js+html
autocomplete.js
instantsearch.js

Document how to use other widget for a specific attribute

lien vers les deux fichiers

<div class="spacer100"></div>

# Upgrade
{: .headline}

To upgrade from version **A** to version **B**, do the following steps:

 1. Install the new version of the extension
 1. Go to the **System > Configuration > Catalog > Algolia Search** administration panel and **save** your configuration. (even if you didn't change anything)
 1. Force the re-indexing of all indexers
 1. Follow any other Changelog guidelines
 
 
# Warning 

 - The extension is using the default hooks of magento, if you are doing insertion and or deletion of products outside of Magento code/interface the extension won't see it and you will be out of sync. The best way to avoid that is to use Magento methods but if this not something possible you still have the possibility to call the extension indexing methods stay in sync
 