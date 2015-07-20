#!/usr/bin/env bash
set -e # exit when error
set -x # debug messages

npm install algoliasearch-extensions-bundle@latest --save
cp node_modules/algoliasearch-extensions-bundle/bundle.min.js ../../js/bundle.min.js
cp node_modules/algoliasearch-extensions-bundle/bundle.css ../../skin/bundle.css
