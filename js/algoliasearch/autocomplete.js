document.addEventListener("DOMContentLoaded", function(event) {
	algoliaBundle.$(function ($) {
		
		/** We have nothing to do here if autocomplete is disabled **/
		if (!algoliaConfig.autocomplete.enabled) {
			return;
		}
		
		/**
		 * Set autocomplete templates
		 * For templating is used Hogan library
		 * Docs: http://twitter.github.io/hogan.js/
		 **/
		algoliaConfig.autocomplete.templates = {
			suggestions: algoliaBundle.Hogan.compile($('#autocomplete_suggestions_template').html()),
			products: algoliaBundle.Hogan.compile($('#autocomplete_products_template').html()),
			categories: algoliaBundle.Hogan.compile($('#autocomplete_categories_template').html()),
			pages: algoliaBundle.Hogan.compile($('#autocomplete_pages_template').html()),
			additionnalSection: algoliaBundle.Hogan.compile($('#autocomplete_extra_template').html())
		};
		
		/**
		 * Initialise Algolia client
		 * Docs: https://www.algolia.com/doc/javascript
		 **/
		var algolia_client = algoliaBundle.algoliasearch(algoliaConfig.applicationId, algoliaConfig.autocomplete.apiKey);
		algolia_client.addAlgoliaAgent('Magento integration (' + algoliaConfig.extensionVersion + ')');
		
		/** Add autocomplete menu sections **/
		if (algoliaConfig.autocomplete.nbOfProductsSuggestions > 0) {
			algoliaConfig.autocomplete.sections.unshift({ hitsPerPage: algoliaConfig.autocomplete.nbOfProductsSuggestions, label: algoliaConfig.translations.products, name: "products"});
		}
		
		if (algoliaConfig.autocomplete.nbOfCategoriesSuggestions > 0) {
			algoliaConfig.autocomplete.sections.unshift({ hitsPerPage: algoliaConfig.autocomplete.nbOfCategoriesSuggestions, label: algoliaConfig.translations.categories, name: "categories"});
		}
		
		if (algoliaConfig.autocomplete.nbOfQueriesSuggestions > 0) {
			algoliaConfig.autocomplete.sections.unshift({ hitsPerPage: algoliaConfig.autocomplete.nbOfQueriesSuggestions, label: '', name: "suggestions"});
		}
		
		/** Setup autocomplete data sources **/
		var sources = [],
			i = 0;
		$.each(algoliaConfig.autocomplete.sections, function (name, section) {
			var source = getAutocompleteSource(section, algolia_client, $, i);
			
			if (source) {
				sources.push(source);
			}
			
			/** Those sections have already specific placeholder, so do not use the default aa-dataset-{i} class **/
			if (section.name !== 'suggestions' && section.name !== 'products') {
				i++;
			}
		});
		
		/**
		 * Setup the autocomplete search input
		 * For autocomplete feature is used Algolia's autocomplete.js library
		 * Docs: https://github.com/algolia/autocomplete.js
		 **/
		$(algoliaConfig.autocomplete.selector).each(function (i) {
			var menu = $(this);
			var options = {
				hint: false,
				templates: {
					dropdownMenu: '#menu-template'
				},
				dropdownMenuContainer: "#algolia-autocomplete-container",
				debug: algoliaConfig.autocomplete.isDebugEnabled
			};
			
			if (isMobile() === true) {
				// Set debug to true, to be able to remove keyboard and be able to scroll in autocomplete menu
				options.debug = true;
			}
			
			if (algoliaConfig.removeBranding === false) {
				options.templates.footer = '<div class="footer_algolia"><a href="https://www.algolia.com/?utm_source=magento&utm_medium=link&utm_campaign=magento_autocompletion_menu" title="Search by Algolia" target="_blank"><img src="' +algoliaConfig.urls.logo + '" alt="Search by Algolia" /></a></div>';
			}
			
			if (typeof algoliaHookBeforeAutocompleteStart === 'function') {
				var hookResult = algoliaHookBeforeAutocompleteStart(sources, options, algolia_client);
				
				sources = hookResult.shift();
				options = hookResult.shift();
			}
			
			/** Bind autocomplete feature to the input */
			$(this)
				.autocomplete(options, sources)
				.parent()
				.attr('id', 'algolia-autocomplete-tt')
				.on('autocomplete:updated', function (e) {
					fixAutocompleteCssSticky(menu);
				})
				.on('autocomplete:updated', function (e) {
					fixAutocompleteCssHeight(menu);
				}).on('autocomplete:selected', function (e, suggestion, dataset) {
					location.assign(suggestion.url);
				});
			
			$(window).resize(function () {
				fixAutocompleteCssSticky(menu);
			});
		});
	});
});
