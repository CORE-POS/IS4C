
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

    mod.recalculateMargin = function(elem) {
        var price = elem.value;
        var cost = $(elem).closest('tr').find('td.cost').html();
        var margin = (price - cost) / price;
        margin = Math.round(margin * 100) / 100;
        $(elem).closest('tr').find('td.margin').html(margin);
        var orig = $(elem).closest('tr').find('input.orig-price').val();
        if (orig != price) {
            $(elem).closest('td').addClass('warning');
        } else {
            $(elem).closest('td').removeClass('warning');
        }
        mod.recalculateSheet();
    }

    return mod;

}(jQuery));

