var rpOrder = (function ($) {

    var mod = {};
    var state = {
        'retention': 60,
        'days': [false, false, false, false, false, false, false],
        'onHand': {},
        'orderAmt': {},
        'directAmt': {}
    };
    var searchVendor = 0;
    var retainElem = false;
    var minDate = false;
    var maxDate = false;

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

    function updateState() {
        state['retention'] = $('#retention').val();
        state['days'] = [];
        $('.daycheck').each(function () {
            state['days'].push($(this).prop('checked') ? true : false);
        });
    };

    mod.save = function() {
        $.ajax({
            type: 'post',
            data: 'json=' + encodeURIComponent(JSON.stringify(state))
        }).done(function() {
            var now = new Date();
            $('.last-save').html(now.toLocaleTimeString());
        });
    }

    function saveLoop() {
        $.ajax({
            type: 'get',
            data: 'json=' + encodeURIComponent(JSON.stringify(state))
        }).always(function() {
            setTimeout(saveLoop, 10000);
        });
    };

    function clearIncoming() {
        $('input.onHand').each(function () {
            $(this).attr('data-incoming', 0);
            $(this).closest('td').find('span.incoming-notice').html('');
        });
    };

    function getIncoming(min, max) {
        var store = $('select[name=store]').val();
        $.ajax({
            type: 'get',
            data: 'date1='+min+'&date2='+max+'&store='+store,
            dataType: 'json'
        }).done(function (resp) {
            var qtyMap = {};
            var textMap = {};
            for (var i=0; i<resp.length; i++) {
                var obj = resp[i];
                qtyMap[obj.upc] = obj.qty;
                textMap[obj.upc] = obj.text;
            }
            console.log(qtyMap);
            $('td.upc a').each(function () {
                var upc = $(this).text();
                if (qtyMap.hasOwnProperty(upc)) {
                    console.log(upc);
                    var row = $(this).closest('tr');
                    var onHand = $(row).find('input.onHand');
                    $(onHand).attr('data-incoming', qtyMap[upc]);
                    $(onHand).closest('td').find('span.incoming-notice').html(textMap[upc]);
                    //$(onHand).closest('td').addClass('alert-success').attr('title', 'Incoming: ' + qtyMap[upc] + ' from ' + brandMap[upc]);
                    mod.reCalcRow(row);
                }
            });
        });
    };

    mod.initState = function(s) {
        if (typeof s == 'object') {
            state = s;
            if (state['onHand'].__proto__ == Array.prototype) {
                state['onHand'] = {};
            }
            if (state['orderAmt'].__proto__ == Array.prototype) {
                state['orderAmt'] = {};
            }
            if (state['directAmt'].__proto__ == Array.prototype) {
                state['directAmt'] = {};
            }
            var i = 0;
            $('.daycheck').each(function() {
                if (state['days'][i]) {
                    $(this).prop('checked', true);
                }
                i++;
            });
            mod.updateDays();

            var oIDs = Object.keys(state['orderAmt']);
            for (i=0; i<oIDs.length; i++) {
                var elemID = oIDs[i];
                if (state['orderAmt'][elemID] !== '') {
                    document.getElementById(elemID).value = Number(state['orderAmt'][elemID]);
                }
            }

            var hIDs = Object.keys(state['onHand']);
            for (i=0; i<hIDs.length; i++) {
                var elemID = hIDs[i];
                var elem = $('#'+elemID);
                $(elem).val(state['onHand'][elemID]);
                mod.reCalcRow($(elem).closest('tr'));
            }

            var oIDs = Object.keys(state['orderAmt']);
            for (i=0; i<oIDs.length; i++) {
                var elemID = oIDs[i];
                if (state['orderAmt'][elemID] !== '') {
                    document.getElementById(elemID).value = Number(state['orderAmt'][elemID]);
                }
            }
        }
        //saveLoop();
    };

    mod.updateOnHand = function(elem) {
        var onHand = state['onHand'];
        onHand[elem.id] = elem.value;
        state['onHand'] = onHand;
        mod.save();
    };

    mod.updateOrder = function(elem) {
        state['orderAmt'][elem.id] = elem.value;
        mod.save();
        var inOrder = $(elem).closest('tr').find('input:checked');
        if (inOrder.length > 0) {
            var ids = inOrder.first().val();
            $.ajax({
                'type': 'post',
                'data': 'id=' + ids + '&qty=' + elem.value,
                'dataType': 'json'
            }).done(function (resp) {
                // order is updated!
            });
        }
    };

    mod.updateDays = function() {
        clearIncoming();
        var week = $('#modProj').html();
        if (week == 0) {
            week = $('#projSales').html().replace(',', '');
        }
        var selectedDays = 0;
        minDate = false;
        maxDate = false;
        $('.daycheck:checked').each(function () {
            var pct = $(this).val().replace('%', '') / 100;
            selectedDays += pct * week;
            var dateID = $(this).data('dateid');
            if (minDate === false || dateID < minDate) {
                minDate = dateID;
            }
            if (maxDate === false || dateID > maxDate) {
                maxDate = dateID;
            }
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
        updateState();
        if (minDate !== false && maxDate !== false) {
            getIncoming(minDate, maxDate);
        }
        mod.save();
    };

    mod.reCalcRow = function(elem) {
        var caseSize = $(elem).find('td.caseSize').html();
        var adj = $(elem).find('td.parCell').html();
        var onHand = $(elem).find('input.onHand').val();
        if (onHand <= 0) {
            return;
        }
        if ($('input#autoOrderCheck').prop('checked') == false) {
            return;
        }
        if (!retainElem) {
            retainElem = $('#retention');
        }
        onHand = onHand * (retainElem.val() / 100);
        var incoming = Number($(elem).find('input.onHand').attr('data-incoming'));
        if (!isNaN(incoming)) {
            onHand += incoming;
        }

        var start = (adj * 1 * caseSize) - (onHand * 1 * caseSize);
        var cases = 0;
        while (start > (0.25 * caseSize)) {
            cases += 1;
            start -= caseSize;
        }
        orderField = $(elem).find('input.orderAmt');
        if (orderField.val() <= 0 && orderField.is(':visible')) {
            orderField.val(cases);
        }
    };

    mod.inc = function(btn, amt) {
        var elem = $(btn).parent().find('input.orderAmt');
        var next = ($(elem).val() * 1) + amt;
        if (next < 0) {
            next = 0;
        }
        $(elem).val(next);
        mod.updateOrder(elem);
    };

    function nextRow(elem) {
        var myRow = $(elem).closest('tr');
        var limit = 0;
        while (true) {
            var next = $(myRow).next('tr');
            if (next.length == 0) {
                break;
            }
            if ($(next).css('display') == 'none') {
                myRow = next;
            } else if (next.length > 0) {
                return next.get(0);
            }
            limit++;
            if (limit > 10) {
                break;
            }
        }
        var myTable = $(elem).closest('table');
        var nextTable = $(myTable).next().next('table');
        next = $(nextTable).find('td').first().parent();
        if ($(next).css('display') == 'none') {
            return nextRow(next);
        }
        if (next.length > 0) {
            return next.get(0);
        }

        return false;
    };

    function prevRow(elem) {
        var myRow = $(elem).closest('tr');
        var limit = 0;
        while (true) {
            var prev = $(myRow).prev('tr');
            if (prev.length == 0) {
                break;
            }
            if ($(prev).css('display') == 'none') {
                myRow = prev;
            } else if ($(prev).find('td').length > 0) {
                return prev.get(0);
            }
            limit++;
            if (limit > 10) {
                break;
            }
        }
        var myTable = $(elem).closest('table');
        var prevTable = $(myTable).prev().prev('table');
        prev = $(prevTable).find('td').last().parent();
        if ($(prev).css('display') == 'none') {
            return prevRow(prev);
        }
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
                mod.all--;
                $(elem).closest('td').addClass('info');
                if ($('#openOrders').find('#link'+resp.orderID).length == 0) {
                    var newlink = '<li id="link' + resp.orderID + '">';
                    newlink += '<a href="../../../purchasing/ViewPurchaseOrders.php?id=' + resp.orderID + '">';
                    newlink += resp.name + '</a></li>';
                    $('#openOrders').append(newlink);
                    $('#altOpenOrders').append(newlink);
                }
                var orderIDs = "";
                $('#openOrders li').each(function () {
                    orderIDs += $(this).attr('id').replace('link', '') + ",";
                });
                if (orderIDs) {
                    var printLink = '<a href="RpPrintOrders.php?id=' + orderIDs + '">Print these</a>';
                    $('#printLink').html(printLink);
                    $('#altPrintLink').html(printLink);
                }
            });
        } else {
            $.ajax({
                'type': 'post',
                'data': 'id=' + id + '&_method=delete',
                'dataType': 'json'
            }).done(function (resp) {
                $(elem).closest('td').removeClass('info');
                mod.all--;
            });
        }
    };

    function endOrderAll(count, meters, buttons) {
        if (count > 15 || mod.all <= 0) {
            meters.hide();
            buttons.prop('disabled', false);
        } else {
            setTimeout(function () { endOrderAll(count + 1, meters, buttons) }, 1000);
        }
    };

    mod.all = 0;
    mod.orderAll = function() {
        var buttons = $('button.orderAll');
        var meters = $('.progress');
        buttons.prop('disabled', true);
        meters.show();
        mod.all = 0;

        $('input.orderPri').each(function () {
            var qty = $(this).closest('tr').find('input.orderAmt').val();
            var secondary = $(this).closest('tr').find('input.orderSec');
            if (qty > 0 && !$(this).prop('checked') && !secondary.prop('checked')) {
                $(this).prop('checked', true);
                mod.all++;
                mod.placeOrder(this);
            }
        });

        setTimeout(function () { endOrderAll(1, meters, buttons) }, 1000);
    };

    mod.vendorFilter = function() {
        $('tr.item-row').hide();
        $('input.vFilter:checked').each(function () {
            $('tr.vendor-' + $(this).val()).show();
        });
    };

    return mod;
})(jQuery);
