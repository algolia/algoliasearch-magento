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
	
	// Queue info
	
	var url = window.location.href,
		position = url.indexOf('/system_config/edit/'),
		baseUrl = url.substring(0, position);
	
	$.getJSON(baseUrl + '/queue', function(queueInfo) {
		var message = '<span style="font-size: 25px; position: relative; top: 5px;">⚠</span> ' +
			'<strong style="font-size: 1.15em;"><a href="https://community.algolia.com/magento/doc/m1/indexing/?utm_source=magento&utm_medium=extension&utm_campaign=magento_1&utm_term=shop-owner&utm_content=doc-link#general-information" target="_blank">Indexing queue</a> is not enabled</strong><br>' +
			'It\'s highly recommended to enable it, especially if you are on production environment. ' +
			'You can learn how to enable the index queue in the documentation: <a href="https://community.algolia.com/magento/doc/m1/indexing/?utm_source=magento&utm_medium=extension&utm_campaign=magento_1&utm_term=shop-owner&utm_content=doc-link#general-information" target="_blank">Indexing queue</a>';
		
		if (queueInfo.isEnabled === true) {
			message = '<strong style="font-size: 1.15em;"><a href="https://community.algolia.com/magento/doc/m1/indexing/?utm_source=magento&utm_medium=extension&utm_campaign=magento_1&utm_term=shop-owner&utm_content=doc-link#general-information" target="_blank">Indexing queue</a> information</strong><br>' +
				'Number of queued jobs: <strong>' + queueInfo.currentSize + '</strong>, ' +
				'all queued jobs will be processed in appr. <strong>' + queueInfo.eta + '</strong> ' +
				'<small style="color: #2f2f2f; font-size: .8em;">(assuming your queue runner runs every 5 minutes)</small><br>' +
				'If you want to clear the queue, hit the button: <button class="algolia_clear_queue">Clear the queue</button><br />' +
				'<small style="color: #2f2f2f; font-size: .9em; display: inline-block;">' +
				'More information about how the indexing queue works you can find in the documentation: <a href="https://community.algolia.com/magento/doc/m1/indexing/?utm_source=magento&utm_medium=extension&utm_campaign=magento_1&utm_term=shop-owner&utm_content=doc-link#general-information" target="_blank">Indexing queue</a>' +
				'</small>';
		}
		
		$('.entry-edit').before('<div style="font-size: 1.1em; line-height: 25px; padding: 3px 8px; border: 1px solid; margin-bottom: 10px; color: #333;">' + message + '</div>');
	});
	
	$(document).on('click', '.algolia_clear_queue', function(e) {
		e.preventDefault();
		
		if (!confirm('Are you sure you want to clear the queue? This operation cannot be reverted.')) {
			return false;
		}
		
		$(this).replaceWith('<span class="algolia_clearing" style="font-weight: bold;">Clearing the queue <span id="wait"></span></span>');
		var dots = window.setInterval( function() {
			var $wait = $('#wait'),
				waitText = $wait.text();
			
			if (waitText.length > 2) {
				$wait.text('');
			}
			else {
				$wait.text(waitText + '.');
			}
		}, 200);
		
		$.getJSON(baseUrl + '/queue/truncate', function(payload) {
			window.clearInterval(dots);
			
			if (payload.status === 'ok') {
				$('.algolia_clearing').replaceWith('<span class="algolia_cleared" style="font-weight: bold; color: darkgreen;">The queue has been cleared</span>');
			}
			else {
				$('.algolia_clearing').replaceWith('<span class="algolia_clear_errored" style="font-weight: bold; color: darkred;">There was an error during clearing the queue - <i>'+payload.message+'</i></span>');
			}
		});
		
		return false;
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