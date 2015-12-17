---
layout: documentation
title: Documentation
permalink: /documentation/
---

# Installation
{: .headline}

Please follow those steps to install the Algolia Search extension:

## Create an Algolia account

 * Create an **[Algolia](https://www.algolia.com)** Account.
 * Choose the Algolia datacenter which is the closest to your datacenter.
 * Get your Algolia credentials from the "Credentials" left-menu.

<img src="../img/signup.png" class="img-responsive" />

## Install the extension

 * Download and install the extension from the [Magento Commerce](http://www.magentocommerce.com/magento-connect/search-algolia-instant-search.html) or [GitHub](https://github.com/algolia/algoliasearch-magento).
 * Configure your credentials from the **System > Configuration > Catalog > Algolia Search** administration panel.

<img src="../img/configuration.png" class="img-responsive" />

## Initial indexing

 * Force the re-indexing of all products & categories with the **System > Index Management > Algolia Search Products** index.
 * Force the re-indexing of all products & categories with the **System > Index Management > Algolia Search Categories** index.
 * Force the re-indexing of all pages with the **System > Index Management > Algolia Search Pages** index.
 * Force the re-indexing of all suggestions with the **System > Index Management > Algolia Search Suggestions** index.

<img src="../img/indexers.png" class="img-responsive" />

# Indexing flow
{: .headline}

## Indexing Queue Enabled

If enabled, every indexing job (whole re-indexing, addition/deletion/update of products) will be queued (`algoliasearch_queue` table). 

<div class="alert alert-warning">
  <i class="fa fa-exclamation-triangle"></i>
  Enabling the indexing queue is recommended for production environments.
</div>

### With cron

To asynchronously process those queued indexing jobs, configure the following cron:

```sh
*/5 * * * * php -f /absolute/path/to/magento/shell/indexer.php -- -reindex algolia_queue_runner
```

This will run `N` jobs every 5 minutes depending of your queue configuration.


### Without cron

If you want to process the queue manually using the command line you can run:

```sh
php -f /absolute/path/to/magento/shell/indexer.php -- -reindex algolia_queue_runner
```

If you want to process the queue entirely in one time you can run:

```sh
EMPTY_QUEUE=1 php -f /absolute/path/to/magento/shell/indexer.php -- -reindex algolia_queue_runner
```

## Indexing Queue Disabled

Every indexing job (whole re-indexing, update/deletion/update of products or categories, ...) will happen synchronously.

<div class="alert alert-danger">
  <i class="fa fa-exclamation-triangle"></i>
  Trying to synchronously index too many objects might trigger PHP timeouts.
</div>



# Customization
{: .headline}

## Auto-completion menu

The extension is making use of [autocomplete.js](https://github.com/algolia/autocomplete.js) to display the as-you-type auto-completion menu. By default it suggests:

 * products,
 * categories,
 * pages,
 * and popular queries.

You can add new sections through the administration panel. If you want to do more customization, you'll need to update the underlying JavaScript code.

## Search results page

The extension is making use of [instantsearch.js](https://github.com/algolia/instantsearch.js) to display the as-you-type search results page. By default it uses the following widgets:

 * a hits widget,
 * a pagination widget,
 * a sort widget,
 * and a few refinements widgets (hierarchical menu, refinement list & range slider).

You can add custom widgets or update those ones by updating the underlying JavaScript code.

## Advanced

### Introduction

If you want to customize the look and feel of the auto-completion menu and/or the instant search results page, the two important files you will need to modify are:

- the [`topsearch.phtml`](https://github.com/algolia/algoliasearch-magento/blob/master/design/frontend/template/topsearch.phtml) including the HTML templates and JavaScript code
- the [`algoliasearch.css`](https://github.com/algolia/algoliasearch-magento/blob/master/skin/algoliasearch.css) defining all the look & feel


### Custom widgets

If you want to use a widget that is not expose in the administration panel for a particular faceted attribute you can configure it using the `customAttributeFacet` variable of the `topsearch.phtml` file. For example if you want to have a toggle widget for the `in_stock` attribute, your `customAttributeFacet` variable should look like:

{% highlight js %}
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
{% endhighlight %}

# Upgrade
{: .headline}

To upgrade from version `X.Y` to version `X.Z`, do the following steps:

 1. Install the new version of the extension
 1. Go to the **System > Configuration > Catalog > Algolia Search** administration panel and **save** your configuration. (even if you didn't change anything)
 1. Force the re-indexing of all indexers
 1. Follow any other guidelines specified in the [ChangeLog](https://github.com/algolia/algoliasearch-magento/blob/master/CHANGELOG.md)

# Caveats
{: .headline}

### Magento hooks

 The extension is using the default hooks of Magento, if you are doing insertion or deletion of products outside of the Magento code/interface the extension won't see it and your Algolia index will be out of sync. The best way to avoid that is to use Magento's methods. If this is not possible you still have the possibility to call the extension indexing methods manually as soon you as do the update.
