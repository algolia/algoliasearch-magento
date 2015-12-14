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

Indexing
---------------

### Indexers

- **Algolia Search**: Index every products and **is also responsible for updating records** when you update/delete products.

- **Algolia Search Categories**: Index every categories and **is also responsible for updating records** when you update/delete categories.

- **Algolia Search Pages**: Index every pages **but do not handle automatic updates**. You need to do it manually from time to time.

- **Algolia Search Suggestions**: Index every suggestions **but do not handle automatic updates**. You need to do it manually from time to time.

- **Algolia Search Additionnal sections**: Index every additionnal section (Colors, Manufacturer, ...) **but do not handle automatic updates**. You need to do it manually from time to time.

- **Algolia Search Queue Runner**: Process the queue. You can use it from the admin menu but the best way it to schedule the cron.

#### Indexing flow

##### Cron enabled (recommended for production environments)

If enabled, every indexing job (global re-indexing, addition/deletion/update of products) will be queued (algoliasearch_queue table).

To asynchronously process those queued indexing jobs, make sure you've configured the Magento cron:

```
*/5 * * * * php -f /absolute/path/to/magento/shell/indexer.php --reindex algolia_queue_runner
```

This will run n jobs every 5 minutes depending or your queue configuration


If you want to empty the queue in one time you can run:

```
EMPTY_QUEUE=1 php -f /absolute/path/to/magento/shell/indexer.php --reindex algolia_queue_runner
```

##### Cron disabled (development & small stores purpose)

Every indexing job (global reindexing, update of products, deletion of products, update of categories, ...) will happen synchronously. **Be careful this can cause php timeout**.

#### Full reindex

##### Products

After the first full reindex you should not have to do a full reindex again because the updates will be done incrementally.

You will need a full reindex when:

- You apply a price rule at the catalog level, otherwise prices will not be up to date
- You apply a settings that have an impact on what need to be indexed (stocks rules)
- You flush the image cache

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
