FROM occitech/magento:php5.5-apache

RUN cd /tmp && curl -O http://www.magentocommerce.com/downloads/assets/1.9.1.0/magento-1.9.1.0.tar.gz && tar xvf magento-1.9.1.0.tar.gz && mv magento/* magento/.htaccess /var/www/htdocs

RUN echo 'mysql-server mysql-server/root_password password P4ssw0rd' | debconf-set-selections
RUN echo 'mysql-server mysql-server/root_password_again password P4ssw0rd' | debconf-set-selections

RUN apt-get update
RUN apt-get install -y mysql-client-5.5 mysql-server
RUN apt-get install -y libxml2-dev
RUN docker-php-ext-install soap

COPY ./bin/install-magento /usr/local/bin/install-magento
RUN chmod +x /usr/local/bin/install-magento

RUN cd /tmp && curl -O http://www.magentocommerce.com/downloads/assets/1.9.0.0/magento-sample-data-1.9.0.0.tar.gz && tar xvf magento-sample-data-1.9.0.0.tar.gz

RUN cd /var/www/htdocs/media && cp -R /tmp/magento-sample-data-1.9.0.0/media/* . && chmod -R 777 /var/www/htdocs/media
RUN cd /var/www/htdocs/skin && cp -R /tmp/magento-sample-data-1.9.0.0/skin/* .

RUN chown -R www-data:www-data /var/www/htdocs

RUN service mysql start && (echo "CREATE DATABASE magento" | mysql -u root --password=P4ssw0rd) && mysql -u root --password=P4ssw0rd magento < /tmp/magento-sample-data-1.9.0.0/magento_sample_data_for_1.9.0.0.sql && service mysql stop

RUN service mysql start && MYSQL_HOST=127.0.0.1 MYSQL_USER=root MYSQL_PASSWORD=P4ssw0rd MYSQL_DATABASE=magento MAGENTO_LOCALE=en_GB MAGENTO_TIMEZONE=Europe/Paris MAGENTO_DEFAULT_CURRENCY=USD MAGENTO_URL=http://192.168.59.103 MAGENTO_ADMIN_FIRSTNAME=Admin MAGENTO_ADMIN_LASTNAME=MyStore MAGENTO_ADMIN_EMAIL=amdin@mymagentostore.com MAGENTO_ADMIN_USERNAME=admin MAGENTO_ADMIN_PASSWORD=magentorocks1 /usr/local/bin/install-magento && service mysql stop

RUN apt-get install -y vim emacs-nox git-core
RUN cd /tmp && curl -s -L -O https://raw.github.com/colinmollenhour/modman/master/modman-installer && chmod +x modman-installer && ./modman-installer

RUN cd /var/www/htdocs && /root/bin/modman init && /root/bin/modman clone https://github.com/algolia/algoliasearch-magento && rm -rf .modman/algoliasearch-magento

RUN apt-get install -y libapache2-mod-php5 phpmyadmin

EXPOSE 80
CMD service mysql start && cd /var/www/htdocs && /root/bin/modman repair algoliasearch-magento && /usr/sbin/apache2 -DFOREGROUND
