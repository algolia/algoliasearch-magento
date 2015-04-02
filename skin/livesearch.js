var AlgoliaLiveSearch = Class.create();
AlgoliaLiveSearch.prototype = {
    initialize: function(options) {
        this.options = Object.extend({
            placeholder: 'Search...',
            applicationID: null,
            apiKey: null,
            indexName: null,
            searchDelay: 0,
            minLength: 0,
            resultLinks: null,
            categoriesQueryOptions: {
                hitsPerPage: 10,
                attributesToRetrieve: null,
                attributesToHighlight: 'name'
            },
            productsQueryOptions: {
                hitsPerPage: 10,
                attributesToRetrieve: null,
                attributesToHighlight: 'name'
            },
            renderResults: null,
            clearResults: null,
            markNext: null,
            markPrevious: null,
            selectEntry: null
        }, options || {});
        this.searchForm = new Varien.searchForm('search_mini_form', 'search', this.options.placeholder);
        this.algolia = new AlgoliaSearch(this.options.applicationID, this.options.apiKey);
        this.searchTimeoutId = null;
        this.active = false;
        this.index  = 0;

        this.submitSearchCallback = this.submitSearch.bind(this);
        this.performSearchCallback = this.performSearch.bind(this);
        this.searchResultsCallback = this.searchResults.bind(this);
        this.focusOutCallback = this.focusOut.bind(this);

        this.searchForm.field
            .observe('focus', this.submitSearchCallback)
            .observe('blur', this.options.clearResults ? this.options.clearResults.bind(this) : function(){})
            .observe('blur', this.focusOutCallback)
            .observe('keydown', this.onKeyPress.bindAsEventListener(this));

        if (Mage && Mage.Cookies && Mage.Cookies.get('lastSearchQuery')) {
            this.searchForm.field.value = Mage.Cookies.get('lastSearchQuery');
            Mage.Cookies.set('lastSearchQuery', '');
        }
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
        if (searchQuery.length >= this.options.minLength && searchQuery.lastIndexOf(' ') != searchQuery.length - 1)
        {
            this.algolia.startQueriesBatch();
            this.algolia.addQueryInBatch(this.options.indexName + '_products', searchQuery, this.options.productsQueryOptions);
            this.algolia.addQueryInBatch(this.options.indexName + '_categories', searchQuery, this.options.categoriesQueryOptions);
            this.algolia.sendQueriesBatch(this.searchResultsCallback);

            Event.fire(document, 'algolia:search', {query: searchQuery});
        }
    },
    focusOut: function(){
        this.active = false;
        this.index = 0;
    },
    searchResults: function(success, content) {
        if (! success) {
            if (console) console.log(content);
            return;
        }

        this.active = true;
        this.index = 0;
        this.selectedEntry = null;
        this.options.renderResults.call(this, content.results);
    },
    onKeyPress: function(event) {
        if(this.active) {
            switch (event.keyCode) {
                case 13:
                    return;
                case Event.KEY_RETURN:
                    this.selectEntry();
                    Event.stop(event);
                    return;
                case Event.KEY_ESC:
                    this.searchForm.field.blur();
                    Event.stop(event);
                    return;
                case Event.KEY_LEFT:
                case Event.KEY_RIGHT:
                    return;
                case Event.KEY_UP:
                    this.markPrevious();
                    Event.stop(event);
                    return;
                case Event.KEY_TAB:
                case Event.KEY_DOWN:
                    this.markNext();
                    Event.stop(event);
                    return;
            }
        }
        else
        if(event.keyCode==Event.KEY_TAB || event.keyCode==Event.KEY_RETURN ||
            (Prototype.Browser.WebKit > 0 && event.keyCode == 0)) return;

        this.submitSearch();
    },
    markNext: function() {
        this.selectedEntry = this.options.markNext ? this.options.markNext.call(this) : this.markEntry(1);
    },
    markPrevious: function(){
        this.selectedEntry = this.options.markPrevious ? this.options.markPrevious.call(this) : this.markEntry(-1);
    },
    selectEntry: function(){
        console.log(this.selectedEntry);
        if(this.options.selectEntry){
            this.options.selectEntry.call(this);
        }
        else if(this.selectedEntry){
            this.selectedEntry.click();
        }
    },
    markEntry: function(diff){
        if(this.options.resultLinks){
            if(!this.selectedEntry){
                diff = 0;
            }

            var links = this.options.resultLinks;

            if(links.length == 0){
                this.index = 0;
                return;
            }

            if(this.index+diff >= links.length || this.index+diff < 0){
                return;
            }

            this.index += diff;

            links.invoke('removeClassName', 'marked');
            links[this.index].addClassName('marked');

            return links[this.index];
        }

        return null;
    }
};
