Algolia Search for Magento BETA
==================

#### The magento plugin is currently at an early stage of development.

------------------


[Algolia Search](http://www.algolia.com) is a search API that provides hosted full-text, numerical and faceted search.
Algolia’s Search API makes it easy to deliver a great search experience in your apps & websites providing:

 * REST and JSON-based API
 * search among infinite attributes from a single searchbox
 * instant-search after each keystroke
 * relevance & popularity combination
 * typo-tolerance in any language
 * faceting
 * 99.99% SLA
 * first-class data security


This module let you easily integrate the Algolia Search API to your Magento instance. The module is based on [algoliasearch-client-php](https://github.com/algolia/algoliasearch-client-php) and [algoliasearch-client-js](https://github.com/algolia/algoliasearch-client-js).


Installation
--------------

To setup the Algolia integration you'll need [modman](https://github.com/colinmollenhour/modman): a module manager for Magento. To install the Algolia integration for the first time, run the following commands:

```sh
$ cd /path/to/your/magento/directory
$ modman init
$ modman clone https://github.com/algolia/algoliasearch-magento.git
```

Update
--------

To fetch the last updates from our Github repository, run the following command:

```sh
$ modman update algoliasearch-magento
```
