Algolia Search for Magento 1.6+
==================

![Latest version](https://img.shields.io/badge/latest-1.14.0-green.svg)

[![Build Status](https://travis-ci.org/algolia/algoliasearch-magento.svg?branch=master)](https://travis-ci.org/algolia/algoliasearch-magento)
![PHP >= 5.3](https://img.shields.io/badge/php-%3E=5.3-green.svg)

![Magento 1.6.2](https://img.shields.io/badge/magento-1.6.2-blue.svg)
![Magento 1.7.1](https://img.shields.io/badge/magento-1.7.1-blue.svg)
![Magento 1.8.1](https://img.shields.io/badge/magento-1.8.1-blue.svg)
![Magento 1.9.2](https://img.shields.io/badge/magento-1.9.2-blue.svg)
![Magento 1.9.3](https://img.shields.io/badge/magento-1.9.3-blue.svg)

-------

🔎 Are you a Magento engineer? [Join our team](https://www.algolia.com/careers#!?j=eed58660-f684-436d-a2ff-e9947d2b65a2) and help us deliver the best search solution for Magento stores!

-------

- **Auto-completion menu:** Offer End-Users immediate access to your whole catalog from the dropdown menu, whatever your number of categories or attributes.

- **Instant search results page:** Have your search results page, navigation and pagination updated in realtime, after each keystroke.

Official website: [community.algolia.com/magento](https://community.algolia.com/magento).

*Note: if your store is running under Magento version 2, please check our [Algolia for Magento 2 extension](https://github.com/algolia/algoliasearch-magento-2).*

Algolia Search
--------------

[Algolia](http://www.algolia.com) is a search engine API as a service capable of delivering realtime results from the first keystroke.

This extension replaces the default search of Magento with a typo-tolerant, fast & relevant search experience backed by Algolia. It's based on [algoliasearch-client-php](https://github.com/algolia/algoliasearch-client-php), [autocomplete.js](https://github.com/algolia/autocomplete.js) and [instantsearch.js](https://github.com/algolia/instantsearch.js).

You can check out our [live demo](https://magento.algolia.com).

Documentation
--------------

Check out the [Algolia Search for Magento 1 documentation](https://community.algolia.com/magento/doc/m1/getting-started/).

Installation
------------

Follow our [getting started guide](https://community.algolia.com/magento/doc/m1/getting-started/).

Contribute to the Extension
------------

### 1. Docker (recommended)

The easiest way to setup your development environment is to use [Docker](https://www.docker.com/). If you're a Mac user, use [Docker for Mac](https://docs.docker.com/engine/installation/mac/) to run Docker containers.

#### Setup the Docker instance

Just run the following script to setup a running Magento 1.9.2 instance with some sample data & the Algolia Search extension installed:

```sh
$ ./dev/restart.sh -a YourApplicationID \
               -k YourAdminAPIKey \
               -s YourSearchOnlyAPIKey \
               -p YourIndexPrefix \
               -b http://`docker ip`/
```

#### Administration panel

Administration login is `admin` with password `magentorocks1` and you can access it from `http://[docker ip]/admin`.

#### phpMyAdmin

A phpMyAdmin instance is available from `http://[docker ip]/phpmyadmin`

#### Shell

You can execute a shell inside the container with the following command:

```sh
$ docker exec -i -t algoliasearch-magento /bin/bash
```

#### Run the tests

Just run the following script to create a Magento container and launch the tests directly:

```sh
$ ./dev/runTests.sh -a YourApplicationID \
               -k YourAdminAPIKey \
               -s YourSearchOnlyAPIKey \
               -p YourIndexPrefix \
               -b http://`docker ip`/
```

### 2. Modman

If you do not want to use docker. You can use [modman](https://github.com/colinmollenhour/modman) (a module manager for Magento) by running the following commands:

```sh
$ cd /path/to/your/magento/directory
$ modman init
$ modman clone https://github.com/algolia/algoliasearch-magento.git
```
