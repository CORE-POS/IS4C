var recentSales = (function($) {
    var mod = {};

    var changeUrl = function(field, val) {
        var url = '?' + field + '=' + val;
        url += '&store=' + $('select[name=store]').val();
        window.location = url;
    };

    mod.bindSelect = function(field, val) {
        $('select[name=store]').change(function() {
            changeUrl(field, val);
        });
    };

    return mod;

}(jQuery));
