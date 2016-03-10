#!/usr/bin/env bash
set -e # exit when error
set -x # debug messages

npm install algoliasearch-extensions-bundle@latest --save
cp node_modules/algoliasearch-extensions-bundle/dist/algoliaBundle.min.js* ../../js/
cp node_modules/algoliasearch-extensions-bundle/dist/algoliaAdminBundle.min.js* ../../js/
