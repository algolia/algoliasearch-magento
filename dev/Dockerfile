FROM occitech/magento:php5.5-apache

# packages configuration
RUN echo 'mysql-server mysql-server/root_password password P4ssw0rd' | debconf-set-selections
RUN echo 'mysql-server mysql-server/root_password_again password P4ssw0rd' | debconf-set-selections

# packages/dependencies installation
RUN systemctl mask mysql && apt-get update && apt-get install -y \
  mysql-client-5.5 \
  mysql-server \
  php5-mysql \
  php5-xdebug \
  libxml2-dev \
  vim emacs-nox \
  git-core \
  wget \
  zsh && systemctl unmask mysql

RUN docker-php-ext-install soap
RUN docker-php-ext-install mysqli

RUN chsh -s /bin/zsh
RUN sh -x -c "$(curl -fsSL https://raw.github.com/robbyrussell/oh-my-zsh/master/tools/install.sh | grep -v 'set -e')"
COPY bin/.zshrc /root/.zshrc

COPY bin/php.ini /usr/local/etc/php/php.ini
RUN cd /tmp && curl -O https://phpmyadmin-downloads-532693.c.cdn77.org/phpMyAdmin/4.4.9/phpMyAdmin-4.4.9-english.tar.gz && tar xvf phpMyAdmin-4.4.9-english.tar.gz && mv phpMyAdmin-4.4.9-english /var/www/htdocs/phpmyadmin
COPY bin/config.inc.php /var/www/htdocs/phpmyadmin/

# magento 1.9.1
## download & install script
RUN cd /tmp && curl -O https://demos.algolia.com/downloads/magento-1.9.2.1.tar.gz && tar xvf magento-1.9.2.1.tar.gz && mv magento/* magento/.htaccess /var/www/htdocs
COPY ./bin/install-magento /usr/local/bin/install-magento
RUN chmod +x /usr/local/bin/install-magento
## sample dataset import
RUN cd /tmp && curl -O https://demos.algolia.com/downloads/magento-sample-data-1.9.0.0.tar.gz && tar xvf magento-sample-data-1.9.0.0.tar.gz
RUN cd /var/www/htdocs/media && cp -R /tmp/magento-sample-data-1.9.0.0/media/* . && chmod -R 777 /var/www/htdocs/media
RUN cd /var/www/htdocs/skin && cp -R /tmp/magento-sample-data-1.9.0.0/skin/* .
RUN chown -R www-data:www-data /var/www/htdocs
## database setup
RUN service mysql start && (echo "CREATE DATABASE magento" | mysql -u root --password=P4ssw0rd) && mysql -u root --password=P4ssw0rd magento < /tmp/magento-sample-data-1.9.0.0/magento_sample_data_for_1.9.0.0.sql && service mysql stop
RUN service mysql start && MYSQL_HOST=127.0.0.1 MYSQL_USER=root MYSQL_PASSWORD=P4ssw0rd MYSQL_DATABASE=magento MAGENTO_LOCALE=en_GB MAGENTO_TIMEZONE=Europe/Paris MAGENTO_DEFAULT_CURRENCY=USD MAGENTO_URL=http://mymagentostore.com MAGENTO_ADMIN_FIRSTNAME=Admin MAGENTO_ADMIN_LASTNAME=MyStore MAGENTO_ADMIN_EMAIL=amdin@mymagentostore.com MAGENTO_ADMIN_USERNAME=admin MAGENTO_ADMIN_PASSWORD=magentorocks1 /usr/local/bin/install-magento && service mysql stop
## disable cache
RUN service mysql start && n98-magerun --root-dir=/var/www/htdocs cache:flush && n98-magerun --root-dir=/var/www/htdocs cache:disable && service mysql stop
## allow symlinks
RUN service mysql start && n98-magerun --root-dir=/var/www/htdocs config:set dev/template/allow_symlink "1" >/dev/null 2>&1  && service mysql stop
## disable admin popup
RUN service mysql start && n98-magerun --root-dir=/var/www/htdocs admin:notifications >/dev/null 2>&1  && service mysql stop
## disable url key in admin
RUN service mysql start && n98-magerun --root-dir=/var/www/htdocs config:set admin/security/use_form_key "0" >/dev/null 2>&1  && service mysql stop

# algoliasearch-magento setup
RUN cd /tmp && curl -s -L -O https://raw.github.com/colinmollenhour/modman/master/modman-installer && chmod +x modman-installer && ./modman-installer
RUN cd /var/www/htdocs && /root/bin/modman init && /root/bin/modman clone https://github.com/algolia/algoliasearch-magento && rm -rf .modman/algoliasearch-magento

# start script
COPY ./bin/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

#path admin template to have credentials filled && auto login
RUN sed -i 's/name="login\[username\]" value=""/name="login[username]" value="admin"/g' /var/www/htdocs/app/design/adminhtml/default/default/template/login.phtml && \
	sed -i 's/name="login\[password\]" class="required-entry input-text" value=""/name="login[password]" class="required-entry input-text" value="magentorocks1"/g' /var/www/htdocs/app/design/adminhtml/default/default/template/login.phtml && \
	sed -i 's/<\/script>/Event.observe(window, "load", function() {$("loginForm").submit();});<\/script>/g' /var/www/htdocs/app/design/adminhtml/default/default/template/login.phtml && \
	sed -i "s/#ini_set('display_errors', 1);/ini_set('display_errors', 1);error_reporting(E_ALL);Mage::setIsDeveloperMode(true);/g" /var/www/htdocs/index.php

# GO
EXPOSE 80
CMD start.sh