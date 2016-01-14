
    $(document).ready(function(){
        var scroll_start = 0;
        var startchange = $('.navbar-brand.auto-hide');
        if (startchange.length) {
            $(document).scroll(function() {
                scroll_start = $(this).scrollTop();
                if(scroll_start > 300) {
                    startchange.addClass("visible");
                } else {
                    startchange.removeClass("visible");
                }
            });
        }
    });