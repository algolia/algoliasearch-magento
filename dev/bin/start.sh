#! /usr/bin/env bash

# start services
service mysql start
service apache2 start

# set configuration variables & volumes
cd /var/www/htdocs
/root/bin/modman repair --force algoliasearch-magento
n98-magerun --root-dir=/var/www/htdocs config:set algoliasearch/credentials/application_id $APPLICATION_ID
n98-magerun --root-dir=/var/www/htdocs config:set algoliasearch/credentials/search_only_api_key $SEARCH_ONLY_API_KEY
n98-magerun --root-dir=/var/www/htdocs config:set algoliasearch/credentials/api_key $API_KEY
n98-magerun --root-dir=/var/www/htdocs config:set algoliasearch/credentials/index_prefix $INDEX_PREFIX
n98-magerun --root-dir=/var/www/htdocs config:set web/unsecure/base_url $BASE_URL
n98-magerun --root-dir=/var/www/htdocs config:set web/secure/base_url $BASE_URL

# GET / to initialize the algolia_search_indexer (other way to do it?)
wget http://localhost/index.php/admin && echo "wget"

# reindex whole index
n98-magerun --root-dir=/var/www/htdocs index:reindex algolia_search_indexer
