algoliaBundle.$(function ($) {
	AlgoliaAnalytics.init({
		appId: algoliaConfig.applicationId,
		apiKey: algoliaConfig.instant.apiKey
	});

	// "Click" in autocomplete
	$(algoliaConfig.autocomplete.selector).each(function () {
		$(this).on('autocomplete:selected', function (e, suggestion, dataset) {

			var sources = analyticsHelper.sources;
			var source = sources.filter(function(src) {
				return src.name == dataset;
			});

			if (source.length > 0) {
				var source = source[0];
				trackClick(source.indexName, suggestion.objectID, suggestion.__position, suggestion.__queryID);
			}
		});
	});

	// "Click" on instant search page
	$(document).on('click', algoliaConfig.ccAnalytics.ISSelector, function() {
		var $this = $(this);
		var lastResults = analyticsHelper.getLastResults();

		// want to track results returned
		if (lastResults) {
			trackClick(lastResults.index, $this.data('objectid'), $this.data('position'), $this.data('queryid'));
		}
	});

	// "Add to cart" conversion
	if (algoliaConfig.ccAnalytics.conversionAnalyticsMode === 'add_to_cart') {
		function getQueryParamFromCurrentUrl(queryParamName) {
			var url = window.location.href;
			var regex = new RegExp('[?&]' + queryParamName + '(=([^&#]*)|&|#|$)');
			var results = regex.exec(url);
			if (!results || !results[2]) return '';
			return results[2];
		}

		$(document).on('click', algoliaConfig.ccAnalytics.addToCartSelector, function () {
			var objectId = $(this).data('objectid') || getQueryParamFromCurrentUrl('objectID');
			var queryId = $(this).data('queryid') ||  getQueryParamFromCurrentUrl('queryID');
			var index = algoliaConfig.indexName + "_products" ||  getQueryParamFromCurrentUrl('index');

			trackConversion(index, objectId, queryId);
		});
	}

	if (algoliaConfig.ccAnalytics.conversionAnalyticsMode === 'place_order') {

		if (typeof algoliaOrderConversionJson !== 'undefined') {
			$.each(algoliaOrderConversionJson, function(idx, itemData) {
				if (itemData && itemData.objectID) {
					trackConversion(itemData.indexName, itemData.objectID, itemData.queryID);
				}
			});
		}
	}

});

var analyticsHelper = {};

algolia.registerHook('beforeAutocompleteSources', function(sources) {
	analyticsHelper.sources = sources;
	return sources;
});

algolia.registerHook('beforeInstantsearchStart', function (search) {
	search.once('render', function() {
		analyticsHelper.getLastResults = function () {
			return search.helper.lastResults;
		}
	});
	return search;
});

algolia.registerHook('beforeInstantsearchInit', function (instantsearchOptions) {
	instantsearchOptions.searchParameters['clickAnalytics'] = true;
	return instantsearchOptions;
});

function trackClick(index, objectID, position, queryId) {
	var clickData = {
		index: index,
		eventName: "Clicked item",
		objectIDs: [objectID.toString()],
		positions: [parseInt(position)],
		queryID: queryId
	};

	AlgoliaAnalytics.clickedObjectIDsAfterSearch(clickData);
}

function trackConversion(index, objectID, queryId) {
	AlgoliaAnalytics.convertedObjectIDsAfterSearch({
		index: index,
		eventName: "Conversion",
		objectIDs: [objectID.toString()],
		queryID: queryId,
	});
}
