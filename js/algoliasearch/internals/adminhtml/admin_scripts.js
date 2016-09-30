algoliaAdminBundle.$(function($) {
    var fixHelper = function(e, ui) {
        ui.children().each(function() {
            $(this).width($(this).width());
        });
        return ui;
    };

    $(".grid tbody").sortable({
        containment: "parent",
        items: 'tr:not(:first-child):not(:last-child)',
        helper: fixHelper,
        start: function (event, ui) {
            $(ui.item).css('box-shadow', '2px 2px 2px #444').css('margin-left', '10px');
        }
    });

    $('.grid tr:not(:first-child):not(:last-child) td').css('cursor', 'move');

	$.getJSON('https://api.github.com/repos/algolia/algoliasearch-magento/releases/latest', function(payload) {
		var latestVersion = payload.name;

		if(compareVersion(algoliaSearchExtentionsVersion, latestVersion) > 0) {
			$('.content-header h3').after('</td><td style="font-size: 1.25em; color: rgb(58, 151, 202); padding: 3px 8px; border: 1px solid;">' +
				'<span style="font-size: 30px; position: relative; top: 5px;">⚠</span>' +
				' You are using old version of Algolia extension. ' +
				'Latest version of the extension is '+latestVersion+'. ' +
				'You can get it on ' +
				'<a href="https://www.magentocommerce.com/magento-connect/search-algolia-search.html" target="_blank">Magento Connect</a>.<br />' +
				'<small style="color: #2f2f2f; font-size: .8em; padding-left: 36px; display: inline-block">' +
				'It\'s highly recommended to update your version to avoid any unexpecting issues and to get new features.<br />' +
				'<i>If you are happy with the extension, please rate the extension on <a href="https://www.magentocommerce.com/magento-connect/search-algolia-search.html" target="_blank">Magento Connect</a>.</i>' +
				'</small></td>');
		}
	});

	function compareVersion(left, right) {
		left = sanitizeVersion(left);
		right = sanitizeVersion(right);

		for (var i = 0; i < Math.max(left.length, right.length); i++) {
			if (left[i] > right[i]) {
				return -1;
			}
			if (left[i] < right[i]) {
				return 1;
			}
		}

		return 0;
	}

	function sanitizeVersion(input) {
		return input.split('.').map(function (n) {
			return parseInt(n, 10);
		});
	}
	
	var $enableSynonymsSelect = $('#algoliasearch_synonyms_enable_synonyms');
	
	handleSynonymsElements($enableSynonymsSelect.val());
	
	$enableSynonymsSelect.change(function() {
		var val = $(this).val();
		
		handleSynonymsElements(val);
	});
	
	function handleSynonymsElements(value) {
		var $synonymsRows = $('#row_algoliasearch_synonyms_synonyms, #row_algoliasearch_synonyms_oneway_synonyms, #row_algoliasearch_synonyms_synonyms_file');
		
		if(value == 1) {
			$synonymsRows.show();
		} else {
			$synonymsRows.hide();
		}
	}
	
	var $customRankingRows = $('#row_algoliasearch_products_custom_ranking_product_attributes, #row_algoliasearch_categories_custom_ranking_category_attributes');
	initCustomRankings($customRankingRows);
	
	$customRankingRows.on('click', 'button[id^="addToEndBtn"]', function (e) {
		initCustomRankings($customRankingRows);
	});
	
	$customRankingRows.on('change', 'select[name$="[attribute]"]', function (e) {
		handleCustomRankingCustomAttributes($(this));
	});
	
	function initCustomRankings($customRankingRows) {
		$customRankingRows.find('table select[name$="[attribute]"]').each(function(e) {
			handleCustomRankingCustomAttributes($(this));
		});
	}
	
	function handleCustomRankingCustomAttributes($selectBox) {
		var selectValue = $selectBox.val(),
			$input = $selectBox.parent('td').next().find('input[type="text"]');
		
		if(selectValue !== 'custom_attribute') {
			$input.hide();
		}
		else {
			$input.show();
		}
	}
});