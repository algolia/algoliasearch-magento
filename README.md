Algolia Search for Magento
==================

[Algolia Search](http://www.algolia.com) is a hosted full-text, numerical, and faceted search engine capable of delivering realtime results from the first keystroke.

This extension replaces the default search of Magento with a typo-tolerant, fast & relevant search experience backed by Algolia. It's based on [algoliasearch-client-php](https://github.com/algolia/algoliasearch-client-php) and [algoliasearch-client-js](https://github.com/algolia/algoliasearch-client-js).


See features and benefits of [Algolia Search Extension for Magento](https://www.algolia.com/with/magento).

<!--![Latest version](https://img.shields.io/badge/latest-1.2.0-green.svg)
![Magento 1.6.2](https://img.shields.io/badge/magento-1.6.2-blue.svg)
![Magento 1.7.1](https://img.shields.io/badge/magento-1.7.1-blue.svg)
![Magento 1.8.1](https://img.shields.io/badge/magento-1.8.1-blue.svg)
![Magento 1.9](https://img.shields.io/badge/magento-1.9-blue.svg)
![PHP >= 5.3](https://img.shields.io/badge/php-%3E=5.3-green.svg)-->

Installation
--------------

### Magento-Connect

To setup this module using the packaged Community Extension, download the last version from [the magento-connect store](http://www.magentocommerce.com/magento-connect/algolia-search-extension.html).

##### 404 issue when accessing to System > Configuration > Algolia Search

You can follow this [http://www.fanplayr.com/1415/magento-404-error-page-not-found-in-configuration/](http://www.fanplayr.com/1415/magento-404-error-page-not-found-in-configuration/)

Features
--------

### Search bar with auto-complete

This extension adds an auto-completion menu to your search bar displaying products and categories "as-you-type".


### Instant search

This extension adds a default implementation of instant search that you can customize easily

### Typo-tolerant full-text search (Fallback and SEO)

If you choose not to use the instant search. The extension will replace the fulltext indexer providing you a typo-tolerant & relevant search experience.

If you choose to use the instant search, when you search for something fulltext indexer replacement is still used so that you can have a backend implementation of the search in order to keep a good SEO

Indexing
---------------

### Initial import

Once configured, do not forget to trigger the re-indexing in **System > Index Management > Algolia Search** :

### Indexing flow

#### Cron enabled (recommanded)

Every indexing job (global reindexing, update of products, deletion of products, update of categories, ...) will be put on to the queue (algoliasearch_queue table).

To process this jobs you will need to add a cron job like this :

```
0,5,10,15,20,25,30,35,40,45,50,55 * * * * /bin/sh /absolute/path/to/magento/cron.sh
```

#### Cron disabled (for small instance only)

Every indexing job (global reindexing, update of products, deletion of products, update of categories, ...) will happen synchronously. 

### Catalog search Index

Reindexing **Catalog Search Index**, it will index only for products

### Algolia search Index

Reindexing **Algolia Search**, it will index only for products, pages and categories


Configuration
--------------

To setup this module, you'll need an Algolia account. Just sign up [here](http://www.algolia.com/users/sign_up) to create an account and retrieve your credentials.

Once the extension is installed, a new **Algolia Search** menu will appear in your **System > Configuration** menu.


### Products Configuration

This section of the configuration allows you to configure how your **Product** records will look like in Algolia. List here all the attributes you need to search, rank and display your products. The order of this setting matters as those at the top of the list are considered more important.

Use the **Ranking** configuration to specify the numerical attribute used to reflect the popularity of your products. By default, we recommend using the number of ordered quantity as popularity criteria.

### Categories Configuration (auto-completion menu only)

This section of the configuration allows you to configure how your **Category** records will look like in Algolia. List here all the attributes you need to search, rank and display your categories. The order of this setting matters as those at the top of the list are considered more important.

Use the **Ranking** configuration to specify the numerical attribute used to reflect the popularity of your categories. By default, we recommend using the number of products in the category as popularity criteria.


Customize
------------

To customize the autocomplete and/or the instant search.

##### Html and Js

You need go to ```/design/frontend/base/default/template/algoliasearch/topsearch.phtml```.

#### Css

You need go to ```/skin/frontend/base/default/algoliasearch/algoliasearch.css```

Development
------------

### 1. Docker (recommanded)

The easiest way to setup your development environment is to use [Docker](https://www.docker.com/).

#### Setup the docker instance

 Just run the following script to setup a runnin Magento 1.9 instance with some sample data & the Algolia Search extension installed:

```sh
$ ./dev/restart.sh -a YourApplicationID \
               -k YourAdminAPIKey \
               -s YourSearchOnlyAPIKey \
               -p YourIndexPrefix \
               -b http://`boot2docker ip`/
```

#### Administration panel

Administration login is `admin` with password `magentorocks1` http://[boot2docker ip]/admin.

#### Phpmyadmin

You have a phpmyadmin installed that you can access at http://[boot2docker ip]/phpmyadmin

#### Access the container via command line

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