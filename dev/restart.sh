#!/bin/bash

docker build -t magento .

docker stop magento_container || true
docker rm magento_container || true

docker run -p 80:80 -v `pwd`/..:/var/www/htdocs/.modman/algoliasearch-magento \
-e APPLICATION_ID=F5EK7150FU \
-e SEARCH_ONLY_API_KEY=0346ace6394a8c1ae1f04e8cf3d91451 \
-e API_KEY=5aab844f31a312387b531ff76b10f634 \
-e INDEX_PREFIX=magento2_ \
--name magento_container -d -t magento