document.addEventListener("DOMContentLoaded", function (e) {
	algoliaBundle.$(function ($) {
		window.isMobile = function() {
			var check = false;
			
			(function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
			
			return check;
		};
		
		window.transformHit = function (hit, price_key, helper) {
			if (Array.isArray(hit.categories)) {
				hit.categories = hit.categories.join(', ');
			}

			if (hit._highlightResult.categories_without_path && Array.isArray(hit.categories_without_path)) {
				hit.categories_without_path = $.map(hit._highlightResult.categories_without_path, function (category) {
					return category.value;
				});

				hit.categories_without_path = hit.categories_without_path.join(', ');
			}
			
			var matchedColors = [];
			
			if (helper && algoliaConfig.useAdaptiveImage === true) {
				if (hit.images_data && helper.state.facetsRefinements.color) {
					matchedColors = helper.state.disjunctiveFacetsRefinements.color.slice(0); // slice to clone
				}
				
				if (hit.images_data && helper.state.disjunctiveFacetsRefinements.color) {
					matchedColors = helper.state.disjunctiveFacetsRefinements.color.slice(0); // slice to clone
				}
			}
			
			if (Array.isArray(hit.color)) {
				var colors = [];

				$.each(hit._highlightResult.color, function (i, color) {
					if (color.matchLevel === 'none') {
						return;
					}
					
					colors.push(color.value);
					
					if (algoliaConfig.useAdaptiveImage === true) {
						var re = /<em>(.*?)<\/em>/g;
						var matchedWords = color.value.match(re).map(function (val) {
							return val.replace(/<\/?em>/g, '');
						});
						
						var matchedColor = matchedWords.join(' ');
						
						if (hit.images_data && color.fullyHighlighted && color.fullyHighlighted === true) {
							matchedColors.push(matchedColor);
						}
					}
				});

				colors = colors.join(', ');

				hit._highlightResult.color = { value: colors };
			}
			else if (hit._highlightResult.color && hit._highlightResult.color.matchLevel === 'none') {
				hit._highlightResult.color = { value: '' };
			}
			
			if (algoliaConfig.useAdaptiveImage === true) {
				$.each(matchedColors, function (i, color) {
					color = color.toLowerCase();
					
					if (hit.images_data[color]) {
						hit.image_url = hit.images_data[color];
						hit.thumbnail_url = hit.images_data[color];
						
						return false;
					}
				});
			}

			if (hit._highlightResult.color && hit._highlightResult.color.value && hit.categories_without_path) {
				if (hit.categories_without_path.indexOf('<em>') === -1 && hit._highlightResult.color.value.indexOf('<em>') !== -1) {
					hit.categories_without_path = '';
				}
			}

			if (Array.isArray(hit._highlightResult.name)) {
				hit._highlightResult.name = hit._highlightResult.name[0];
			}

			if (Array.isArray(hit.price)) {
				hit.price = hit.price[0];
			}

			if (hit['price'] !== undefined && price_key !== '.' + algoliaConfig.currencyCode + '.default' && hit['price'][algoliaConfig.currencyCode][price_key.substr(1) + '_formated'] !== hit['price'][algoliaConfig.currencyCode]['default_formated']) {
				hit['price'][algoliaConfig.currencyCode][price_key.substr(1) + '_original_formated'] = hit['price'][algoliaConfig.currencyCode]['default_formated'];
			}

			return hit;
		};

		window.getAutocompleteSource = function (section, algolia_client, $, i) {
			if (section.hitsPerPage <= 0) {
				return null;
			}

			var options = {
				hitsPerPage: section.hitsPerPage,
				analyticsTags: 'autocomplete'
			};

			var source;

			if (section.name === "products") {
				options.facets = ['categories.level0'];
				options.numericFilters = 'visibility_search=1';

				source = {
					source: $.fn.autocomplete.sources.hits(algolia_client.initIndex(algoliaConfig.indexName + "_" + section.name), options),
					name: section.name,
					templates: {
						empty: function (query) {
							var template = '<div class="aa-no-results-products">' +
								'<div class="title">' + algoliaConfig.translations.noProducts + ' "' + $("<div>").text(query.query).html() + '"</div>';

							var suggestions = [];

							if (algoliaConfig.showSuggestionsOnNoResultsPage && algoliaConfig.popularQueries.length > 0) {
								$.each(algoliaConfig.popularQueries.slice(0, Math.min(3, algoliaConfig.popularQueries.length)), function (i, query) {
									query = $('<div>').html(query).text(); // Avoid xss
									suggestions.push('<a href="' + algoliaConfig.baseUrl + '/catalogsearch/result/?q=' + encodeURIComponent(query) + '">' + query + '</a>');
								});

								template += '<div class="suggestions"><div>' + algoliaConfig.translations.popularQueries + '</div>';
								template += '<div>' + suggestions.join(', ') + '</div>';
								template += '</div>';
							}

							template += '<div class="see-all">' + (suggestions.length > 0 ? algoliaConfig.translations.or + ' ' : '') + '<a href="' + algoliaConfig.baseUrl + '/catalogsearch/result/?q=__empty__">' + algoliaConfig.translations.seeAll + '</a></div>' +
								'</div>';

							return template;
						},
						suggestion: function (hit) {
							hit = transformHit(hit, algoliaConfig.priceKey)
							hit.displayKey = hit.displayKey || hit.name;

							return algoliaConfig.autocomplete.templates[section.name].render(hit);
						}
					}
				};
			}
			else if (section.name === "categories" || section.name === "pages") {
				if (section.name === "categories" && algoliaConfig.showCatsNotIncludedInNavigation == false) {
					options.numericFilters = 'include_in_menu=1';
				}

				source = {
					source: $.fn.autocomplete.sources.hits(algolia_client.initIndex(algoliaConfig.indexName + "_" + section.name), options),
					name: i,
					templates: {
						empty: '<div class="aa-no-results">' + algoliaConfig.translations.noResults + '</div>',
						suggestion: function (hit) {
							if (section.name === 'categories') {
								hit.displayKey = hit.path;
							}

							if (hit._snippetResult && hit._snippetResult.content && hit._snippetResult.content.value.length > 0) {
								hit.content = hit._snippetResult.content.value;

								if (hit.content.charAt(0).toUpperCase() !== hit.content.charAt(0)) {
									hit.content = '&#8230; ' + hit.content;
								}

								if ($.inArray(hit.content.charAt(hit.content.length - 1), ['.', '!', '?'])) {
									hit.content = hit.content + ' &#8230;';
								}

								if (hit.content.indexOf('<em>') === -1) {
									hit.content = '';
								}
							}

							hit.displayKey = hit.displayKey || hit.name;

							return algoliaConfig.autocomplete.templates[section.name].render(hit);
						}
					}
				};
			}
			else if (section.name === "suggestions") {
				/** Popular queries/suggestions **/
				var suggestions_index = algolia_client.initIndex(algoliaConfig.indexName + "_suggestions"),
					products_index = algolia_client.initIndex(algoliaConfig.indexName + "_products"),
					suggestionsSource;
				
				if (algoliaConfig.autocomplete.displaySuggestionsCategories == true) {
					suggestionsSource = $.fn.autocomplete.sources.popularIn(suggestions_index, {
						hitsPerPage: section.hitsPerPage
						}, {
							source: 'query',
							index: products_index,
							facets: ['categories.level0'],
							hitsPerPage: 0,
							typoTolerance: false,
							maxValuesPerFacet: 1,
							analytics: false
						}, {
							includeAll: true,
							allTitle: algoliaConfig.translations.allDepartments
						});
				} else {
					suggestionsSource = $.fn.autocomplete.sources.hits(suggestions_index, {
						hitsPerPage: section.hitsPerPage
					});
				}

				source = {
					source: suggestionsSource,
					displayKey: 'query',
					name: section.name,
					templates: {
						suggestion: function (hit) {
							if (hit.facet) {
								hit.category = hit.facet.value;
							}

							if (hit.facet && hit.facet.value !== 'All departments') {
								hit.url = algoliaConfig.baseUrl + '/catalogsearch/result/?q=' + hit.query + '#q=' + hit.query + '&hFR[categories.level0][0]=' + encodeURIComponent(hit.category) + '&idx=' + algoliaConfig.indexName + '_products';
							} else {
								hit.url = algoliaConfig.baseUrl + '/catalogsearch/result/?q=' + hit.query;
							}

							return algoliaConfig.autocomplete.templates.suggestions.render(hit);
						}
					}
				};
			} else {
				/** Additional sections **/
				var index = algolia_client.initIndex(algoliaConfig.indexName + "_section_" + section.name);

				source = {
					source: $.fn.autocomplete.sources.hits(index, {
						hitsPerPage: section.hitsPerPage,
						analyticsTags: 'autocomplete'
					}),
					displayKey: 'value',
					name: i,
					templates: {
						suggestion: function (hit) {
							hit.url = algoliaConfig.baseUrl + '/catalogsearch/result/?q=' + encodeURIComponent(hit.value) + '&refinement_key=' + section.name;
							return algoliaConfig.autocomplete.templates.additionnalSection.render(hit);
						}
					}
				};
			}

			if (section.name === 'products') {
				source.templates.footer = function (query, content) {
					var keys = [];
					for (var key in content.facets['categories.level0']) {
						var url = algoliaConfig.baseUrl + '/catalogsearch/result/?q=' + encodeURIComponent(query.query) + '#q=' + encodeURIComponent(query.query) + '&hFR[categories.level0][0]=' + encodeURIComponent(key) + '&idx=' + algoliaConfig.indexName + '_products';
						keys.push({
							key: key,
							value: content.facets['categories.level0'][key],
							url: url
						});
					}

					keys.sort(function (a, b) {
						return b.value - a.value;
					});

					var ors = '';

					if (keys.length > 0) {
						ors += '<span><a href="' + keys[0].url + '">' + keys[0].key + '</a></span>';
					}

					if (keys.length > 1) {
						ors += ', <span><a href="' + keys[1].url + '">' + keys[1].key + '</a></span>';
					}

					var allUrl = algoliaConfig.baseUrl + '/catalogsearch/result/?q=' + encodeURIComponent(query.query);
					var returnFooter = '<div id="autocomplete-products-footer">' + algoliaConfig.translations.seeIn + ' <span><a href="' + allUrl + '">' + algoliaConfig.translations.allDepartments + '</a></span> (' + content.nbHits + ')';

					if (ors && algoliaConfig.instant.enabled) {
						returnFooter += ' ' + algoliaConfig.translations.orIn + ' ' + ors;
					}

					returnFooter += '</div>';

					return returnFooter;
				}
			}

			if (section.name !== 'suggestions' && section.name !== 'products') {
				source.templates.header = '<div class="category">' + (section.label ? section.label : section.name) + '</div>';
			}

			return source;
		};

		window.fixAutocompleteCssHeight = function () {
			if ($(document).width() > 768) {
				var $otherSections = $('.other-sections'),
					$dataSetProducts = $('.aa-dataset-products');

				$otherSections.css('min-height', '0');
				$dataSetProducts.css('min-height', '0');

				var height = Math.max($otherSections.outerHeight(), $dataSetProducts.outerHeight());
				$dataSetProducts.css('min-height', height);
			}
		};

		window.fixAutocompleteCssSticky = function (menu) {
			var dropdown_menu = $('#algolia-autocomplete-container .aa-dropdown-menu');
			var autocomplete_container = $('#algolia-autocomplete-container');
			autocomplete_container.removeClass('reverse');

			/** Reset computation **/
			dropdown_menu.css('top', '0px');

			/** Stick menu vertically to the input **/
			var targetOffset = Math.round(menu.offset().top + menu.outerHeight());
			var currentOffset = Math.round(autocomplete_container.offset().top);

			dropdown_menu.css('top', (targetOffset - currentOffset) + 'px');

			if (menu.offset().left + menu.outerWidth() / 2 > $(document).width() / 2) {
				/** Stick menu horizontally align on right to the input **/
				dropdown_menu.css('right', '0px');
				dropdown_menu.css('left', 'auto');

				targetOffset = Math.round(menu.offset().left + menu.outerWidth());
				currentOffset = Math.round(autocomplete_container.offset().left + autocomplete_container.outerWidth());

				dropdown_menu.css('right', (currentOffset - targetOffset) + 'px');
			}
			else {
				/** Stick menu horizontally align on left to the input **/
				dropdown_menu.css('left', 'auto');
				dropdown_menu.css('right', '0px');
				autocomplete_container.addClass('reverse');

				targetOffset = Math.round(menu.offset().left);
				currentOffset = Math.round(autocomplete_container.offset().left);

				dropdown_menu.css('left', (targetOffset - currentOffset) + 'px');
			}
		};

		$(window.algoliaConfig.autocomplete.selector).each(function () {
			$(this).closest('form').submit(function (e) {
				var query = $(this).find(algoliaConfig.autocomplete.selector).val();

				if (algoliaConfig.instant.enabled && query == '')
					query = '__empty__';

				window.location = $(this).attr('action') + '?q=' + query;

				return false;
			});
		});

		function handleInputCrossAutocomplete(input) {
			if (input.val().length > 0) {
				input.closest('#algolia-searchbox').find('.clear-query-autocomplete').show();
				input.closest('#algolia-searchbox').find('.magnifying-glass').hide();
			}
			else {
				input.closest('#algolia-searchbox').find('.clear-query-autocomplete').hide();
				input.closest('#algolia-searchbox').find('.magnifying-glass').show();
			}
		}

		window.focusInstantSearchBar = function (search, instant_search_bar) {
			if ($(window).width() > 992) {
				instant_search_bar.focusWithoutScrolling();
				if (algoliaConfig.autofocus === false) {
					instant_search_bar.focus().val('');
				}
			}
			instant_search_bar.val(search.helper.state.query);
		};

		window.handleInputCrossInstant = function (input) {
			if (input.val().length > 0) {
				input.closest('#instant-search-box').find('.clear-query-instant').show();
			}
			else {
				input.closest('#instant-search-box').find('.clear-query-instant').hide();
			}
		};

		var instant_selector = !algoliaConfig.autocomplete.enabled ? ".algolia-search-input" : "#instant-search-bar";

		$(document).on('input', algoliaConfig.autocomplete.selector, function () {
			handleInputCrossAutocomplete($(this));
		});

		$(document).on('input', instant_selector, function () {
			handleInputCrossInstant($(this));
		});

		$(document).on('click', '.clear-query-instant', function () {
			var input = $(this).closest('#instant-search-box').find('input');
			input.val('');
			input.get(0).dispatchEvent(new Event('input'));
			handleInputCrossInstant(input);
		});

		$(document).on('click', '.clear-query-autocomplete', function () {
			var input = $(this).closest('#algolia-searchbox').find('input');
			input.val('');

			if (algoliaConfig.autocomplete.enabled != algoliaConfig.instant.enabled) {
				input.get(0).dispatchEvent(new Event('input'));
			}

			handleInputCrossAutocomplete(input);
		});


		/** Handle small screen **/
		$('body').on('click', '#refine-toggle', function () {
			$('#instant-search-facets-container').toggleClass('hidden-sm').toggleClass('hidden-xs');
			if ($(this).html()[0] === '+')
				$(this).html('- ' + algoliaConfig.translations.refine);
			else
				$(this).html('+ ' + algoliaConfig.translations.refine);
		});

		$.fn.focusWithoutScrolling = function () {
			var x = window.scrollX, y = window.scrollY;
			this.focus();
			window.scrollTo(x, y);
		};

	});
});