var orderView = (function($) {
    var mod = {};
    var forceUPC = true;

    mod.forceUPC = function(f) {
        forceUPC = f;
    };

    mod.saveContactInfo = function()
    {
        var dstr = $('.contact-field').serialize();
        dstr += '&orderID='+$('#orderID').val();
        $.ajax({
            type: 'post',
            data: dstr,
            dataType: 'json'
        }).done(function(resp) {
            console.log(resp);
        });
    };

    mod.saveItem = function()
    {
        var dstr = $(this).closest('tbody').find('.item-field').serialize();
        dstr += '&orderID='+$('#orderID').val();
        dstr += '&changed='+$(this).attr('name');
        var elem = $(this).closest('tbody');
        $.ajax({
            type: 'post',
            data: dstr,
            dataType: 'json'
        }).done(function(resp) {
            if (resp.regPrice) {
                elem.find('input[name="srp"]').val(resp.regPrice);
            }
            if (resp.total) {
                elem.find('input[name="actual"]').val(resp.total);
            }
            if (resp.discount) {
                if (elem.find('.disc-percent').html() !== 'Sale') {
                    elem.find('.disc-percent').html(resp.discount + '%');
                }
            }
        });
    };

    mod.confirmC = function(oid,tid,label){
        if (window.confirm("Are you sure you want to close this order as "+label+"?")){
            $.ajax({
                url: 'OrderAjax.php',
                type: 'post',
                data: 'id='+oid+'&close='+tid
            }).done(function(){
                window.location = $('#redirectURL').val();
            });
        }
    };

    mod.afterLoadCustomer = function() {
        $('.contact-field').change(mod.saveContactInfo);
        $('#memNum').change(mod.memNumEntered);
        $('#s_personNum').change(function() {
            mod.savePN($('#orderID').val(), $(this).val());
        });
        $('.done-btn').click(function(e) {
            mod.validateAndHome();
            e.preventDefault();
            return false;
        });
        $('#orderStatus').change(function() {
            mod.updateStatus($('#orderID').val(), $(this).val());
            if ($(this).val() == 0) { // New No Call
                $('#ctcselect').val(0).trigger('change'); // No
            } else if ($(this).val() == 3) { // New Call
                $('#ctcselect').val(1).trigger('change'); // Yes
            }
        });
        $('#orderStore').change(function() {
            mod.updateStore($('#orderID').val(), $(this).val());
        });
        $('.print-cb').change(function() {
            mod.togglePrint($('#orderID').val());
        });
        $('.btn-test-send').click(function(){
            $.ajax({
                url: 'OrderAjax.php',
                type: 'post',
                dataType: 'json',
                data: 'id='+$('#orderID').val()+'&testNotify=1'
            }).done(function(resp){
                if (resp.sentEmail) {
                    alert('Sent Test Notification');
                } else {
                    alert('Notification Test Failed');
                }
            });
        });
    };

    mod.saveCtC = function (val,oid){
        $.ajax({
            url: 'OrderAjax.php',
            type: 'post',
            data: 'id='+oid+'&ctc='+val
        });
    };

    mod.memNumEntered = function(){
        var oid = $('#orderID').val();
        var cardno = $('#memNum').val();	
        $.ajax({
            type: 'get',
            data: 'customer=1&orderID='+oid+'&memNum='+cardno,
            dataType: 'json'
        }).done(function(resp){
            if (resp.footer) {
                $('#footerDiv').html(resp.footer);
                $('#confirm-date').change(function(e) {
                    mod.saveConfirmDate(e.target.checked, $('#orderID').val());
                });
                $('#ctcselect').change(function() {
                    mod.saveCtC($(this).val(), $('#orderID').val());
                });
            }
            if (resp.customer) {
                $('#customerDiv').html(resp.customer);
                mod.afterLoadCustomer();
            }
            if (resp.memType) {
                var memType = parseInt(resp.memType, 10);
                var discMemTypes = [1,3,5,6];
                console.log($.inArray(memType, discMemTypes));
                var i = 0;
                $('.upc').each(function(){
                    i++;
                });
                $('.upc').each(function(){
                    var upc = $(this).text();
                    var srp = $('#srp'+i).val();
                    var actual = $('#act'+i).val();
                    if ($.inArray(memType, discMemTypes) != -1) {
                        // add member discounts 
                        if (actual == srp) {
                            var discText = $('#discPercent'+upc).text();
                            if (discText != 'Never' && discText != 'Sale') {
                                var newsrp = actual - (actual * 0.15);
                                newsrp = newsrp.toFixed(2);
                                $('#act'+i).val(newsrp);
                                $('#act'+i).trigger('change');
                            }
                        }
                    } else {
                        // remove member discounts 
                        if (actual < srp) {
                            var discText = $('#discPercent'+upc).text();
                            if (discText != 'Sale') {
                                $('#act'+i).val(srp);
                                $('#act'+i).trigger('change');
                            }
                        }
                    }
                    i--;
                });
            }
        });
    };

    mod.searchWindow = function (){
        window.open('search.php','Search',
            'width=350,height=400,status=0,toolbar=0,scrollbars=1');
    };

    mod.afterLoadItems = function() {
        $('.item-field').change(mod.saveItem);
        if ($('#newqty').length) {
            $('#newqty').focus();	
            $('#itemDiv form').submit(function (e) {
                mod.newQty($(this).data('order'), $(this).data('trans'));
                e.preventDefault();
                return false;
            });
        } else if ($('#newdept').length) {
            $('#newbrand').focus();	
            bindAutoComplete('#newbrand', '../ws/', 'brand');
            $('#itemDiv form').submit(function (e) {
                mod.newDept($(this).data('order'), $(this).data('trans'));
                e.preventDefault();
                return false;
            });
        } else {
            $('#itemDiv form').submit(function(e) {
                mod.addUPC();
                e.preventDefault();
                return false;
            });
        }
        $('.close-order-btn').click(function (e) {
            mod.confirmC($('#orderID').val(), $(this).data('close'), $(this).html());
            e.preventDefault();
            return false;
        });
        $('.btn-delete').click(function (e) {
            mod.deleteID($(this).data('order'), $(this).data('trans'));
            e.preventDefault();
            return false;
        });
        $('.itemChkO').change(function () {
            mod.toggleO($(this).data('order'), $(this).data('trans'));
        });
        $('.itemChkA').change(function () {
            var checked = $(this).is(':checked');
            var verbiage = (checked) ? 'Are you sure this item has arrived?' : 'Change item to not yet arrived?';
            var c = confirm(verbiage);
            if (c == true) {
                mod.toggleA($(this).data('order'), $(this).data('trans'), checked);
            } else {
                $(this).prop('checked', !checked);
            }
        });
        $('.add-po-btn').click(function(ev) {
            ev.preventDefault();
            var dstr = 'addPO=1&orderID=' + $(this).data('order');
            dstr += '&transID='+$(this).data('trans');
            dstr += '&storeID='+$(this).data('store');
            var elem = $(this);
            $.ajax({
                url: 'OrderViewPage.php',
                type: 'post',
                data: dstr,
                dataType: 'json'
            }).done(function(resp) {
                if (!resp.error && resp.poID) {
                    elem.closest('span').html('<a href="../purchasing/ViewPurchaseOrders.php?id=' + resp.poID + '">In PO</a>');
                    // want to check, not necessarily toggle
                    //mod.toggleO(elem.data('order'), elem.data('trans'));
                }
            });
        });
        $('.btn-search').click(mod.searchWindow);
        bindAutoComplete('input.input-vendor', '../ws/', 'vendor');
        $('select.chosen').chosen();
    };

    mod.addUPC = function()
    {
        var oid = $('#orderID').val();
        var cardno = $('#memNum').val();
        var upc = $('#newupc').val();
        var qty = $('#newcases').val();
        if (/^\d+$/.test(upc.trim()) || !forceUPC) {
            $.ajax({
                type: 'post',
                data: 'orderID='+oid+'&memNum='+cardno+'&upc='+upc+'&cases='+qty
            }).done(function(resp){
                $('#itemDiv').html(resp);
                mod.afterLoadItems();
            });
        }
    };
    mod.deleteID = function(orderID,transID)
    {
        $.ajax({
            data: '_method=delete&orderID='+orderID+'&transID='+transID
        }).done(function(resp){
            $('#itemDiv').html(resp);
            mod.afterLoadItems();
        });
    };
    mod.newQty = function (oid,tid){
        var qty = $('#newqty').val();
        $.ajax({
            type: 'post',
            data: 'orderID='+oid+'&transID='+tid+'&qty='+qty
        }).done(function(resp){
            $('#itemDiv').html(resp);
            mod.afterLoadItems();
        });
    };
    mod.newDept = function (oid,tid){
        var dstr = $('.more-item-info :input').serialize();
        $.ajax({
            type: 'post',
            data: 'orderID='+oid+'&transID='+tid+'&'+dstr
        }).done(function(resp){
            $('#itemDiv').html(resp);
            mod.afterLoadItems();
        });
    };
    mod.savePN = function (oid,val){
        $.ajax({
            url: 'OrderAjax.php',
            type: 'post',
            data: 'pn='+val+'&id='+oid
        });
    };
    mod.saveConfirmDate = function (val,oid){
        if (val){
            $.ajax({
                url: 'OrderAjax.php',
                type: 'post',
                data: 'id='+oid+'&confirm=1'
            }).done(function(resp){
                $('#confDateSpan').html('Confirmed '+resp);
            });
        } else {
            $.ajax({
                url: 'OrderAjax.php',
                type: 'post',
                data: 'id='+oid+'&confirm=0'
            }).done(function(){
                $('#confDateSpan').html('Not confirmed');
            });
        }
    };
    mod.togglePrint = function (oid)
    {
        $.ajax({
            type: 'post',
            data: 'togglePrint=1&orderID='+oid
        });
    };
    mod.toggleO = function (oid,tid)
    {
        $.ajax({
            type: 'post',
            data: 'toggleMemType=1&orderID='+oid+'&transID='+tid
        });
    };
    mod.toggleA = function (oid,tid,checked)
    {
        console.log(checked);
        $.ajax({
            type: 'post',
            dataType: 'json',
            data: 'toggleStaff=1&orderID='+oid+'&transID='+tid+'&checked='+checked,
        }).done(function(resp) {
            if (resp.sentEmail) {
                alert('Sent Arrival Notification');
            }
        });
    };
    mod.doSplit = function (oid,tid){
        var dcheck=false;
        $('select.editDept').each(function(){
            if ($(this).val() === '0'){
                dcheck=true;
            }
        });

        if (dcheck){
            window.alert("Item(s) don't have a department set");
            return false;
        }

        $.ajax({
            url: 'ajax-calls.php',
            type: 'post',
            data: 'action=SplitOrder&orderID='+oid+'&transID='+tid
        }).done(function(resp){
            $('#itemDiv').html(resp);
            mod.afterLoadItems();
        });
    };
    mod.validateAndHome = function (){
        var dcheck=false;
        $('select.editDept').each(function(){
            if ($(this).val() === '0'){
                dcheck=true;
            }
        });

        if (dcheck){
            window.alert("Item(s) don't have a department");
            return false;
        }

        var CtC = $('#ctcselect').val();
        if (CtC === '2'){
            window.alert("Choose Call to Confirm option");
            return false;
        }

        var nD = $('#nDept').val();
        var nT = $('#nText').val();
        if (nT !== '' && nD === '0') {
            window.alert("Assign your notes to a department");
            return false;
        }

        if ($('#orderStore').val() == 0) {
            window.alert('Choose a store');
        } else {
            window.location = $('#redirectURL').val();
        }

        return false;
    };
    mod.updateStatus = function updateStatus(oid,val){
        $.ajax({
            url: 'OrderAjax.php',
            type: 'post',
            dataType: 'json',
            data: 'id='+oid+'&status='+val
        }).done(function(resp){
            $('#statusdate'+oid).html(resp.tdate);
            if (resp.sentEmail) {
                alert('Sent Arrival Notification');
            }
        });
    };
    mod.updateStore = function updateStore(oid, val)
    {
        $.ajax({
            url: 'OrderAjax.php',
            type: 'post',
            data: 'id='+oid+'&store='+val
        });
    }

    mod.getComms = function(oid) {
        $.ajax({
            url: 'OrderAjax.php',
            type: 'get',
            data: 'id='+oid+'&comms=1'
        }).done(function (resp) {
            $('#commLog').html(resp);
        });
    };

    mod.sendMsg = function() {
        var msgID = $('#commID').val();
        if (msgID == 0) {
            return;
        }
        $.ajax({
            url: 'OrderAjax.php',
            type: 'post',
            data: 'id='+$('#orderID').val() + '&commID=' + msgID
        }).done(function (resp) {
            $('#commLog').html(resp);
        });
    };

    mod.getBetterDeal = function() {
        let isMember = $('#isMember').val();
        $('tbody').each(function(){
            let srp = $(this).find('input[name="srp"]').val()
            let actual = $(this).find('input[name="actual"]').val()
            let discPercent = 1 - (actual / srp);
            let discPercentString = $(this).find('td.disc-percent').text()

            if (actual < srp && discPercentString == 'Sale' && discPercent < 0.15) {
                let discPrice = srp - (srp * 0.15)
                discPrice = discPrice.toFixed(2);

                $(this).find('input[name="actual"]').val(discPrice);
                $(this).find('input[name="actual"]').change();
                $(this).find('td.disc-percent').text('15%');
            }
        });
    };

    return mod;

}(jQuery));

$(document).ready(function(){
	var initoid = $('#init_oid').val();
    orderView.afterLoadCustomer();
    $('#ctcselect').change(function() {
        orderView.saveCtC($(this).val(), $('#orderID').val());
    });
    $('.done-btn').click(function(e) {
        orderView.validateAndHome();
        e.preventDefault();
        return false;
    });
    $('#confirm-date').change(function(e) {
        orderView.saveConfirmDate(e.target.checked, $('#orderID').val());
    });
    orderView.afterLoadItems();

    orderView.getComms($('#orderID').val());
});

$(window).unload(function() {
	$('#nText').change();
});

