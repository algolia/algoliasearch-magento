FROM algolia/base-algoliasearch-magento

ARG INSTALL_XDEBUG
RUN if [ $INSTALL_XDEBUG = Yes ]; then pecl install xdebug && docker-php-ext-enable xdebug; fi

# test script
COPY ./bin/test.sh /usr/local/bin/test.sh
RUN chmod +x /usr/local/bin/test.sh

# GO
ENTRYPOINT test.sh
