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

	var currentVersion = '1.2.5';
	$.getJSON('https://api.github.com/repos/algolia/algoliasearch-magento/releases/latest', function(payload) {
		var latestVersion = payload.name;

		if(compareVersion(currentVersion, latestVersion) > 0) {
			$('.content-header h3').after('</td><td style="font-size: 1.25em; color: #D83900; padding: 3px 8px; border: 1px solid;">' +
				'<span style="font-size: 30px; position: relative; top: 5px;">⚠</span>' +
				' You are using old version of Algolia extension. ' +
				'Latest version of the extension is '+latestVersion+'. ' +
				'You can get it on ' +
				'<a href="https://www.magentocommerce.com/magento-connect/search-algolia-search.html" target="_blank">Magento Connect</a>.<br />' +
				'<small style="color: #2f2f2f; font-size: .8em; padding-left: 36px;">' +
				'It\'s highly recommended to update your version to avoid any unexpecting issues and to get new features.' +
				'</small></td>');
		}
	});

	function sanitize(input) {
		return input.split('.').map(function (n) {
			return parseInt(n, 10);
		});
	}

	function compareVersion(left, right) {
		left = sanitize(left);
		right = sanitize(right);

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
});