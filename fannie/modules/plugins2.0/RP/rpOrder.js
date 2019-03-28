var rpOrder = (function ($) {
    var mod = {};
    var searchVendor = 0;
    var retainElem = false;

    mod.setSearchVendor = function(v) {
        searchVendor = v;
    }

    mod.initAutoCompletes = function() {
        $('input#newItem').autocomplete({
            source: vendorAutoComplete,
            select: function (ev, ui) {
                ev.preventDefault();
                var data = JSON.parse(ui.item.value);
                $('input#newItem').val(data.item);
                $('select#newVendor').val(data.vendorID);
                $('input#newUPC').val(data.upc);
                $('input#newSKU').val(data.sku);
                $('input#newCase').val(data.caseSize);
            },
            minLength: 3
        });

        $('input#newLC').autocomplete({
            source: lcAutoComplete,
            minLength: 3
        });
    };

    function ajaxAutoComplete(dstr, callback) {
        $.ajax({
            type: 'get',
            data: dstr,
            dataType: 'json'
        }).fail(function () {
            callback([]);
        }).done(function (resp) {
            callback(resp)
        });
    };

    function vendorAutoComplete(req, callback) {
        var dstr = 'searchVendor=' + encodeURIComponent(req.term);
        dstr += '&vendorID=' + searchVendor;
        ajaxAutoComplete(dstr, callback);
    };

    function lcAutoComplete(req, callback) {
        var dstr = 'searchLC=' + encodeURIComponent(req.term);
        ajaxAutoComplete(dstr, callback);
    }

    mod.updateDays = function() {
        var week = $('#projSales').html().replace(',', '');
        var selectedDays = 0;
        $('.daycheck:checked').each(function () {
            var pct = $(this).val().replace('%', '') / 100;
            selectedDays += pct * week;
        });
        $('#selectedSales').html(Math.round(selectedDays * 100) / 100);

        var retail = 0;
        var numDays = $('.daycheck:checked').length;
        $('input.basePar').each(function () {
            var price = $(this).prev('input.price').val();
            retail += (price * 1) * ($(this).val() * 1) * numDays;
        });
        $('#guessRetail').html(Math.round(retail * 100) / 100);

        var scaler = selectedDays / retail;
        var shownAs = scaler > 1 ? scaler - 1 : (-1 * (1 - scaler));
        shownAs = Math.round(shownAs * 100 * 100) / 100 + '%';
        if (isNaN(scaler)) {
            shownAs = '0';
        }
        $('#adjDiff').html(shownAs);

        $('input.basePar').each(function () {
            var adj = $(this).val() * 1 * scaler * numDays;
            if (isNaN(scaler)) {
                adj = $(this).val();
            }
            var caseSize = $(this).closest('tr').find('td.caseSize').html();
            adj = adj / caseSize;
            var adj = Math.round(adj * 100) / 100;
            $(this).next('td.parCell').html(adj);

            mod.reCalcRow($(this).closest('tr'));
        });
    };

    mod.reCalcRow = function(elem) {
        var caseSize = $(elem).find('td.caseSize').html();
        var adj = $(elem).find('td.parCell').html();
        var onHand = $(elem).find('input.onHand').val();
        if (!retainElem) {
            retainElem = $('#retention');
        }
        onHand = onHand * (retainElem.val() / 100);

        var start = (adj * 1 * caseSize) - (onHand * 1 * caseSize);
        var cases = 0;
        while (start > (0.25 * caseSize)) {
            cases += 1;
            start -= caseSize;
        }
        $(elem).find('input.orderAmt').val(cases);
    };

    mod.inc = function(btn, amt) {
        var elem = $(btn).parent().find('input.orderAmt');
        var next = ($(elem).val() * 1) + amt;
        if (next < 0) {
            next = 0;
        }
        $(elem).val(next);
    };

    function nextRow(elem) {
        var myRow = $(elem).closest('tr');
        var next = $(myRow).next('tr');
        if (next.length > 0) {
            return next.get(0);
        }
        var myTable = $(elem).closest('table');
        var nextTable = $(myTable).next().next('table');
        next = $(nextTable).find('td').first().parent();
        if (next.length > 0) {
            return next.get(0);
        }

        return false;
    };

    function prevRow(elem) {
        var myRow = $(elem).closest('tr');
        var prev = $(myRow).prev('tr');
        if ($(prev).find('td').length > 0) {
            return prev.get(0);
        }
        var myTable = $(elem).closest('table');
        var prevTable = $(myTable).prev().prev('table');
        prev = $(prevTable).find('td').last().parent();
        if (prev.length > 0) {
            return prev.get(0);
        }

        return false;
    }

    mod.onHandKey = function(ev) {
        if (ev.which == 13 || ev.which == 40) {
            ev.preventDefault();
            var next = nextRow(ev.target);
            if (next) {
                $(next).find('input.onHand').focus();
            }
        } else if (ev.which == 38) {
            ev.preventDefault();
            var prev = prevRow(ev.target);
            if (prev) {
                $(prev).find('input.onHand').focus();
            }
        } else if (ev.which == 39) {
            ev.preventDefault();
            $(ev.target).closest('tr').find('input.orderAmt').focus();
        }
    };

    mod.orderKey = function(ev) {
        if (ev.which == 13 || ev.which == 40) {
            ev.preventDefault();
            var next = nextRow(ev.target);
            if (next) {
                $(next).find('input.orderAmt').focus();
            }
        } else if (ev.which == 38) {
            ev.preventDefault();
            var prev = prevRow(ev.target);
            if (prev) {
                $(prev).find('input.orderAmt').focus();
            }
        } else if (ev.which == 37) {
            ev.preventDefault();
            $(ev.target).closest('tr').find('input.onHand').focus();
        }
    };

    mod.placeOrder = function(elem) {
        var id = encodeURIComponent($(elem).val());
        var qty = $(elem).closest('tr').find('input.orderAmt').val();
        if ($(elem).prop('checked') && qty) {
            $.ajax({
                'type': 'post',
                'data': 'id=' + id + '&qty=' + qty,
                'dataType': 'json'
            }).done(function (resp) {
                $(elem).closest('td').addClass('info');
                if ($('#openOrders').find('#link'+resp.orderID).length == 0) {
                    var newlink = '<li id="link' + resp.orderID + '">';
                    newlink += '<a href="../../../purchasing/ViewPurchaseOrders.php?id=' + resp.orderID + '">';
                    newlink += resp.name + '</a></li>';
                    $('#openOrders').append(newlink);
                }
                var orderIDs = "";
                $('#openOrders li').each(function () {
                    orderIDs += $(this).attr('id').replace('link', '') + ",";
                });
                if (orderIDs) {
                    var printLink = '<a href="RpPrintOrders.php?id=' + orderIDs + '">Print these</a>';
                    $('#printLink').html(printLink);
                }
            });
        } else {
            $.ajax({
                'type': 'post',
                'data': 'id=' + id + '&_method=delete',
                'dataType': 'json'
            }).done(function (resp) {
                $(elem).closest('td').removeClass('info');
            });
        }
    };

    mod.orderAll = function() {
        var buttons = $('button.orderAll');
        var meters = $('.progress');
        buttons.prop('disabled', true);
        meters.show();

        $('input.orderPri').each(function () {
            var qty = $(this).closest('tr').find('input.orderAmt').val();
            if (qty > 0 && !$(this).prop('checked')) {
                $(this).prop('checked', true);
                mod.placeOrder(this);
            }
        });

        meters.hide();
        buttons.prop('disabled', false);
    };

    return mod;
})(jQuery);
