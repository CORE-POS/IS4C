var singleSubmit = (function($) {
    var mod = {};

    // prevent submit event from occurring more than once
    var norepeat = function(e) {
        if ($(this).data('submitted') === true) {
            e.preventDefault();
            return false;
        } else {
            $(this).data('submitted', true);
            return true;
        }
    };

    // apply restriction to element specified by selector
    mod.restrict = function(selector) {
        $(selector).submit(norepeat);
    };

    return mod;

})(jQuery);
