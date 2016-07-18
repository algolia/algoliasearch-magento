## Change Log

### 1.6.1

#### UPDATES
- JavaScript templates were split to separate files (#483)
- Locale CSV file was moved from `en_GB` to `en_US` directory (#481)
- Template loader can load more then only two blocks (#502)

#### FIXES
- Fixed bug when `top.search` block was not overriden in some themes (#500)
- Fixed bug with reindexing with empty synonyms lines (#480)
- Localized strings are properly escaped in JavaScript now (#488)
- Fixed occasional warnings in `getProductsRecords` method (#503)
- Fixed duplicite CSS class names (#484)
	- Potential **BC break** - please review your design and CSS so it follows new classes


### 1.6.0

#### NEW FEATURES
- Support of synonyms API
- Facets can be chosen from all attributes now
- Added option to remove products from Algolia index when performing full re-index. By default the feature is disabled.
- Added warning if you use old version of the extension
- *Index settings* are set to products' indices on full products re-index
- Code now follows PSR2 standarts, annotations were added to most unresolveable variables
- Added option to index products when they are added/removed to/from category via "Manage category". By default the feature is enabled.
  - **BC break** - it may cause difficulties on large stores. If it does so, please disable the feature in Configuration.
- Added locale CSV file for easier translations


#### UPDATES
- Frontend templates completely refactored
  - topsearch.phtml file was divided into 2 separate files   - autocomplete.phtml and instantsearch.phtml
  - internal JS code and template files were moved to separate `internals` folder
  - names were assigned to templates blocks
  - Parts of JS code are commented with links to it's documentation
  - **BC break** - if you use you own templates, they must be updated for new templates' structure
- New version of [instantsearch.js](https://github.com/algolia/instantsearch.js) library updated
- Refactored [products' re-indexing](https://community.algolia.com/magento/documentation/#full-products-reindex)
  - **BC break** - when you install this version, the indexing queue will be truncated. Full re-index is required.
- Added logging of updated/deleted products
- `name` and `description` attributes are now not casted before indexing. It solves issue with non-highlighted numerical products' names.
- All static strings can be localized via Magento localizator now
- All absolute skin URLs were replaces by Magento's built in `getSkinUrl` method
- Small usability improvements
- Upgrade the underlying PHP API client to 1.10.0
- Updated [documentation](https://community.algolia.com/magento/documentation/)

#### FIXES
- All images are now indexed with its base folder path. It fixes the issue with placeholder images and CDNs.
  - **BC break** - it's mandatory to reindex your products to index correct images paths of products
- Backbutton on instant-search page now respects the query and refinements
- Fixed issue when the warning about skipped/truncated products was not displayed sometimes
- Categories on the bottom of auto-complete menu are not displayed when instantsearch is disabled because of wrong links
- SKU of simple products is indexed correctly now
- Count of products in category is now indexed correctly
- XSS in auto-complete menu
- Pages from different stores are not displayed in auto-complete menu anymore
- Undefined `algoliaConfig` variable in IE9
- Main content is no longer hidden on disabled JavaScript
- Fix not started instant search which caused bugs on products' details
- Prices are now indexed correctly with taxes

### 1.5.5

- NEW: Add an option to include data from out-of-stock sub products
- NEW: Use secured api keys to only retrieve one group price in the frontend
- NEW: Better update strategy to simplify the indexer code and to avoid missing deleted products event
- UPDATE: Better handling of include in navigation config
- UPDATE: underlying php client
- UPDATE: Conditionally render template directives
- UPDATE: Make sub product skus searchable
- FIX: slaves creation issue
- FIX: small price issue
- FIX: fallback to default search in case there is a error from the api


### 1.5.4

- UPDATED: instantsearch.js update
- FIX: issue with slaves creation
- FIX: issue for bundle products when customer group is enabled
- FIX: casting in resulting in bad typing in Algolia

### 1.5.3

- UPDATED: added a config to disable logging
- UPDATED: better instant search UI
- FIX: various design improvements
- FIX: take into account "Include in Navigation" for categories
- FIX: sorting in instant search page
- FIX: wrong price for configurable products
- FIX: mass action delete

### 1.5.2

==== BREAKING CHANGES ====

- A full reindex of the product indexer is needed

==========================

- NEW: handle multiple currencies
- UPDATED: improve errors/warnings for reindexing 
- FIX: handle both secure and unsecure base url for images
- FIX: ability to have only instant search
- FIX: method to get product and categories url

### 1.5.1

- FIX: xss issue

### 1.5.0

==== BREAKING CHANGES ====

- The queue is now running outside of the Magento default cron system. To run the jobs you will need to run
  the `algolia_queue_runner` indexer via the following command `php -f shell/indexer.php --reindex algolia_queue_runner`
  You can add it to your crontab just add this line:
  `*/5 * * * * php -f /absolute/path/to/magento/shell/indexer.php -- -reindex algolia_queue_runner`
- The version is fixing a bug around deleted products that were not deleted from Algolia. To be sure you're in sync you should clear your products indices from the Algolia dashboard.
- As this is a major update you will loose your settings and will need to reconfigure the extension

=========================

- NEW: replace custom logic with autocomplete.js and instantsearch.js
- NEW: add `total_ordered` because `ordered_qty` does not always make sense
- NEW: add a drag and drop feature to reorder the tables in the administration panel
- UPDATED: smarter queue logic that is able to batch jobs
- UPDATED: option to have most popular suggestions when no results page
- FIX: fix an issue with configurable and grouped sub_products query
- FIX: replace image helper overridden by subclass

### 1.4.8
- NEW: allow to have custom product types
- NEW: make image generation size parameter customizable to be able to save resources when already in cache
- UPDATED: remove root category when fetching product categories
- UPDATED: rewrite image class to be able to log the error when not being able to generate it
- UPDATED: Handle display price with AND without tax
- FIX: price issues
- FIX: Safari issue with instant search

### 1.4.7

- NEW: added an option to disable the seo request
- NEW: added ability to disable (search OR search + indexing) per store
- NEW: added logging
- UPDATED: improve handling of out of stock products
- UPDATED: improve performance overall indexing performance
- FIX: issue with category ids
- FIX: issue with configurable product additionnal attributes
- FIX: corner case for price with visibility and stock options

### 1.4.6

- UPDATED: Price handling, no template update required anymore + correct handling of special price + correct handling of tax
- UPDATED: indexing process has been optimized
- FIX: add emulation for pages to have correct urls
- UPDATED: Separate category and product indexer

### 1.4.5

- FIX: Improve performance issue when backend-search
- FIX: Various small improvements

### 1.4.4

- NEW: Handle bundle products
- FIX: Handling of multiple currencies

### 1.4.3

- UPDATED: improve indexing performance
- FIX: Issue with importing AlgoliaSearch
- FIX: CSS issue
- FIX: Prevent purchase from crashing if Algolia account is blocked

### 1.4.2

- FIX: Issue with slider

### 1.4.1

- NEW: Add analytics tags
- UPDATED: Customer group implementation (to lower the number of records)
- FIX: Amazon like suggestion
- FIX: special price in case of partial updates
- FIX: No results page (facets)

### 1.4.0

- NEW: The design of the instant search is now responsive and mobile friendly
- NEW: Add the possibility to autocomplete on an attribute like brands, manufacturer, ...
- NEW: Handling of customer groups
- NEW: Add the possibility to do `partialUpdates` instead of `addObjects` to allow external sources to patch the records
- UPDATED: Handle empty query
- FIX: IE8 & IE9 compatibility issues
- FIX: Ability to use the extension if the magento instance is hosted in a subdir
- FIX: Add missing translations in the template
- FIX: Fixed a bug occurring while concatenating/minifying the JavaScript code

### 1.3.5

- UPDATED: Administration panel review
- UPDATED: Handling of the click on the magnifying glass
- UPDATED: Fixed instant search page if auto-completion menu is disabled
- NEW: Grouped/configurable attributes handling

### 1.3.4

- UPDATED: Fixed URL redirection

### 1.3.3

- UPDATED: Fixed some attributes retrieval

### 1.3.2

- NEW: Add out of stock records
- Fix issue with reloading

### 1.3.0

- NEW: Redesign the UX if both the autocompletion menu and the instant results page are enabled
- NEW: Add the indexing of suggestions + implement an Amazon-like suggestions engine
- NEW: Handling of special price
- UPDATED: Indexing of pages is now in a separate indexer
- UPDATED: Clean the underlying JS code

### 1.2.3
- NEW: add option to disable branding
- UPDATED: fix issue with autocomplete templating

### 1.2.2
- NEW: Package & isolate all JS code + dependencies
- UPDATED: Default slider style improvements
- UPDATED: Display the category leaves only in the auto-completion menu

### 1.2.1
- UPDATED: Fix a JavaScript bug on Firefox
- UPDATED: Fix a JSON encoding issue with categories

### 1.2.0
- NEW: Add an instant search implementation
- NEW: Administration configuration layout cleanup & improvements
- UPDATED: Rewrite the indexing flow
- UPDATED: Here and there bug fixes

### 1.1.4
- Fix call to undefined method isIndexProductCount

### 1.1.3
- Change url protocol of thumbnail
- Fix bug affecting search in the backend due to bad objectID

### 1.1.2
- Fix issues with product deletions
- Add support of pages
- Add a setting on save of config

### 1.1.1
- Add price with tax to product records
- Add a retrievable column to config

### 1.1.0
 - Administration panel refactoring & rewriting:
   - simplify the searchable attributes configuration
   - simplify the custom ranking configuration
   - use the number of products in a category as the default popularity criteria
   - use the number of sales as the default popularity criteria
 - Improve the default auto-completion menu style
 - Tested with Magento 1.6.2, 1.7.1, 1.8.1 and 1.9 and PHP 5.3, 5.4, 5.5 and 5.6
 - Upgrade the underlying Algolia PHP API client to 1.5.5 (high available DNS)

### 1.0.3
 - Upgrade the underlying PHP API client to 1.5.5
 - Fix deadlock that may occur with order processing
 - Fix results saved every search (remove flag probably added for debugging).
