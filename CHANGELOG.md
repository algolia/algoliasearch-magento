## Change Log

### 1.2.3
- add option to disable branding
- fix issue with autocomplete templating

### 1.2.2
- Package & isolate all JS code + dependencies
- Default slider style improvements
- Display the category leaves only in the auto-completion menu

### 1.2.1
- Fix a JavaScript bug on Firefox
- Fix a JSON encoding issue with categories

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
