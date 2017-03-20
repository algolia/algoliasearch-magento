#! /usr/bin/env bash

# Start services
echo -e "\e[93m-- Starting Apache and MySQL services --\e[0m"
service mysql start
service apache2 start

if [ "$TRAVIS" == true ]; then
  echo -e "\n\e[93m-- Setting the correct rights to Magento files --\e[0m"
  chmod -R 777 /var/www/htdocs
  chown -R www-data:www-data /var/www/htdocs
fi

# GET / to initialize Magento - required before test runs
echo -e "\n\e[93m-- Fetching the Magento homepage to initialize Magento --\e[0m"
wget 127.0.0.1

# Apache is not needed for tests
echo -e "\e[93m-- Stopping the Apache server (not needed for the tests) --\e[0m"
service apache2 stop

chmod -R 777 /var/www/htdocs/media
chown -R www-data:www-data /var/www/htdocs/media

# Repair Modman simlinks
# echo -e "\n\e[93m-- Force repairing the Modman symlinks --\e[0m"
# cd /var/www/htdocs
# /root/bin/modman repair --force algoliasearch-magento

# Again in case root created some folder with root:root
chmod -R 777 /var/www/htdocs/media
chown -R www-data:www-data /var/www/htdocs/media

# Run tests
echo -e "\n\e[93m-- Running the tests --\e[0m"
cd /var/www/htdocs/.modman/algoliasearch-magento

if [ $FILTER ]; then
    vendor/bin/phpunit tests --filter "$FILTER"
else
    vendor/bin/phpunit tests
fi