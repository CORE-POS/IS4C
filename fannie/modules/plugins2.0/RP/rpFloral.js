var rpOrder = (function ($) {

    var mod = {};
    var state = {
        'retention': 60,
        'days': [false, false, false, false, false, false, false],
        'onHand': {},
        'orderAmt': {},
        'directAmt': {},
        'floralAmt': {},
        'priFarms': {},
        'secFarms': {}
    };
    var retainElem = false;
    var minDate = false;
    var maxDate = false;

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
            if (state['floralAmt'].__proto__ == Array.prototype) {
                state['floralAmt'] = {};
            }
            if (!state.hasOwnProperty('priFarms') || state['priFarms'].__proto__ == Array.prototype) {
                state['priFarms'] = {};
            }
            if (!state.hasOwnProperty('secFarms') || state['secFarms'].__proto__ == Array.prototype) {
                state['secFarms'] = {};
            }
            var i = 0;
            $('.daycheck').each(function() {
                if (state['days'][i]) {
                    $(this).prop('checked', true);
                }
                i++;
            });
            mod.updateDays();

            var hIDs = Object.keys(state['onHand']);
            for (i=0; i<hIDs.length; i++) {
                var elemID = hIDs[i];
                var elem = $('#'+elemID);
                $(elem).val(state['onHand'][elemID]);
                mod.reCalcRow($(elem).closest('tr'));
            }

            var oIDs = Object.keys(state['floralAmt']);
            for (i=0; i<oIDs.length; i++) {
                var elemID = oIDs[i];
                if (state['floralAmt'][elemID] !== '') {
                    var field = document.getElementById(elemID);
                    if (field) {
                        field.value = Number(state['floralAmt'][elemID]);
                    }
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
        state['floralAmt'][elem.id] = elem.value;
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
        var selectedDays = 0;
        $('.daycheck:checked').each(function () {
            selectedDays += 1;
        });

        var scaler = selectedDays;
        if (scaler == 0) {
            scaler = 1;
        }

        $('input.basePar').each(function () {
            var adj = $(this).val() * 1 * scaler;
            if (isNaN(scaler)) {
                adj = $(this).val();
            }
            var adj = Math.round(adj * 100) / 100;
            $(this).next('td.parCell').html(adj);

            mod.reCalcRow($(this).closest('tr'));
        });
        updateState();
        mod.save();
    };

    mod.reCalcRow = function(elem) {
        var adj = $(elem).find('td.parCell').html();
        var onHand = $(elem).find('input.onHand').val();
        if (onHand <= 0) {
            return;
        }
        if ($('input#autoOrderCheck').prop('checked') == false) {
            return;
        }

        var estimate = Math.round(adj - onHand);

        orderField = $(elem).find('input.orderAmt');
        if (orderField.val() <= 0 && orderField.is(':visible')) {
            orderField.val(estimate);
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
        } else if (ev.which == 39) {
            ev.preventDefault();
            $(ev.target).closest('tr').find('input.secondAmt').focus();
        }
    };

    mod.placeOrder = function(elem) {
        var id = encodeURIComponent($(elem).val());
        farm = $(elem).closest('tr').find('.primaryFarm').val();
        id += "" + farm;
        if ($(elem).prop('checked')) {
            qty = $(elem).closest('tr').find('input.orderAmt').val();
            if (farm == '' || !qty) {
                return;
            }
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
            if (qty > 0 && !$(this).prop('checked')) {
                $(this).prop('checked', true);
                mod.placeOrder(this);
            }
        });
        $('input.orderSec').each(function () {
            var qty = $(this).closest('tr').find('input.secondAmt').val();
            if (qty > 0 && !$(this).prop('checked')) {
                $(this).prop('checked', true);
                mod.placeOrder(this);
            }
        });

        setTimeout(function () { endOrderAll(1, meters, buttons) }, 1000);
    };

    mod.defaultFarm = function(farm) {
        if (farm) {
            $('.primaryFarm').val(farm);
        } else {
            $('.primaryFarm').each(function() {
                $(this).val($(this).attr('data-default'));
            });
        }
    };

    return mod;
})(jQuery);
