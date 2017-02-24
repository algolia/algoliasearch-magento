#! /usr/bin/env bash

# start services
service mysql start
service apache2 start

# GET / to initialize the algolia_search_indexer (Open to another cleaner way to do it :) )
if [ $EXPOSED_PORT == 80 ]; then
  wget $BASE_URL
else
  wget 0.0.0.0
fi

# set configuration variables & volumes
cd /var/www/htdocs

n98-magerun --skip-root-check --root-dir=/var/www/htdocs config:set algoliasearch/credentials/application_id $APPLICATION_ID
n98-magerun --skip-root-check --root-dir=/var/www/htdocs config:set algoliasearch/credentials/search_only_api_key $SEARCH_ONLY_API_KEY
n98-magerun --skip-root-check --root-dir=/var/www/htdocs config:set --encrypt algoliasearch/credentials/api_key $API_KEY
n98-magerun --skip-root-check --root-dir=/var/www/htdocs config:set algoliasearch/credentials/index_prefix $INDEX_PREFIX
n98-magerun --skip-root-check --root-dir=/var/www/htdocs config:set algoliasearch/credentials/is_instant_enabled "1"
n98-magerun --skip-root-check --root-dir=/var/www/htdocs config:set web/unsecure/base_url $BASE_URL
n98-magerun --skip-root-check --root-dir=/var/www/htdocs config:set web/secure/base_url $BASE_URL

chmod -R 777 /var/www/htdocs/media
chown -R www-data:www-data /var/www/htdocs/media

if [ $INSTALL_ALGOLIA == Yes ]; then
  /root/bin/modman repair --force algoliasearch-magento
  /root/bin/modman repair --force algoliasearch-magento-extend-module-skeleton

  # reindex whole index
  n98-magerun --skip-root-check --root-dir=/var/www/htdocs index:reindex algolia_search_indexer
  n98-magerun --skip-root-check --root-dir=/var/www/htdocs index:reindex algolia_search_indexer_cat
  n98-magerun --skip-root-check --root-dir=/var/www/htdocs index:reindex algolia_search_indexer_pages
  n98-magerun --skip-root-check --root-dir=/var/www/htdocs index:reindex search_indexer_suggest
else
  /root/bin/modman undeploy algoliasearch-magento
  /root/bin/modman undeploy algoliasearch-magento-extend-module-skeleton
fi

# Again in case root created some folder with root:root
chmod -R 777 /var/www/htdocs/media
chown -R www-data:www-data /var/www/htdocs/media

# do it after indexing so that var/log doesn't get created as root
n98-magerun --skip-root-check --root-dir=/var/www/htdocs config:set dev/log/active 1

if [ $MAKE_RELEASE == Yes ]; then
  php makeRelease.php
fi

service apache2 stop
exec /usr/sbin/apache2ctl -D FOREGROUND