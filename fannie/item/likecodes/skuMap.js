
var skuMap = (function ($) {

    var mod = {};

    function impl(search_term, callback) {
        var req = {
            jsonrpc: '2.0',
            method: '\\COREPOS\\Fannie\\API\\webservices\\FannieAutoComplete',
            id: new Date().getTime(),
            params: { field: 'catalog', search: search_term }
        };
        $.ajax({
            url: '../../ws/',
            type: 'post',
            data: JSON.stringify(req),
            dataType: 'json',
            contentType: 'application/json'
        }).done(function (resp) {
            if (resp.result) {
                callback(resp.result);
            }
        });
    };

    mod.autocomplete = function(elem, vendorID) {
        $(elem).autocomplete({
            source: function(request, callback) {
                impl(vendorID + ":" + request.term, callback);
            },
            select: function(event, ui) {
                var theLC = $(this).closest('tr').find('.rowLC').html();
                var theSKU = ui.item.label;
                var popOver = $(this);
                $.ajax({
                    url: 'LikeCodeSKUsPage.php',
                    method: 'post',
                    data: 'id='+theLC+'&sku='+theSKU+'&vendorID='+vendorID,
                }).done(function (resp) {
                    showBootstrapPopover(popOver, '', '');
                    var tmp = theSKU.split('$');
                    var cost = tmp[tmp.length - 1];
                    popOver.closest('tr').find('.skuCost'+vendorID).html(cost);
                });
            },
        });
    };

    mod.unlink = function(elem, vendorID) {
        $(elem).change(function (event) {
            if (this.value.trim() == '') {
                var theLC = $(this).closest('tr').find('.rowLC').html();
                var popOver = $(this);
                $.ajax({
                    url: 'LikeCodeSKUsPage.php',
                    method: 'post',
                    data: 'id='+theLC+'&sku=&vendorID='+vendorID,
                }).done(function (resp) {
                    showBootstrapPopover(popOver, '', '');
                    popOver.closest('tr').find('.skuCost'+vendorID).html('');
                });
            }
        });
    };

    return mod;

}(jQuery));

