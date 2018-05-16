algoliaBundle.$(function ($) {
	AlgoliaAnalytics.init({
		applicationID: algoliaConfig.applicationId,
		apiKey: algoliaConfig.instant.apiKey
	});

	$(algoliaConfig.autocomplete.selector).each(function () {
		$(this).on('autocomplete:selected', function (e, suggestion) {
			AlgoliaAnalytics.click({
				queryID: suggestion.__queryID,
				objectID: suggestion.objectID,
				position: suggestion.__position
			});
		});
	});

	$(document).on('click', algoliaConfig.ccAnalytics.ISSelector, function() {
		var $this = $(this);
		AlgoliaAnalytics.click({
			objectID: $this.data('objectid').toString(),
			position: parseInt($this.data('position'))
		});
	});
});

algolia.registerHook('beforeInstantsearchInit', function (instantsearchOptions) {
	instantsearchOptions.searchParameters['clickAnalytics'] = true;

	return instantsearchOptions;
});

algolia.registerHook('beforeInstantsearchStart', function (search) {

	search.once('render', function() {
		AlgoliaAnalytics.initSearch({
			getQueryID: function() {
				console.log(search);
				return search.helper.lastResults && search.helper.lastResults._rawResults[0].queryID
			}
		});
	});

	return search;
});
