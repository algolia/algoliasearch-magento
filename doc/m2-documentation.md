---
layout: documentation
title: Magento 2 Documentation
permalink: /m2-documentation/
---

<small>
The extension officially supports only 2.0.X versions of Magento. 
It's possible to use it for versions >= 2.1.0, but some unexpected issues might appear. When you experience that, please [open an issue](https://github.com/algolia/algoliasearch-magento-2/issues/new).
</small>

# Getting started

## Create an Algolia account

1. Create your **[Algolia](https://www.algolia.com/?utm_medium=social-owned&amp;utm_source=magento%20website&amp;utm_campaign=docs)** accout. The [sign-up wizard](https://www.algolia.com/users/sign_up?utm_medium=social-owned&amp;utm_source=magento%20website&amp;utm_campaign=docs) will guide you through Algolia's onboarding process. Pay extra attention to choosing your Algolia datacenter. Select the one which is the closest to your datacenter.
2. Once you are logged into dashboard, get your Algolia credentials from the "Credentials" left-menu.

<figure>
    <img src="../img/signup.png" class="img-responsive">
    <figcaption>Algolia's sign up form</figcaption>
</figure>

## Install the extension

1. Install the extension from the [Magento Marketplace]([link_missing]) or via [Composer](https://getcomposer.org):
```
$ composer require algolia/algoliasearch-magento-2
```
2. In your Magento administration navigate to **Stores > Configuration > Algolia Search** administration panel.
3. In **Credentials & Setup** tab configure your Algolia credentials.

<figure>
    <img src="../img/m2-configuration.png" class="img-responsive">
    <figcaption>Extension's basic information configurations</figcaption>
</figure>

## Initial indexing

Force the re-indexing of all sections you want to synchronize with Algolia. In your console run command: 

```sh
$ bin/magento indexer:reindex algolia_products algolia_categories algolia_pages algolia_suggestions algolia_additional_sections
```

This command will trigger reindexing on all your content.

**Congratulations!** You just installed Algolia extension to your Magento 2 store!

# Indexing

In extension we try to keep your Magento store and Algolia indices synchronized. We have two types of indexing mechanism:

- **Section re-index**
This re-indexes whole part of your catalog _(Products, Categories etc...)_
- **Single item re-index**
Each time your catalog changes _(e.g. addition / deletion / update of products / categories etc...)_, we push the change into Algolia indices

By default all this operations happen synchronously and administrator has to wait before continue his/her work. As it is not very convenient we came up with **Indexing queue**.

## Indexing Queue
To enable indexing queue navigate to **Stores > Configuration > Algolia Search > Indexing Queue / Cron** in your Magento administration.
Once you have enabled queue, all operations mentioned above will be queued in database table called `algoliasearch_queue`.

By enabling Indexing queue you can set how many jobs will be processed each time the queue is processed. By default the number is 10. But you can adjust it to fit to your catalog and server your Magento store runs on.

Now you need to setup running the queue. There are to options how to do that:

### With cron

To asynchronously process queued jobs, you can configure the following cron:

```sh
*/5 * * * * absolute/path/to/magento/bin/magento indexer:reindex algolia_queue_runner
```

This will run `N` jobs every 5 minutes depending of your queue configuration.


### Without cron

If you want to process the queue manually using the command line you can run:

```sh
$ bin/magento indexer:reindex algolia_queue_runner
```

If you want to process the queue entirely in one time you can run:

```sh
$ EMPTY_QUEUE=1 bin/magento indexer:reindex algolia_queue_runner
```

<div class="alert alert-warning">
    <i class="fa fa-exclamation-triangle"></i>
    Enabling the indexing queue is recommended for production environments.
</div>

<div class="alert alert-danger">
  <i class="fa fa-exclamation-triangle"></i>
  As mentioned before, when indexing queue is disabled every indexing job _(whole re-indexing, update/deletion/update of products or categories, etc...)_ will happen synchronously.
  Trying to synchronously index too many objects might trigger PHP timeouts.
</div>

## Full products' reindex

### With enabled indexing queue

With enabled indexing queue products are reindexed with usage of temporary indices. That means that all products are pushed into temporary Algolia indices. When all products are pushed, the production indices are replaced by temporary ones. This approach has these advantages:

1. Higher re-indexing speed when only indexable products are processed and pushed to Algolia
2. Higher reliability regarding removing deleted products
3. Lower number of operations needed for full re-index

All changes done by re-indexing will be visible in search results when the whole process of re-indexing is done and production indices are replaced by temporary ones.

### With disabled indexing queue

When the indexing queue is disabled, full product re-index has to process whole catalog. It has to push updates to Algolia as well as remove inactive products from there.
That being said it takes more time and resources. It is also a little bit less reliable as some deleted products may not be processed and removed from Algolia's indices.

<div class="alert alert-warning">
    <i class="fa fa-exclamation-triangle"></i>
    Doing full reindex on large catalog is strongly recommended with <strong>indexing queue enabled</strong>.
</div>

## Indexable attributes

You can specify which attributes you want to index in your Algolia indices. This option is available only for Products and Categories. For indexable attributes configuration navigate to **Stores > Configuration > Algolia Search > Products / Categories** tab.
There you can find table where you can set the attributes you want to send to Algolia. On each attribute you are able to specify if the attribute is Searchable, Retrievable and Order setting of the attribute. For more information about these settings please read [the official Algolia documentation](https://www.algolia.com/doc/?utm_medium=social-owned&amp;utm_source=magento%20website&amp;utm_campaign=docs).

<figure>
    <img src="../img/m2-attributes.png" class="img-responsive">
    <figcaption>Configuration of attributes to index</figcaption>
</figure>

## Suggestions

Each time the search is processed in backend of Magento, the query, number of results, number of searches of the query are stored/updated in Magento database. Exactly in <code>search_query</code>. This is done automaticly by Magento itself and out extension has nothing to do with it.
Be careful - only backend searches are stored in database. Autocomplete or instant search queries are not inserted into the database.

When you enable the indexing of suggestions, the extension fetches queries from that table, filter the results according your settings (minimal nuber of results, minimal popularity, ...) and filtered queries pushes into Algolia suggestion index.

To have correct data in your Algolia suggestion index, you need to have correct data in your <code>search_query</code> table. To achieve that you need to have enabled backend search by Algolia. That you can have done by enabling <code>Search</code> and <code>Make SEO request</code> in configuration of Algolia extension in Magento administration.
When you have this options enabled, backend search will be processed by Algolia and data in <code>search_query</code> will be updated over time.

Suggestions are not indexed automatically by the extension. You need to trigger reindex manually or you can put it into a cron tab to be processed automatically. For example every hour:

```sh
1 * * * * absolute/path/to/magento/bin/magento indexer:reindex algolia_suggestions
```


# UI/UX

## Custom theme

By default the extension tries to override <code>topSearch</code> block of the theme template. In case your custom theme doesn't contain <code>topSearch</code> block, you need to navigate to **Stores > Configuration > Algolia Search > Advanced** and change DOM selector of your search input.
When you do that, the extension won't try to override <code>topSearch</code> block and will only include it's scripts. In this case you will have to update your styles and put your desired look and feel to your auto-completion menu.

## Auto-completion menu

The extension uses [autocomplete.js](https://github.com/algolia/autocomplete.js) library to display the as-you-type auto-completion menu. By default the menu suggests:

- products
- categories
- pages

You can configure displayed data in administration section **Stores > Configuration > Algolia Search > Autocomplete**.
There you can configure which sections and how many items should be displayed in auto-complete menu.

If you need to do more customization, perhaps for auto-complete layout, you will need to update the underlying template. For more information please navigate to [Customization](#customization) section.

<figure>
    <img src="../img/m2-autocomplete-admin.png" class="img-responsive">
    <figcaption>Extension's autocomplete feature configuration</figcaption>
</figure>

## Instant search results page

The extension uses [instantsearch.js](https://github.com/algolia/instantsearch.js) library to display the as-you-type search results page. By default following widgets are displayed:

- **hits** - displays products matching to customer query and filters
- **pagination** - navigation between products' pages
- **sorting** - switch between different products' sortings
- **price range slider** - used to refine range of prices
- **hierarchial menu** - allows to refine results by categories

You can configure displayed data and set another refinements. Just navigate to **Stores > Configuration > Algolia Search > Instant Search Results Page**. You can configure which attributes you want to use as facets. Facets are used for filtering products. For more information about faceting please read [the official Algolia documentation](https://www.algolia.com/doc/?utm_medium=social-owned&amp;utm_source=magento%20website&amp;utm_campaign=docs).

In the same way you can configure attributes for sorting your products. Be careful because each sorting creates Algolia index. For more information read [the official Algolia documentation](https://www.algolia.com/doc/?utm_medium=social-owned&amp;utm_source=magento%20website&amp;utm_campaign=docs).

If you need to add another widgets or update the existing ones you will need to update the underlying template. For more information please navigate to [Customization](#customization) section.

<div class="alert alert-warning">
    <i class="fa fa-exclamation-triangle"></i>
    By default instant search page is disabled, because it can break your existing layout. You can enable it in **Stores > Configuration > Algolia Search > Credentials & Setup**.
</div>

<figure>
    <img src="../img/m2-instantsearch-admin.png" class="img-responsive">
    <figcaption>Extension's instant search feature configuration</figcaption>
</figure>

## Customization

If you want to customize the look and feel of the auto-completion menu and/or the instant search results you need to have access to your server and you need to be a little bit developer. Or have one next to you :)

All visual aspects are defined in single CSS file - [`algoliasearch.css`](https://github.com/algolia/algoliasearch-magento-2/blob/master/view/frontend/web/algoliasearch.css). This file contains styles for both auto-complete menu and instant search page.

### Auto-completion menu customization

There is one essential file - [`autocomplete.phtml`](https://github.com/algolia/algoliasearch-magento-2/blob/master/view/frontend/templates/autocomplete.phtml).
In this file you can find all HTML templates and JavaScript code used for rendering the menu.

### Instant Search Page customization

All code for rendering instant search page can be found in [`instantsearch.phtml`](https://github.com/algolia/algoliasearch-magento-2/blob/master/view/frontend/templates/instantsearch.phtml).
If you want to use a widget that is not exposed in the administration panel for a particular faceted attribute you can configure it using the `customAttributeFacet` variable of the `instantsearch.phtml` file. For example if you want to have a toggle widget for the `in_stock` attribute, your `customAttributeFacet` variable should look like:

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

More information about customizing widgets you can find in [instantsearch.js documentation](https://community.algolia.com/instantsearch.js/documentation/).

# Upgrade

It's strongly recommended to use [Composer](https://getcomposer.org) or [Magento Connect](https://www.magentocommerce.com/magento-connect/search-algolia-search.html) to install the extension. Other installation methods are not supported.

For upgrade to new version, do the following steps:

1. Install the new version of the extension
	- via Magento Connect
	- or by Composer command <code>$ composer update algolia/algoliasearch-magento-2</code>
3. Go to the **Stores > Configuration > Algolia Search** administration panel and save your configuration. **Even if you didn’t change anything.**
4. Force the re-indexing of all indexers
5. Follow any other guidelines specified in the [changelog](https://github.com/algolia/algoliasearch-magento-2/blob/master/CHANGELOG.md)

# Developers

## Custom events

For developers the extension provides custom events to hook custom code on top of Algolia Search extension.

`algolia_index_settings_prepare`
Dispatches before pushing index settings to Algolia.


`algolia_rebuild_store_product_index_collection_load_before`
Dispatches after products collection creation.


`algolia_product_index_before`
Dispatches before fetching product's attributes for indexing.


`algolia_subproducts_index`
Dispatches after sub products are taken into account when fetching product's data for indexing.


`algolia_category_index_before`
Dispatches before fetching category's attributes for indexing.


`algolia_additional_section_item_index_before`
Dispatches after fetching [additional_section]'s attributes for indexing.

## Logging & Debugging

Sometimes may happen that not everything run smoothly. In may be caused by millions of reasons. That is why we impletemented logging into Algolia's Magento extension.
Logging can be enabled in **Stores > Configuration > Algolia Search > Credentials & Setup** tab. When you enable togging, internal informations form the extension will be logged into Algolia log file. The file is located in Magento's log directory. By default it is `var/log` directory.

Logging can produce large amount of data. So it shuld be enabled only while debugging and investigating issues. **It should definitely not be enabled in production!**

List of logged events:

- Full product reindex
- Rebuilding one page of products
- Loading products' collection
- Creating of products' records
- Creating of single product's record
- Start and stop of sending products to Algolia
- Start and stop of removing products from Algolia
- Start and stop of emulation
- Exceptions from images' loading
- Miscellaneous errors and exceptions

## Contributing

See the project and readme on [GitHub](https://github.com/algolia/algoliasearch-magento-2).

## Caveats

###  Magento hooks

The extension is using the default hooks of Magento, if you are doing insertion or deletion of products outside of the Magento code/interface the extension won't see it and your Algolia index will be out of sync. The best way to avoid that is to use Magento's methods. If this is not possible you still have the possibility to call the extension indexing methods manually as soon you as do the update.
