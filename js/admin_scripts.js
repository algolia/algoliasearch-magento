algoliaBundle.$(function($) {
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
});