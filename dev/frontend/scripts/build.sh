#!/usr/bin/env bash
set -e # exit when error
set -x # debug messages

#!/usr/bin/env bash

yarn add algoliasearch-extensions-bundle@latest --save &&
(cd node_modules/algoliasearch-extensions-bundle && npm run build) &&
cp node_modules/algoliasearch-extensions-bundle/dist/algoliaBundle.min.js* ../../js/algoliasearch/internals/frontend/ &&
cp node_modules/algoliasearch-extensions-bundle/dist/algoliaAdminBundle.min.js* ../../js/algoliasearch/internals/adminhtml
