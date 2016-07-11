---
layout: faq
title: FAQ
permalink: /faq/
---

## How many records does the Magento extension create?

The Magento extensions creates several indices.

To be able to have very fast results Algolia precomputes part of the order of the results at indexing time. This means that you cannot have multiple sorts for a single index. To handle multiple sorts, we need to create 1 Algolia index for each sort.

In Magento, this results in creating by default:

- 1 index per store
- 1 index per store per additional sort order (by price, by date, ...)

Which means that for a Magento instance with:

- 2 stores (2 languages for example)
- 100 products
- 2 sorts on "price" (asc, desc)
- 2 sorts on "date" (asc, desc)

You'll have 100 * 2 + 100 * 4 * 2 = 1000 Algolia records.

If you enable the customer group feature it creates:

- 1 index per store
- 1 index per price sort per group per store => This is to be able to have the correct sort on price no matter what the user group
- 1 index per non-price sort per store

Which means that for a Magento instance with:

- 2 stores (2 languages for example)
- 100 products
- 5 customer groups
- 2 sorts on "price" (asc, desc)
- 2 sorts on "date" (asc, desc)

You'll have 100 * 2 + 100 * 2 * 5 * 2 + 100 * 2 * 2 = 2600 Algolia records.

You can reduce the number of records by removing some sorts. This can be configured in the extension administration panel. See the screenshot below:

<figure>
    <img src="../img/sorts.png" class="img-responsive">
    <figcaption>Sorting configuration</figcaption>
</figure>

## Why Magento shows "404 error page not found." in configuration?

Logout and login from your Magento administration panel should fix the issue.

If it doesn't work you can follow this procedure [http://fanplayr.com/resources/magento-404-error-page-not-found-in-configuration/](http://fanplayr.com/resources/magento-404-error-page-not-found-in-configuration/).

## Can I disable Algolia on some store?

Yes you just need to disable indexing for the store where you do not need Algolia. Navigate to **System > Configuration > Algolia Search**, in upper left corner switch to store you want to disable from indexing. Then you can just disable indexing in the configuration. See the screenshot:

<figure>
    <img src="../img/disable-indexing.png" class="img-responsive">
    <figcaption>Enable indexing setting</figcaption>
</figure>

## I hit "Reindex" button, but there are still no products in Algolia indices

In case you have indexing queue enabled, the reindex button will "only" insert indexing jobs to queue database table and not really send them to Algolia. Please make sure you have set queue proccessing correctly and you have set reasonable number of products to be processed in one job. If you set the number od processed products too high the processing script may run out of memory and no products will be indexed.
More information about indexing queue can be found in [documentation](/magento/documentation/#indexing-queue).

## I cannot find my products by SKU

Please, make sure you are using the newest version of the extension. And make sure you set SKU as searchable attribute to index in Algolia's extension configuration in Magento backend.

## I have deleted some products, why are they still appearing in Algolia indices even after full reindex?

Please, make sure you are using the latest version of the extension. It happens when you update / delete your products directly in database and do not trigger standart Magento hooks. The full reindex then had problem with recognizing deleted products and removing them from Algolia.
This issue was resolved in version 1.6.0. Instruction how to upgrade can be found in [documentation](/magento/documentation/#upgrade).

## Why are images not showing up?

Since Algolia is displaying results without going through the backend of magento, at indexing time we need to generate a link for the url. What magento give you when you are asking for this url is the url of the cached and resized image that you need to display.

On some occasions, users of our extension have encountered an issue where the cache for the images would not get automatically generated.

First thing you need to check is that you have a recent enough version of the extension. If you are using a version lower than 1.5.x, the first thing you need to do is update to the latest version.

There is two main issues that you can have with images are the following:

**If images are there and then go away:**

- **Why:**
It usually means that the image cache has been dropped, this usually something triggered manually in System > Cache management or via cli. Clearing the image cache will cause indexed link to be invalid because it doesn't exist anymore. When triggering a full reindex the image cache will be created again.

- **How to fix it:**
Avoid clearing the image cache, and in case you do, launch a full reindex just after.

**If images are not generating from the beginning:**

- **Why:**
In almost all cases it's due to memory issue or directory permissions.

- **How to fix it:**
Enable logging in System > Configuration > Algolia Search > Credentials and setup. After enabling the option, the extension will generate an algolia.log file in the /path/to/magento/var/log/ folder. After a full reindexing if you have some thumbnails issue you should see the issue/error in this file.

<figure>
    <img src="../img/logging.png" class="img-responsive">
    <figcaption>Logging configuration</figcaption>
</figure>

If that still doesn't work, you can also try:

- Checking permissions of the `/media` directory (it should be equal to `770 / 660`)
- Checking magento and apache/nginx logs, to check if there is an error message

## Can I integrate Algolia to my search page template?

The realtime search experience implemented by the extension is done using JavaScript in your end-users browsers and therefore cannot have access to the templates of your original theme (which is rendered with PHP from your backend). Instead, it creates a search page with a default theme that you may need to adapt to fit your UI needs.

But you can still customize the design of the instant search results page & the auto-complete menu. See [Customization section](/magento/documentation/#customization) in documentation.

## How instant search page works?

Instant search page is powered by JavaScript library [instantsearch.js](https://community.algolia.com/instantsearch.js/). This means that all the search is handled in your customer's web browser and nothing is going through Magento itself. The instant search fetches results directly from Algolia's API and renders them into the page. That said, instant search do not fetch the results from Magento engine and nothing is proccessed on your Magento server. This is one of the reasons why the searching in your catalog can be that fast and convenient.

But on the other hand it brings two inconveniences:

- **Templates:**
When the whole page is rendered in your client's web browser it cannot respect your Magento store's custom templates. Templates for instant search page must be customized in the extension's template file. For more information about customizing see [Customization section](/magento/documentation/#customization) in the documentation.
- **SEO:**
The extenstion supports only backend search for regular search page and these results can be indexed by search engines like Google, Bing, etc... But because of the frontend implementantion instant search page results on category page cannot be indexed. But there is a workaround. Search parameters of the instant search page are pushed into page's URL. So it is possible to implement backend search base on the URL parameters so the instant search pages can be indexed. But the extension inself do not support this feature out of the box for now.

## I'm using Magento 2. Is the extension compatible?

No, the extension is not compatible, but we have Magento 2 extension currently in beta. You can find it here: [https://github.com/algolia/algoliasearch-magento-2](https://github.com/algolia/algoliasearch-magento-2).
Remember, the Magento 2 extension is still in beta and it's not recommended to use it in production unless you really know what you are doing.

Any feedback on Magento 2 extension is very appreciated. You can submit an issue on GitHub repository or send us any email on [support+magento@algolia.com](mailto:support+magento@algolia.com).