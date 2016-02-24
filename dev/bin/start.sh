#! /usr/bin/env bash

# start services
service mysql start
service apache2 start

# GET / to initialize the algolia_search_indexer (Open to another cleaner way to do it :) )
wget $BASE_URL

# set configuration variables & volumes
cd /var/www/htdocs
/root/bin/modman repair --force algoliasearch-magento
n98-magerun --root-dir=/var/www/htdocs config:set algoliasearch/credentials/application_id $APPLICATION_ID
n98-magerun --root-dir=/var/www/htdocs config:set algoliasearch/credentials/search_only_api_key $SEARCH_ONLY_API_KEY
n98-magerun --root-dir=/var/www/htdocs config:set algoliasearch/credentials/api_key $API_KEY
n98-magerun --root-dir=/var/www/htdocs config:set algoliasearch/credentials/index_prefix $INDEX_PREFIX
n98-magerun --root-dir=/var/www/htdocs config:set algoliasearch/credentials/is_instant_enabled "1"
n98-magerun --root-dir=/var/www/htdocs config:set web/unsecure/base_url $BASE_URL
n98-magerun --root-dir=/var/www/htdocs config:set web/secure/base_url $BASE_URL

chmod -R 777 /var/www/htdocs/media
chown -R www-data:www-data /var/www/htdocs/media

# reindex whole index
n98-magerun --root-dir=/var/www/htdocs index:reindex algolia_search_indexer
n98-magerun --root-dir=/var/www/htdocs index:reindex algolia_search_indexer_cat
n98-magerun --root-dir=/var/www/htdocs index:reindex algolia_search_indexer_pages
n98-magerun --root-dir=/var/www/htdocs index:reindex search_indexer_suggest

# Again in case root created some folder with root:root
chmod -R 777 /var/www/htdocs/media
chown -R www-data:www-data /var/www/htdocs/media

# do it after indexing so that var/log doesn't get created as root
n98-magerun --root-dir=/var/www/htdocs config:set dev/log/active 1

service apache2 stop
exec /usr/sbin/apache2ctl -D FOREGROUND