algoliaBundle.$(function ($) {
	AlgoliaAnalytics.init({
		appId: algoliaConfig.applicationId,
		apiKey: algoliaConfig.instant.apiKey
	});

	// "Click" in autocomplete
	$(algoliaConfig.autocomplete.selector).each(function () {
		$(this).on('autocomplete:selected', function (e, suggestion, dataset, context) {

			var sources = analyticsHelper.sources;
			var source = sources.find(function(e) {
				return e.name == dataset;
			});

			trackClick(source.indexName, suggestion.objectID, suggestion.__position, suggestion.__queryID);
		});
	});

	// "Click" on instant search page
	$(document).on('click', algoliaConfig.ccAnalytics.ISSelector, function() {
		var $this = $(this);
		var lastResults = analyticsHelper.getLastResults();

		// want to track results returned
		if (lastResults) {
			trackClick(lastResults.index, $this.data('objectid'), $this.data('position'), lastResults.queryID ? lastResults.queryID : lastResults._rawResults[0].queryID);
		}
	});

	// "Add to cart" conversion
	if (algoliaConfig.ccAnalytics.conversionAnalyticsMode === 'add_to_cart') {
		$(document).on('click', algoliaConfig.ccAnalytics.addToCartSelector, function () {
			var objectId = $(this).data('objectid') || algoliaConfig.productId;

			if (!objectId) {
				var postData = $(this).data('post');
				if (!postData || !postData.data.product) {
					return;
				}

				objectId = postData.data.product;
			}

			// "setTimeout" ensures "trackConversion" is always triggered AFTER "trackClick"
			// when clicking "Add to cart" on instant search results page
			setTimeout(function () {
				trackConversion(algoliaConfig.indexName + "_products", objectId);
			}, 0);
		});
	}

	// "Place order" conversions
	// "algoliaConfig.ccAnalytics.orderedProductIds" are set only on checkout success page
	if (algoliaConfig.ccAnalytics.conversionAnalyticsMode === 'place_order'
		&& algoliaConfig.ccAnalytics.orderedProductIds.length > 0) {
		trackConversion(algoliaConfig.indexName + "_products", algoliaConfig.ccAnalytics.orderedProductIds);
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

function trackClick(indexName, objectID, position, queryId) {
	var clickData = {
		index: indexName,
		eventName: "Clicked item",
		objectIDs: [objectID.toString()],
		positions: [parseInt(position)],
		queryID: queryId
	};

	AlgoliaAnalytics.clickedObjectIDsAfterSearch(clickData);
}

function trackConversion(indexName, objectIDs) {
	AlgoliaAnalytics.convertedObjectIDsAfterSearch({
		index: indexName,
		eventName: "Conversion",
		objectIDs: Array.isArray(objectIDs) ? objectIDs : [objectID.toString()]
	});
}