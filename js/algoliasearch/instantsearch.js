document.addEventListener("DOMContentLoaded", function (event) {
	algoliaBundle.$(function ($) {
		
		/** We have nothing to do here if instantsearch is not enabled **/
		if (!algoliaConfig.instant.enabled || !(algoliaConfig.isSearchPage || !algoliaConfig.autocomplete.enabled)) {
			return;
		}
		
		if ($(algoliaConfig.instant.selector).length <= 0) {
			throw '[Algolia] Invalid instant-search selector: ' + algoliaConfig.instant.selector;
		}
		
		if (algoliaConfig.autocomplete.enabled && $(algoliaConfig.instant.selector).find(algoliaConfig.autocomplete.selector).length > 0) {
			throw '[Algolia] You can\'t have a search input matching "' + algoliaConfig.autocomplete.selector +
			'" inside you instant selector "' + algoliaConfig.instant.selector + '"';
		}
		
		var findAutocomplete = algoliaConfig.autocomplete.enabled && $(algoliaConfig.instant.selector).find('#algolia-autocomplete-container').length > 0;
		if (findAutocomplete) {
			$(algoliaConfig.instant.selector).find('#algolia-autocomplete-container').remove();
		}
		
		/**
		 * Setup wrapper
		 *
		 * For templating is used Hogan library
		 * Docs: http://twitter.github.io/hogan.js/
		 **/
		var wrapperTemplate = algoliaBundle.Hogan.compile($('#instant_wrapper_template').html());
		var instant_selector = !algoliaConfig.autocomplete.enabled ? algoliaConfig.autocomplete.selector : "#instant-search-bar";
		
		var div = document.createElement('div');
		$(div).addClass('algolia-instant-results-wrapper');
		
		$(algoliaConfig.instant.selector).addClass('algolia-instant-replaced-content');
		$(algoliaConfig.instant.selector).wrap(div);
		
		$('.algolia-instant-results-wrapper').append('<div class="algolia-instant-selector-results"></div>');
		$('.algolia-instant-selector-results').html(wrapperTemplate.render({
			second_bar: algoliaConfig.autocomplete.enabled,
			findAutocomplete: findAutocomplete,
			config: algoliaConfig.instant,
			translations: algoliaConfig.translations
		})).show();
		
		/**
		 * Initialise instant search
		 * For rendering instant search page is used Algolia's instantsearch.js library
		 * Docs: https://community.algolia.com/instantsearch.js/documentation/
		 **/
		
		var instantsearchOptions = {
			appId: algoliaConfig.applicationId,
			apiKey: algoliaConfig.instant.apiKey,
			indexName: algoliaConfig.indexName + '_products',
			urlSync: {
				useHash: true,
				trackedParameters: ['query', 'page', 'attribute:*', 'index']
			}
		};
		
		if (algoliaConfig.request.path.length > 0 && window.location.hash.indexOf('categories.level0') === -1) {
			if (algoliaConfig.areCategoriesInFacets) {
				instantsearchOptions.searchParameters = {
					hierarchicalFacetsRefinements: {
						'categories.level0': [algoliaConfig.request.path]
					}
				};
			} else {
				instantsearchOptions.searchParameters = {
					facetsRefinements: { }
				};
				
				instantsearchOptions.searchParameters['facetsRefinements']['categories.level' + algoliaConfig.request.level] = [algoliaConfig.request.path];
			}
		}
		
		if (typeof algoliaHookBeforeInstantsearchInit === 'function') {
			instantsearchOptions = algoliaHookBeforeInstantsearchInit(instantsearchOptions);
		}
		
		var search = algoliaBundle.instantsearch(instantsearchOptions);
		
		search.client.addAlgoliaAgent('Magento integration (' + algoliaConfig.extensionVersion + ')');
		
		/** Prepare sorting indicies data */
		algoliaConfig.sortingIndices.unshift({
			name: algoliaConfig.indexName + '_products',
			label: algoliaConfig.translations.relevance
		});
		
		/** Setup attributes for current refinements widget **/
		var attributes = [];
		$.each(algoliaConfig.facets, function (i, facet) {
			var name = facet.attribute;
			
			if (name === 'categories') {
				name = 'categories.level0';
			}
			
			if (name === 'price') {
				name = facet.attribute + algoliaConfig.priceKey
			}
			
			attributes.push({
				name: name,
				label: facet.label ? facet.label : facet.attribute
			});
		});
		
		var allWidgetConfiguration = {
			custom: [
				/**
				 * Custom widget - this widget is used to refine results for search page or catalog page
				 * Docs: https://community.algolia.com/instantsearch.js/documentation/#custom-widgets
				 **/
				{
					getConfiguration: function () {
						if (algoliaConfig.request.query.length > 0 && location.hash.length < 1) {
							return {
								query: algoliaConfig.request.query
							}
						}
						
						return {};
					},
					init: function (data) {
						var page = data.helper.state.page;
						
						if (algoliaConfig.request.refinementKey.length > 0) {
							data.helper.toggleRefine(algoliaConfig.request.refinementKey, algoliaConfig.request.refinementValue);
						}
						
						data.helper.setPage(page);
					},
					render: function (data) {
						if (!algoliaConfig.isSearchPage) {
							if (data.results.query.length === 0) {
								$('.algolia-instant-replaced-content').show();
								$('.algolia-instant-selector-results').hide();
							}
							else {
								$('.algolia-instant-replaced-content').hide();
								$('.algolia-instant-selector-results').show();
							}
						}
					}
				},
				/**
				 * Custom widget - Suggestions
				 * This widget renders suggestion queries which might be interesting for your customer
				 * Docs: https://community.algolia.com/instantsearch.js/documentation/#custom-widgets
				 **/
				{
					suggestions: [],
					init: function () {
						if (algoliaConfig.showSuggestionsOnNoResultsPage) {
							var $this = this;
							$.each(algoliaConfig.popularQueries.slice(0, Math.min(4, algoliaConfig.popularQueries.length)), function (i, query) {
								query = $('<div>').html(query).text(); // XSS
								$this.suggestions.push('<a href="' + algoliaConfig.baseUrl + '/catalogsearch/result/?q=' + encodeURIComponent(query) + '">' + query + '</a>');
							});
						}
					},
					render: function (data) {
						var $infosContainer = $('#algolia-right-container').find('.infos');
						
						if (data.results.hits.length === 0) {
							var content = '<div class="no-results">';
							content += '<div><b>' + algoliaConfig.translations.noProducts + ' "' + $("<div>").text(data.results.query).html() + '</b>"</div>';
							content += '<div class="popular-searches">';
							
							if (algoliaConfig.showSuggestionsOnNoResultsPage && this.suggestions.length > 0) {
								content += '<div>' + algoliaConfig.translations.popularQueries + '</div>' + this.suggestions.join(', ');
							}
							
							content += '</div>';
							content += algoliaConfig.translations.or + ' <a href="' + algoliaConfig.baseUrl + '/catalogsearch/result/?q=__empty__">' + algoliaConfig.translations.seeAll + '</a>';
							content += '</div>';
							
							$('#instant-search-results-container').html(content);
							
							$infosContainer.addClass('hidden');
						}
						else {
							$infosContainer.removeClass('hidden');
						}
					}
				}
			],
			/**
			 * Search box
			 * Docs: https://community.algolia.com/instantsearch.js/documentation/#searchbox
			 **/
			searchBox: {
				container: instant_selector,
				placeholder: algoliaConfig.translations.searchFor
			},
			/**
			 * Stats
			 * Docs: https://community.algolia.com/instantsearch.js/documentation/#stats
			 **/
			stats: {
				container: '#algolia-stats',
				templates: {
					body: $('#instant-stats-template').html()
				},
				transformData: function (data) {
					data.first = data.page * data.hitsPerPage + 1;
					data.last = Math.min(data.page * data.hitsPerPage + data.hitsPerPage, data.nbHits);
					data.seconds = data.processingTimeMS / 1000;
					data.translations = window.algoliaConfig.translations;
					
					return data;
				}
			},
			/**
			 * Sorting
			 * Docs: https://community.algolia.com/instantsearch.js/documentation/#sortbyselector
			 **/
			sortBySelector: {
				container: '#algolia-sorts',
				indices: algoliaConfig.sortingIndices,
				cssClass: 'form-control'
			},
			/**
			 * Products' hits
			 * This widget renders all products into result page
			 * Docs: https://community.algolia.com/instantsearch.js/documentation/#hits
			 **/
			hits: {
				container: '#instant-search-results-container',
				templates: {
					allItems: $('#instant-hit-template').html()
				},
				transformData: {
					allItems: function (results) {
						for (var i = 0; i < results.hits.length; i++) {
							results.hits[i] = transformHit(results.hits[i], algoliaConfig.priceKey, search.helper);
							results.hits[i].isAddToCartEnabled = algoliaConfig.instant.isAddToCartEnabled;
							
							results.hits[i].algoliaConfig = window.algoliaConfig;
						}
						
						return results;
					}
				},
				hitsPerPage: algoliaConfig.hitsPerPage
			},
			/**
			 * Widget name: Current refinements
			 * Widget displays all filters and refinements applied on query. It also let your customer to clear them one by one
			 * Docs: https://community.algolia.com/instantsearch.js/documentation/#currentrefinedvalues
			 **/
			currentRefinedValues: {
				container: '#current-refinements',
				cssClasses: {
					root: 'facet'
				},
				templates: {
					header: '<div class="name">' + algoliaConfig.translations.selectedFilters + '</div>',
					clearAll: algoliaConfig.translations.clearAll,
					item: $('#current-refinements-template').html()
				},
				attributes: attributes,
				onlyListedAttributes: true
			},
			/**
			 * Pagination
			 * Docs: https://community.algolia.com/instantsearch.js/documentation/#pagination
			 **/
			pagination: {
				container: '#instant-search-pagination-container',
				cssClass: 'algolia-pagination',
				showFirstLast: false,
				maxPages: 1000,
				labels: {
					previous: algoliaConfig.translations.previousPage,
					next: algoliaConfig.translations.nextPage
				},
				scrollTo: 'body'
			}
		};
		
		/**
		 * Here are specified custom attributes widgets which require special code to run properly
		 * Custom widgets can be added to this object like [attributeName]: function(facet, templates)
		 * Function must return instantsearch.widget object
		 * Docs: https://community.algolia.com/instantsearch.js/documentation/#widgets
		 **/
		var customAttributeFacet = {
			categories: function (facet, templates) {
				var hierarchical_levels = [];
				for (var l = 0; l < 10; l++)
					hierarchical_levels.push('categories.level' + l.toString());
				
				var hierarchicalMenuParams = {
					container: facet.wrapper.appendChild(document.createElement('div')),
					attributes: hierarchical_levels,
					separator: ' /// ',
					alwaysGetRootLevel: true,
					limit: algoliaConfig.maxValuesPerFacet,
					templates: templates,
					sortBy: ['name:asc'],
					cssClasses: {
						list: 'hierarchical',
						root: 'facet hierarchical'
					}
				};
				
				hierarchicalMenuParams.templates.item = '' +
					'<div class="ais-hierearchical-link-wrapper">' +
					'<a class="{{cssClasses.link}}" href="{{url}}">{{name}}' +
					'{{#isRefined}}<span class="cross-circle"></span>{{/isRefined}}' +
					'<span class="{{cssClasses.count}}">{{#helpers.formatNumber}}{{count}}{{/helpers.formatNumber}}</span></a>' +
					'</div>';
				
				return ['hierarchicalMenu', hierarchicalMenuParams];
			}
		};
		
		/** Add all facet widgets to allWidgetsConfiguration variable **/
		
		window.getFacetWidget = function (facet, templates) {
			
			if (facet.type === 'priceRanges') {
				delete templates.item;
				
				return ['priceRanges', {
					container: facet.wrapper.appendChild(document.createElement('div')),
					attributeName: facet.attribute,
					labels: {
						currency: algoliaConfig.currencySymbol,
						separator: algoliaConfig.translations.to,
						button: algoliaConfig.translations.go
					},
					templates: templates,
					cssClasses: {
						root: 'facet conjunctive'
					}
				}];
			}
			
			if (facet.type === 'conjunctive') {
				return ['refinementList', {
					container: facet.wrapper.appendChild(document.createElement('div')),
					attributeName: facet.attribute,
					limit: algoliaConfig.maxValuesPerFacet,
					operator: 'and',
					templates: templates,
					cssClasses: {
						root: 'facet conjunctive'
					}
				}];
			}
			
			if (facet.type === 'disjunctive') {
				return ['refinementList', {
					container: facet.wrapper.appendChild(document.createElement('div')),
					attributeName: facet.attribute,
					limit: algoliaConfig.maxValuesPerFacet,
					operator: 'or',
					templates: templates,
					cssClasses: {
						root: 'facet disjunctive'
					}
				}];
			}
			
			if (facet.type === 'slider') {
				delete templates.item;
				
				return ['rangeSlider', {
					container: facet.wrapper.appendChild(document.createElement('div')),
					attributeName: facet.attribute,
					templates: templates,
					cssClasses: {
						root: 'facet slider'
					},
					tooltips: {
						format: function (formattedValue) {
							return parseInt(formattedValue);
						}
					}
				}];
			}
		};
		
		var facets = algoliaConfig.facets,
			wrapper = document.getElementById('instant-search-facets-container');
		
		$.each(facets, function (i, facet) {
			if (facet.attribute.indexOf("price") !== -1) {
				facet.attribute = facet.attribute + algoliaConfig.priceKey;
			}
			
			facet.wrapper = wrapper;
			
			var templates = {
				header: '<div class="name">' + (facet.label ? facet.label : facet.attribute) + '</div>',
				item: $('#refinements-lists-item-template').html()
			};
			
			var widgetInfo = customAttributeFacet[facet.attribute] !== undefined ?
				customAttributeFacet[facet.attribute](facet, templates) :
				getFacetWidget(facet, templates);
			
			var widgetType = widgetInfo[0],
				widgetConfig = widgetInfo[1];
			
			if (typeof allWidgetConfiguration[widgetType] === 'undefined') {
				allWidgetConfiguration[widgetType] = [widgetConfig];
			} else {
				allWidgetConfiguration[widgetType].push(widgetConfig);
			}
		});
		
		if (algoliaConfig.analytics.enabled === true) {
			if (typeof algoliaAnalyticsPushFunction !== 'function') {
				var algoliaAnalyticsPushFunction = function (formattedParameters, state, results) {
					var trackedUrl = '/catalogsearch/result/?q=' + state.query + '&' + formattedParameters + '&numberOfHits=' + results.nbHits;
					
					// Universal Analytics
					if (typeof window.ga !== 'undefined') {
						window.ga('set', 'page', trackedUrl);
						window.ga('send', 'pageView');
					}
					
					// classic Google Analytics
					if (typeof window._gaq !== 'undefined') {
						window._gaq.push(['_trackPageview', trackedUrl]);
					}
				};
			}
			
			allWidgetConfiguration['analytics'] = {
				pushFunction: algoliaAnalyticsPushFunction,
				delay: algoliaConfig.analytics.delay,
				triggerOnUIInteraction: algoliaConfig.analytics.triggerOnUIInteraction,
				pushInitialSearch: algoliaConfig.analytics.pushInitialSearch
			};
		}
		
		if (typeof algoliaHookBeforeWidgetInitialization === 'function') {
			allWidgetConfiguration = algoliaHookBeforeWidgetInitialization(allWidgetConfiguration);
		}
		
		$.each(allWidgetConfiguration, function (widgetType, widgetConfig) {
			if (Array.isArray(widgetConfig) === true) {
				$.each (widgetConfig, function (i, widgetConfig) {
					addWidget(search, widgetType, widgetConfig);
				});
			} else {
				addWidget(search, widgetType, widgetConfig);
			}
		});
		
		var isStarted = false;
		function startInstantSearch() {
			if(isStarted === true) {
				return;
			}
			
			if (typeof algoliaHookBeforeInstantsearchStart === 'function') {
				search = algoliaHookBeforeInstantsearchStart(search);
			}
			
			search.start();
			
			if (typeof algoliaHookAfterInstantsearchStart === 'function') {
				search = algoliaHookAfterInstantsearchStart(search);
			}
			
			handleInputCrossInstant($(instant_selector));
			
			var instant_search_bar = $(instant_selector);
			if (instant_search_bar.is(":focus") === false) {
				focusInstantSearchBar(search, instant_search_bar);
			}
			
			if (algoliaConfig.autocomplete.enabled) {
				$('#search_mini_form').addClass('search-page');
			}
			
			$(document).on('click', '.ais-hierarchical-menu--link, .ais-refinement-list--checkbox', function () {
				focusInstantSearchBar(search, instant_search_bar);
			});
			
			isStarted = true;
		}
		
		/** Initialise searching **/
		startInstantSearch();
	});
	
	function addWidget(search, type, config) {
		if (type === 'custom') {
			search.addWidget(config);
			
			return;
		}
		
		search.addWidget(algoliaBundle.instantsearch.widgets[type](config));
	}
});
