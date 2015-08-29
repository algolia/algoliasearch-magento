# FAQ


## Things to check before anything:
 
- You are on last version (currently 1.4.3) this can be checked by looking a the user agent of a build query
- Catalog has been indexed at least once.

## Warning saying an index not up to date ?

Exepected to avoid reindexing categories on product update. The count is false so a reindexing is needed

## The indexing is not working or is working but stops at 100*n products

Try to update the value in

```
System > Configuration > Algolia Search > Queue Configuration
> Max number of products by indexing job
```

to ```2``` and **Reindex data**


If it works it means than you have some kind of memory issue and you should fine the right settings you can try from 10 and increase it to 15, 20, ..., and take the last working value. The higher the value is the quicker the indexing will finish

## Uncaught [Algolia] You can't have a search input matching "#search" inside you instant selector ".main"

To avoid the search input for autocomplete to disapear when you type something (it's inside of the div where the instant search content will be created) we throw this error.

To fix it you need to change this settings : 
```
System > Configuration > Algolia Search > Instant Search Results Page Configuration
DOM Selector to the best suited selector
```

## I installed your search extension on my magento store but after that my website template got messy

To be able to have the results instantly, displaying the results or a category page in done directly via javascript with a default html and css avoiding us to go through the magento backend. It's expected that the magento template is not used and you need to modify the default result layout:

- The html can be find in this file: https://github.com/algolia/algoliasearch-magento/blob/master/design/frontend/template/topsearch.phtml

- The css can be find in the file: https://github.com/algolia/algoliasearch-magento/blob/master/skin/algoliasearch.css
