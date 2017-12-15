
var skuMap = (function ($) {

    var mod = {};

    function impl(search_term, callback) {
        var req = {
            jsonrpc: '2.0',
            method: '\\COREPOS\\Fannie\\API\\webservices\\FannieAutoComplete',
            id: new Date().getTime(),
            params: { field: 'catalog', search: search_term }
        };
        console.log(search_term);
        $.ajax({
            url: '../../ws/',
            type: 'post',
            data: JSON.stringify(req),
            dataType: 'json',
            contentType: 'application/json'
        }).done(function (resp) {
            console.log(resp);
            if (resp.result) callback(resp.result);
        });
    };

    mod.autocomplete = function(elem, vendorID) {
        $(elem).autocomplete({
            source: function(request, callback) {
                impl(vendorID + ":" + request.term, callback);
            }
        });
    };

    return mod;

}(jQuery));

