
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
                var theLC = $(this).closest('tr').find('.rowLC a').html();
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
                    console.log(popOver.closest('tr').find('.preferred'+vendorID));
                    popOver.closest('tr').find('.preferred'+vendorID).prop('disabled', false);
                    popOver.closest('tr').find('.skuField'+vendorID).removeClass('danger').removeClass('success');
                });
            },
        });
    };

    mod.unlink = function(elem, vendorID) {
        $(elem).change(function (event) {
            if (this.value.trim() == '') {
                var theLC = $(this).closest('tr').find('.rowLC a').html();
                var popOver = $(this);
                $.ajax({
                    url: 'LikeCodeSKUsPage.php',
                    method: 'post',
                    data: 'id='+theLC+'&sku=&vendorID='+vendorID,
                }).done(function (resp) {
                    showBootstrapPopover(popOver, '', '');
                    popOver.closest('tr').find('.skuCost'+vendorID).html('');
                    popOver.closest('tr').find('.preferred'+vendorID).prop('disabled', true);
                    popOver.closest('tr').find('.skuField'+vendorID).removeClass('danger').removeClass('success');
                });
            }
        });
    };

    mod.setVendor = function(lc, vID) {
        $.ajax({
            url: 'LikeCodeSKUsPage.php',
            method: 'post',
            data: 'id='+lc+'&vendorID='+vID
        });
    };

    mod.setMulti = function(lc, multi) {
        $.ajax({
            url: 'LikeCodeSKUsPage.php',
            method: 'post',
            data: 'id='+lc+'&multiVendor='+(multi ? 1 : 0)
        });
    };

    mod.toggleInact = function(show) {
        if (show) {
            $('tr.inactiveRow').show();
        } else {
            $('tr.inactiveRow').hide();
        }
    };

    return mod;

}(jQuery));

