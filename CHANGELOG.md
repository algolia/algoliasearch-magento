## Change Log

### 1.5.0

==== BREAKING CHANGE ====

- The queue is now runned outside of the Magento default cron system. To run the jobs you will need to run
  the `algolia_queue_runner` indexer via the following command `php -f shell/indexer.php --reindex algolia_queue_runner`
  You can add it to your crontab just add this line:
  `*/5 * * * * php -f /absolute/path/to/magento/shell/indexer.php --reindex algolia_queue_runner`

=========================

- NEW: replace custom logic by autocomplete.js and instantsearch.js
- NEW: add total_ordered because ordered_qty does not always make sense
- NEW: add drag and drop for grid in the config page
- UPDATED: More intelligent queue that is able to batch jobs
- UPDATED: Option to have most popular suggestions when no result page
- FIX: issue with configurable and gouped sub_products query
- FIX: replace image helper override by subclass

### 1.4.8
- NEW: allow to have custom product types
- NEW: make image generation size parameter customizable to be able to save ressources when already in cache
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
 - Upgrade the underlying PHP API client to 1.5.4
 - Fix deadlock that may occur with order processing
 - Fix results saved every search (remove flag probably added for debugging).
