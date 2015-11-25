Algolia Search for Magento
==================

[Algolia Search](http://www.algolia.com) is a hosted full-text, numerical, and faceted search engine capable of delivering realtime results from the first keystroke.

This extension replaces the default search of Magento with a typo-tolerant, fast & relevant search experience backed by Algolia. It's based on [algoliasearch-client-php](https://github.com/algolia/algoliasearch-client-php) and [algoliasearch-client-js](https://github.com/algolia/algoliasearch-client-js).

See features and benefits of [Algolia Search Extension for Magento](https://www.algolia.com/with/magento).

![Latest version](https://img.shields.io/badge/latest-1.5.0-green.svg)
![Magento 1.6.2](https://img.shields.io/badge/magento-1.6.2-blue.svg)
![Magento 1.7.1](https://img.shields.io/badge/magento-1.7.1-blue.svg)
![Magento 1.8.1](https://img.shields.io/badge/magento-1.8.1-blue.svg)
![Magento 1.9.2](https://img.shields.io/badge/magento-1.9-blue.svg)
![PHP >= 5.3](https://img.shields.io/badge/php-%3E=5.3-green.svg)

Demo
--------------

You can check out our live demo:

* [Autocomplete page](https://magento.algolia.com)

* [Instant search results page](https://magento.algolia.com/catalogsearch/result/?q=mad&instant=1#q=ma&page=0&refinements=%5B%5D&numerics_refinements=%7B%7D&index_name=%22magento_default_products%22)

![demo](doc/algolia-search-experience.gif)

Installation
--------------

To setup this module, you'll need an Algolia account. Just sign up [here](http://www.algolia.com/users/sign_up) to create an account and retrieve your credentials.

  1. Create an [Algolia Account](https://www.algolia.com/users/sign_up?hi=magento).
  2. Download the packaged Community Extension from [the magento-connect store](http://www.magentocommerce.com/magento-connect/algolia-search-extension.html)
  3. Install it on your Magento instance.
  4. Configure your credentials from the **System** > **Configuration** > **Catalog** > **Algolia Search** administration panel.
  5. Force the re-indexing of all your products, categories with the **System > Index Management > Algolia Search** index.
  6. Force the re-indexing of all your pages with the **System > Index Management > Algolia Search Pages** index.
  7. Force the re-indexing of all your suggestions with the **System > Index Management > Algolia Search Suggestions** index.

**Note:** If you experience a 404 issue while accessing the *Algolia Search* administration panel, can follow this [procedure](http://www.fanplayr.com/1415/magento-404-error-page-not-found-in-configuration/).

Features
--------

#### Search bar with auto-complete

This extension adds an auto-completion menu to your search bar displaying product, categories & pages "as-you-type".

#### Instant & Faceted search results page

This extension adds a default implementation of an instant & faceted search results page. Just customize the underlying CSS & JavaScript to suits your shop theme.

#### Typo-tolerant full-text search

If you choose not to use the instant search. The extension will replace the fulltext indexer providing you a typo-tolerant & relevant search experience.

If you choose to use the instant search, when you search for something fulltext indexer replacement is still used so that you can have a backend implementation of the search in order to keep a good SEO

Indexing
---------------

### Indexers

- **Algolia Search**: Index every products and categories and **is also responsible for updating records** when you update/delete products and categories.

- **Algolia Search Pages**: Index every pages **but do not handle automatic updates**. You need to do it manually from time to time.

- **Algolia Search Suggestions**: Index every suggestions **but do not handle automatic updates**. You need to do it manually from time to time.

### Initial import

Once configured, do not forget to trigger the re-indexing in **System > Index Management > Algolia Search**:

#### Indexing flow

##### Cron enabled (recommended for production environments)

If enabled, every indexing job (global re-indexing, addition/deletion/update of products) will be queued (algoliasearch_queue table).

To asynchronously process those queued indexing jobs, make sure you've configured the Magento cron:

```
*/5 * * * * php -f /absolute/path/to/magento/shell/indexer.php --reindex algolia_queue_runner
```

##### Cron disabled (development & small stores purpose)

Every indexing job (global reindexing, update of products, deletion of products, update of categories, ...) will happen synchronously. **Be careful this can cause php timeout**.

#### Algolia search Index

Reindexing the **Algolia Search** re-index all products, categories & pages.

#### Catalog search Index

Reindexing the **Catalog Search Index** will only re-index the products.


Configuration
--------------

Once the extension is installed, a new **Algolia Search** menu will appear in your **System > Configuration > Catalog** menu.

### Credentials & Setup

This section allows you to configure your Algolia credentials and whether or not you want to enable the auto-completion and/or the instant search results page.

### Instant Search Results Page Configuration

This section allows you to configure what you want in your instant search result page.

You first need to choose a jQuery DOM selector. The content of this selector will be replaced at each keystroke by the most relevant results & facets the Algolia engine will retrieve.

You can choose which attributes to facet on and which attributes to sort on.

You can also choose to replace the default magento category page with a result page with the selected category value checked in the facet bloc. You need to enable the **Enable Instant Search Results Page** option **AND** to have the `categories` attribute configured as a **Facet** in the Products Configuration section.

### Products Configuration

This section allows you to configure how your **Product** records will look like in Algolia. 

List in **Attributes** all the attributes you need to search, rank and display your products. The order of this setting matters as those at the top of the list are considered more important.

Use the **Ranking** configuration to specify the numerical attribute used to reflect the popularity of your products. By default, we recommend using the number of ordered quantity as popularity criteria.

### Categories Configuration (auto-completion menu only)

This section allows you to configure how your **Category** records will look like in Algolia.

List in **Attributes** all the attributes you need to search, rank and display your categories. The order of this setting matters as those at the top of the list are considered more important.

Use the **Ranking** configuration to specify the numerical attribute used to reflect the popularity of your categories. By default, we recommend using the number of products in the category as popularity criteria.

### Pages Configuration

This section allows you to configure how the static pages of your Magento instance are stored in Algolia.

Use the **Excluded Pages** to exclude pages you don't want to index.


Customization
------------

To customize the autocompletion menu and/or the instant search results page, you can update those 2 files:

##### HTML and JavaScript

Edit ```/design/frontend/base/default/template/algoliasearch/topsearch.phtml```.

#### CSS

Edit ```/skin/frontend/base/default/algoliasearch/algoliasearch.css```.

Contribute to the Extension
------------

### 1. Docker (recommended)

The easiest way to setup your development environment is to use [Docker](https://www.docker.com/). If you're a Mac user, use [boot2docker](http://boot2docker.io/) to run docker containers.

#### Setup the docker instance

Just run the following script to setup a running Magento 1.9.1 instance with some sample data & the Algolia Search extension installed:

```sh
$ ./dev/restart.sh -a YourApplicationID \
               -k YourAdminAPIKey \
               -s YourSearchOnlyAPIKey \
               -p YourIndexPrefix \
               -b http://`boot2docker ip`/ # change that if you're not using boot2docker
```

#### Administration panel

Administration login is `admin` with password `magentorocks1` and you can access it from `http://[boot2docker ip]/admin`.

#### Phpmyadmin

A phpmyadmin instance is available from `http://[boot2docker ip]/phpmyadmin`

#### Shell

You can execute a shell inside the container with the following command:

```sh
$ docker exec -i -t algoliasearch-magento /bin/bash
```

### 2. Modman

If you do not want to use docker. You can use [modman](https://github.com/colinmollenhour/modman) (a module manager for Magento) by running the following commands:

```sh
$ cd /path/to/your/magento/directory
$ modman init
$ modman clone https://github.com/algolia/algoliasearch-magento.git
```
