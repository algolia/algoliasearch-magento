#!/usr/bin/env bash

service mysql start
cd /var/www/htdocs
/root/bin/modman repair --force algoliasearch-magento
n98-magerun --root-dir=/var/www/htdocs config:set algoliasearch/credentials/application_id $APPLICATION_ID
n98-magerun --root-dir=/var/www/htdocs config:set algoliasearch/credentials/search_only_api_key $SEARCH_ONLY_API_KEY
n98-magerun --root-dir=/var/www/htdocs config:set algoliasearch/credentials/api_key $API_KEY
n98-magerun --root-dir=/var/www/htdocs config:set algoliasearch/credentials/index_prefix $INDEX_PREFIX

service apache2 start
wget http://localhost/index.php/admin && echo "wget"
service apache2 stop

n98-magerun --root-dir=/var/www/htdocs index:reindex algolia_search_indexer

service apache2 start