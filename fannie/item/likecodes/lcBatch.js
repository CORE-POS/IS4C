
var lcBatch = (function ($) {

    var mod = {};

    mod.toggleInact = function(show) {
        if (show) {
            $('tr.inactiveRow').show();
        } else {
            $('tr.inactiveRow').hide();
        }
    };

    function refilter() {
        var dstr = $('.filter-field').serialize();
        location = 'LikeCodeBatchPage.php?' + dstr;
    };
    mod.enableFilters = function() {
        $('.filter-field').change(refilter);
    };

    mod.recalculateSheet = function() {
        var numerator = 0;
        var denominator = 0;
        $('tr.price-row').each(function () {
            var price = $(this).find('input.price').val();
            if (price == 0) {
                return;
            }
            var cost = $(this).find('td.cost').html();
            if (cost == 0) {
                return;
            }
            var weight = $(this).find('input.weight').val();
            var weightedMargin = ((price - cost) / price) * weight;
            numerator += weightedMargin;
            denominator +=  1 * weight;
        });
        var result = numerator / denominator;
        result = Math.round(1000 * result) / 1000;
        $('#mainMargin').html(result);
    };

    function addPriceChange(lc, price, cost) {
        $.ajax({
            'url': 'LikeCodeBatchPage.php',
            'method': 'post',
            'data': 'id=' + lc + '&price=' + price + '&cost=' + cost,
            'dataType': 'json'
        }).done(function (resp) {
            $('#priceBatch').html('<a href="../../batches/newbatch/EditBatchPage.php?id=' + resp.id + '">Price Batch</a>');
        });
    };

    function removePriceChange(lc) {
        $.ajax({
            'url': 'LikeCodeBatchPage.php',
            'method': 'post',
            'data': '_method=delete&id=' + lc
        }).done (function(resp) {
        });
    };

    function addSale(lc, price, cost) {
        $.ajax({
            'url': 'LikeCodeBatchPage.php',
            'method': 'post',
            'data': 'id=' + lc + '&sale=' + price + '&cost=' + cost,
            'dataType': 'json'
        }).done(function (resp) {
            $('#saleBatch').html('<a href="../../batches/newbatch/EditBatchPage.php?id=' + resp.id + '">Sale Batch</a>');
        });
    };

    function removeSaleChange(lc) {
        $.ajax({
            'url': 'LikeCodeBatchPage.php',
            'method': 'post',
            'data': '_method=delete&id=' + lc + '&sale=1'
        }).done (function(resp) {
        });
    };

    mod.batchify = function(elem) {
        var row = $(elem).closest('tr');
        var price = row.find('input.price').val();
        var orig = row.find('input.orig-price').val();
        var cost = row.find('td.cost').html();
        var lc = row.find('td.rowLC a').html();
        var changeType = row.find('select.changeType').val();
        switch (changeType) {
            case 'Change':
                removeSaleChange(lc);
                if (orig != price) {
                    addPriceChange(lc, price, cost);
                } else {
                    removePriceChange(lc);
                    row.find('input.price').closest('td').removeClass('warning');
                }
                break;
            case 'Stop Sale':
                removeSaleChange(lc);
                addPriceChange(lc, price, cost);
                row.find('input.price').closest('td').addClass('warning');
                break;
            case 'Start Sale':
                removePriceChange(lc);
                addSale(lc, price, cost);
                break;
        }
    };

    mod.recalculateMargin = function(elem) {
        var price = elem.value;
        var cost = $(elem).closest('tr').find('td.cost').html();
        var margin = (price - cost) / price;
        margin = Math.round(margin * 100) / 100;
        $(elem).closest('tr').find('td.margin').html(margin);
        var orig = $(elem).closest('tr').find('input.orig-price').val();
        var lc = $(elem).closest('tr').find('td.rowLC a').html();
        if (orig != price) {
            $(elem).closest('td').addClass('warning');
        } else {
            $(elem).closest('td').removeClass('warning');
        }
        mod.recalculateSheet();
        mod.batchify(elem);
    }

    return mod;

}(jQuery));

