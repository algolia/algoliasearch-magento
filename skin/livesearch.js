var AlgoliaLiveSearch = Class.create();
AlgoliaLiveSearch.prototype = {
    initialize: function(options) {
        this.options = Object.extend({
            placeholder: 'Search...',
            applicationID: null,
            apiKey: null,
            indexName: null,
            searchDelay: 100,
            minLength: 2,
            queryOptions: {
                hitsPerPage: 10,
                tagFilters: '(product,category)',
                attributesToRetrieve: null,
                attributesToHighlight: 'name'
            },
            renderResults: null,
            clearResults: null
        }, options || {});
        this.searchForm = new Varien.searchForm('search_mini_form', 'search', this.options.placeholder);
        this.algolia = new AlgoliaSearch(this.options.applicationID, this.options.apiKey);
        this.searchTimeoutId = null;

        this.submitSearchCallback = this.submitSearch.bind(this);
        this.performSearchCallback = this.performSearch.bind(this);
        this.searchResultsCallback = this.searchResults.bind(this);

        this.searchForm.field
            .observe('keyup', this.submitSearchCallback)
            .observe('focus', this.submitSearchCallback)
            .observe('blur', this.options.clearResults ? this.options.clearResults.bind(this) : function(){});
    },
    submitSearch: function(event) {
        if (this.searchTimeoutId) {
            clearTimeout(this.searchTimeoutId);
        }
        this.searchTimeoutId = setTimeout(this.performSearchCallback, this.options.searchDelay);
    },
    performSearch: function() {
        var searchQuery = this.searchForm.field.getValue();
        if (searchQuery === '') {
            this.options.clearResults && this.options.clearResults.call(this);
            return;
        }
        if ( searchQuery.length >= this.options.minLength
          && searchQuery.lastIndexOf(' ') != searchQuery.length - 1
        ) {
            this.algolia.startQueriesBatch();
            this.algolia.addQueryInBatch(this.options.indexName, searchQuery, this.options.queryOptions);
            this.algolia.sendQueriesBatch(this.searchResultsCallback);
            Event.fire(document, 'algolia:search', {query: searchQuery});
        }
    },
    searchResults: function(success, content) {
        if ( ! success) {
            if (console) console.log(content);
            return;
        }
        if ( ! content.results
          || content.results.length != 1
          || this.searchForm.field.getValue() != content.results[0].query
        ) {
            return;
        }
        this.options.renderResults.call(this, content.results[0]);
    }
};
