# CHANGE LOG

## 1.14.0

### FEATURES
- Add option to index empty categories (#992)
- Search for facet values (#1020) 
- Facet query rules (#1025) 
- Click & Conversion analytics (#1027) 

### UPDATES
- Show longest attribute when the record is skipped (#976)
- Add CSS classes to InstantSearch widget containers (#977 )
- Hide the instant-search selector on algoliaConfig variable (#981)
- Escape JS suggestions in autocomplete menu (#984)
- Never index SID in URL (#985)
- Skip rebuilding CMS page(s) when the extension has not been configured (#1000)
- Add "filters" rule to ranking formula (#1005)
- Display default price when customer group is 0 (#1011)
- Automatically delete unused replica indices (#1019) 
- Remove category from indexes of all store views (#1028) 

### FIXES
- Fix configurable products' prices (#978)
- Fix bug #979 (#980) 
- Fix expired special prices (#988)
- Fix special price with customer groups (#997)
- Fix buggy behavior on iOS (#1009)


## 1.13.0

### FEATURES
- Optimized template rendering - no useless templates are rendered now (#947)
- Configure "Prevent backend rendering" feature to specify which categories shouldn't be rendered on backend (#958)
- Infinite scrolling feature (#796)

### UPDATES
- Updated dev Docker containers (#941, #971)
- Updated documentation links in administration (#946)
- Queue notification hides when queue is empty (#945)
- Only unique products are fetched during reindex (#963)
- Magento indexers are now correctly updated when Algolia is disabled for them (#964)
- Queue controller renamed to not conflict with other extensions (#970)
- Release archive now contains admin static content (#968)
- Optimized TravisCI (#967)

### FIXES
- Fixed instant search page with no results - now it displays better "no results" message (#942)
- Fixes `special_price` fetching (#944)
- Fixed default value for "adaptive image" configuration input (#953)
- Fixed encoding of CMS pages (#957)
- Fixed buggy hover on in autocomplete menu on iOS devices (#956)
- Fixed "additional sections" feature with enabled customer groups (#961)
- Fixed empty attributes for configurable products (#962)

## 1.12.0

Since this release, the extension is **Enterprise Edition compliant**!

### FEATURES
- Experimental feature to prevent backend rendering of category and search results pages (#886)
    - Use very carefully and read [documentation](https://community.algolia.com/magento/doc/m1/prevent-backend-rendering/) before enabling it
- Introduced events and developer attributes to force add / remove product(s) to / from Algolia (#922)
- Added to option to turn off administration top bar with information about queue (#920)

### UPDATES
- Changed some links in the configuration

### FIXES
- Fixed failing database migration for creation of queue log table (#927)

## 1.11.1

- Query rules are preserved during reindex with indexing queue enabled (#913)
- Information about the indexing queue is displayed in admin-wide notifications (#905)
- Information about queue processing are logged to `algoliasearch_queue_log` DB table (#907)

## 1.11.0

### FEATURES
- Added option to turn on autocomplete's `debug` option from configuration (#865)
- The extension now displays the right image for a color variant depending on search query or selected color filter (#883)

### UPDATES
- Added CSS class for proper function of collapsible IS widgets (#859)
- Changed Magento archive URLs in dev containers (#874)
- Updated Magento 1.9.3 version to the latest one in dev container (#882)
- Optimization of `getPopularQueries()` method (#888)

### FIXES
- Fixed the hardcode admin URL for fetching queue info (#854)
- Fixed issue when some attributes weren't set as retrievable when custom groups were enabled (#856)
- Fixed back button which returned all products on category pages (#852)
- Removed not-necessary additional query on category page (#852)
- Fixed displayed link to analytics documentation (#869)
- Fixed store specific facets (#868)
- Fixed option to disable the module by disabling it's output (#866)


## 1.10.0

### FEATURES
- **BC Break** - JS hooks - instantsearch.js file was completely refactored to create IS config object which can be manipulated via hook method (#822)
- The indexed prices now include `WEEE` tax (#829)
- The configuration page now displays information about the indexing queue and gives possibility to clear the queue (#849)

### UPDATES
- Optimized assets - removed useless images (#820)
- Synonyms management from Magento is turned off by default (#832)
- Instance of `algolia_client` is now passed as thrid parameter to `algoliaHookBeforeAutocompleteStart` hook method (#847)

### FIXES
- The correct price is now used for sorting with multistore / multicurrency setup (#818)
- Fixed SVG icons when using Magento's merge CSS feature (#819)
- Attributes to retrieve now contains attributes for categories (#827)
- Fix the issue with Algolia error when more than 1000 products are supposed to be deleted (#838)
- Correct products are displayed on category page when categories attribute is not set as attribute for faceting (#846)


## 1.9.0

### FEATURES
- JavaScript custom events to easily extend extension's front end (#642, [Documentation](https://community.algolia.com/magento/doc/m1/analytics/))
- Analytics - the extension now uses Magento's GA to measure searches (#754)
- New queue processing mechanism which makes queue processing much more optimized in terms of Algolia operations and processing time (#757, #775)
- Create a "debug" script which generates a file with dumped Algolia configuration. This file then can be sent to Algolia's support for easier investigation (#777)
- Option to send an extra Algolia settings to Algolia indices (#770)
- Ability to enabled / disable indexing and front end search separatly (#793)

### UPDATES
- The extension now completely removes `<script>` and `<style>` tags with its content from CMS pages content (#765)
- The extension now initializes a Magento translator in order to make it available in custom events' methods (#789)
- Test container now accepts two parameters (#794):
    - `--xdebug` to install the container with XDebug
    - `--filter` to filter running tests
- `Engine` class is now loaded via `getResourceModel` method (#798)

### FIXES
- Fixed `clearfix` class in CSS (#772)
- Fixed the issue when search box cursor was moved to the end of a search query (#779)
- Fixes category refinement on category page power by instant search when page reload was performed (#783)
- Fixed the issue when instant search results stayed on the page even after click on cross in a search box (#784)
- Fixed the issue when `array_combine()` method could be called with empty array (#790)
- Fixed pagination on IS page (#805)


## 1.8.1

### FEATURES
- Ability to index only products with specific visibility (Search, Catalog, Both) (#731)
- Product entity is now passed to a custom events called in `getObject` methods (#733)
- Dutch translations (#746)

### UPDATES
- "releases" directory has been removed from the repository. Release archives will be now added to GitHub releases. (#724)
- Refined category is now shown in Current refinements section on replaced category page (#725)
- Queue runner closes DB connection when finished (#736)
- Dev Docker container now uses PHP 7.0 (#734, #743)
- The extension is now tested with PHPUnit and checked on TravisCI (#735, #749 )

### FIXES
- Fixed wiped out settings from Algolia products' indices (#753, #756)
- When Algolia is disabled with `algoliasearch/credentials/enable_backend` for a store it's not returning any search results (#742)

## 1.8.0

### FEATURES
- Added new custom event `algolia_before_products_collection_load` which is triggered before products' collection loads (#666)
- Ability to remove categories or products from autocomplete menu (#702)

### UPDATES
- Empty values of `color` attribute are not indexed by default (#651)
- Custom events were review and renamed. New events were added to `setSettings` methods for difeerent datatypes. Old events are still present to keep backward compatibility. (#652)
- Compatibility with Magento >= 1.9.3 CC and >= 1.14.3 EE (#678, #688)
- **BC Break** - Removed `Varien_Object` transport object from events which are passing objects and not arrays (#669)
- The latest version of PHP API client with new retry strategy (#698)
- Change input type for Admin API key from `obscure` to `password` as it caused issues on different platforms (#695)
- New versions of Algolia javascript libraries (#696)
- The latest version of Algolia PHP API client (#698)
- The extension now sends `searchableAttributes` index setting instead of deprecated `attributesToIndex` (#708)
  - **BC Break** - if you use `attributesToIndex` somewhere in your Magento-related code (ie. events) change it to `searchableAttributes`
- The extension now sends `replicas` index setting instead of deprecated `slaves` (#712)

### FIXES
- There is no need to explicitly set `categories` attribute as facet when "Replace category pages by Instant Search" set to "Yes" (#650)
- Reindexing with indexing queue enabled now preserves set synonyms via Algolia dashboard (#693)
- Small warning fix (#664)
- Autocomplete menu on mobile is now not hiding when keyboard is hidden (#709)
- Fetching data in queue runner now runs in isolated transaction so the jobs won't be performed twice (#713)
  - Solves the issue when double move operation of TMP indices wiped out the index settings

## 1.7.2

### FEATURES
- Ability to select sort and ranking attributes from all attributes, not only indexed ones. Those attributes are indexed automatically with proper settings (searchable, retrievable, ...) (#632)
- Swedish translation (#613)

### UPDATES
- "No"/empty values can now be removed from all attributes, not only from attributes fetched from children products (#623)
- Ability to select autocomplete menu item by hitting Enter (#626)
- For better relevancy hardcoded searchable attributes (in pages, suggestions, additional sections ...) set as `unordered` (#634)

### FIXES
- Missing semicolon in instantsearch.js file (#630)
- Product reindexing when using `catalogsearch_fulltext` indexer name (#633)
- Hide API key from configuration's HTML code (#637)
- Fixed fatal error on delete product (#640)
- Fixed untranslatable hard-coded strings in stats widget template (#641)

## 1.7.1

### FEATURES
- Option to stop the extension from overriding synonyms (#580)
- New attribute `main_categories` which contains product's category structure without path (#581)
- Option to index only categories where product actually is without it's parent categories (#584)
	- **Breaks hierarchical widget, use wisely**
- Option to display popular queries (suggestions) without categories (#586)
- Option to reindex all category's products on category save (#598)
- Option to use non-selectable attribute as custom ranking by writing it's name in configuration (#603)
- Added categories' level attributes to Products' Attributes select boxes so it's possible to use specific levels for relevancy purposes (#621)

### UPDATES
- Improved relevancy for suggestions by setting [removeWordsIfNoResults](https://www.algolia.com/doc/api-client/php/parameters#removewordsifnoresults) to `lastWords` (#575)
- All CSS selectors were prefixed with Algolia containers and unused styles were removed (#578)
	- **BC break** - please check the look & feel of your results
- The note about new version is less agressive and does not feel as error anymore (#579)
- Bundled products now does not take news dates and special price dates from it's sub-products (#580)
- New versions of [instantsearch.js](https://github.com/algolia/instantsearch.js) and [autocomplete.js](https://github.com/algolia/autocomplete.js) libraries (#588)
- Column `data` in `algoliasearch_queue` table changed to LONGTEXT (#596, #597)
- Optimized number of products processed by removing duplicate products from processing (#599)
- Enable to select `in_stock` attribute as attribute for faceting (#602)
- Updated PHP client (#611)
- Updated "Disable extension" label (#619)
- Searchable attributes are set as Unordered by default (#624)

### FIXES
- Info panel with sorting selectbox is now hidden on no results (#576)
- Issue with decimal numbers displayed in current price refinements (#588)
- PHP 5.3 compatibility (#605, #608)
- Displaying all products by clearing all filters by clearAll instantsearch.js widget on instant search category page (#604, #609)
- Fixed bug when `categories_without_path` was always set as searchable attribute (#621)

## 1.7.0

### FEATURES
- Option to choose if attributes' "No" values should or shouldn't be indexed (#554)

### UPDATES
- Optimized front-end - **BC break** (#507, #531)
	- All JS code except the extension's config and JS templates was moved to separate JS files
	- Reduced the size of inline HTML code
	- Better caching abilities
	- Better readability of the code
	- Removed templateloader.phtml file
        - Use `allItems` template in hits widget instead of single `item` (#553)
- Restored support of PHP 5.3 (#524)
- The extension now follows the standart file structure as Magento (#517)
- All extension's assets were move to CSS files as SVGs (#521)
	- Small visual updates in icons
	- Supports retina displays now
- New versions of [instantsearch.js](https://github.com/algolia/instantsearch.js) and [autocomplete.js](https://github.com/algolia/autocomplete.js) libraries (#549)
- PHP API client updated to 1.10.2 version (#529)
- The extension follows new Algolia's UA convention (#530)
- Updated [FAQ](https://community.algolia.com/magento/faq/) (#509, #519, #525, #536)

### FIXES
- Fixed issue with CDN images (#518)
	- Whole images' URLs are now indexed 
	- **BC break!** It's mandatory to reindex all your data
- Fixed issue with overriding `top.search` block (#531)
- Encoded query attribute within additional sections in autocomplete menu (#534)
- Fixed notices from `array_unique` (#552)

## 1.6.1

### UPDATES
- JavaScript templates were split to separate files (#483)
- Locale CSV file was moved from `en_GB` to `en_US` directory (#481)
- Template loader can load more then only two blocks (#502)

### FIXES
- Fixed bug when `top.search` block was not overriden in some themes (#500)
- Fixed bug with reindexing with empty synonyms lines (#480)
- Localized strings are properly escaped in JavaScript now (#488)
- Fixed occasional warnings in `getProductsRecords` method (#503)
- Fixed duplicite CSS class names (#484)
	- Potential **BC break** - please review your design and CSS so it follows new classes


## 1.6.0

### NEW FEATURES
- Support of synonyms API
- Facets can be chosen from all attributes now
- Added option to remove products from Algolia index when performing full re-index. By default the feature is disabled.
- Added warning if you use old version of the extension
- *Index settings* are set to products' indices on full products re-index
- Code now follows PSR2 standarts, annotations were added to most unresolveable variables
- Added option to index products when they are added/removed to/from category via "Manage category". By default the feature is enabled.
  - **BC break** - it may cause difficulties on large stores. If it does so, please disable the feature in Configuration.
- Added locale CSV file for easier translations


### UPDATES
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

### FIXES
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

## 1.5.5

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


## 1.5.4

- UPDATED: instantsearch.js update
- FIX: issue with slaves creation
- FIX: issue for bundle products when customer group is enabled
- FIX: casting in resulting in bad typing in Algolia

## 1.5.3

- UPDATED: added a config to disable logging
- UPDATED: better instant search UI
- FIX: various design improvements
- FIX: take into account "Include in Navigation" for categories
- FIX: sorting in instant search page
- FIX: wrong price for configurable products
- FIX: mass action delete

## 1.5.2

==== BREAKING CHANGES ====

- A full reindex of the product indexer is needed

==========================

- NEW: handle multiple currencies
- UPDATED: improve errors/warnings for reindexing 
- FIX: handle both secure and unsecure base url for images
- FIX: ability to have only instant search
- FIX: method to get product and categories url

## 1.5.1

- FIX: xss issue

## 1.5.0

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

## 1.4.8
- NEW: allow to have custom product types
- NEW: make image generation size parameter customizable to be able to save resources when already in cache
- UPDATED: remove root category when fetching product categories
- UPDATED: rewrite image class to be able to log the error when not being able to generate it
- UPDATED: Handle display price with AND without tax
- FIX: price issues
- FIX: Safari issue with instant search

## 1.4.7

- NEW: added an option to disable the seo request
- NEW: added ability to disable (search OR search + indexing) per store
- NEW: added logging
- UPDATED: improve handling of out of stock products
- UPDATED: improve performance overall indexing performance
- FIX: issue with category ids
- FIX: issue with configurable product additionnal attributes
- FIX: corner case for price with visibility and stock options

## 1.4.6

- UPDATED: Price handling, no template update required anymore + correct handling of special price + correct handling of tax
- UPDATED: indexing process has been optimized
- FIX: add emulation for pages to have correct urls
- UPDATED: Separate category and product indexer

## 1.4.5

- FIX: Improve performance issue when backend-search
- FIX: Various small improvements

## 1.4.4

- NEW: Handle bundle products
- FIX: Handling of multiple currencies

## 1.4.3

- UPDATED: improve indexing performance
- FIX: Issue with importing AlgoliaSearch
- FIX: CSS issue
- FIX: Prevent purchase from crashing if Algolia account is blocked

## 1.4.2

- FIX: Issue with slider

## 1.4.1

- NEW: Add analytics tags
- UPDATED: Customer group implementation (to lower the number of records)
- FIX: Amazon like suggestion
- FIX: special price in case of partial updates
- FIX: No results page (facets)

## 1.4.0

- NEW: The design of the instant search is now responsive and mobile friendly
- NEW: Add the possibility to autocomplete on an attribute like brands, manufacturer, ...
- NEW: Handling of customer groups
- NEW: Add the possibility to do `partialUpdates` instead of `addObjects` to allow external sources to patch the records
- UPDATED: Handle empty query
- FIX: IE8 & IE9 compatibility issues
- FIX: Ability to use the extension if the magento instance is hosted in a subdir
- FIX: Add missing translations in the template
- FIX: Fixed a bug occurring while concatenating/minifying the JavaScript code

## 1.3.5

- UPDATED: Administration panel review
- UPDATED: Handling of the click on the magnifying glass
- UPDATED: Fixed instant search page if auto-completion menu is disabled
- NEW: Grouped/configurable attributes handling

## 1.3.4

- UPDATED: Fixed URL redirection

## 1.3.3

- UPDATED: Fixed some attributes retrieval

## 1.3.2

- NEW: Add out of stock records
- Fix issue with reloading

## 1.3.0

- NEW: Redesign the UX if both the autocompletion menu and the instant results page are enabled
- NEW: Add the indexing of suggestions + implement an Amazon-like suggestions engine
- NEW: Handling of special price
- UPDATED: Indexing of pages is now in a separate indexer
- UPDATED: Clean the underlying JS code

## 1.2.3
- NEW: add option to disable branding
- UPDATED: fix issue with autocomplete templating

## 1.2.2
- NEW: Package & isolate all JS code + dependencies
- UPDATED: Default slider style improvements
- UPDATED: Display the category leaves only in the auto-completion menu

## 1.2.1
- UPDATED: Fix a JavaScript bug on Firefox
- UPDATED: Fix a JSON encoding issue with categories

## 1.2.0
- NEW: Add an instant search implementation
- NEW: Administration configuration layout cleanup & improvements
- UPDATED: Rewrite the indexing flow
- UPDATED: Here and there bug fixes

## 1.1.4
- Fix call to undefined method isIndexProductCount

## 1.1.3
- Change url protocol of thumbnail
- Fix bug affecting search in the backend due to bad objectID

## 1.1.2
- Fix issues with product deletions
- Add support of pages
- Add a setting on save of config

## 1.1.1
- Add price with tax to product records
- Add a retrievable column to config

## 1.1.0
 - Administration panel refactoring & rewriting:
   - simplify the searchable attributes configuration
   - simplify the custom ranking configuration
   - use the number of products in a category as the default popularity criteria
   - use the number of sales as the default popularity criteria
 - Improve the default auto-completion menu style
 - Tested with Magento 1.6.2, 1.7.1, 1.8.1 and 1.9 and PHP 5.3, 5.4, 5.5 and 5.6
 - Upgrade the underlying Algolia PHP API client to 1.5.5 (high available DNS)

## 1.0.3
 - Upgrade the underlying PHP API client to 1.5.5
 - Fix deadlock that may occur with order processing
 - Fix results saved every search (remove flag probably added for debugging).
