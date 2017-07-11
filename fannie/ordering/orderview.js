var orderView = (function($) {
    var mod = {};
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
                $('#ctcselect').val(0); // No
            } else if ($(this).val() == 3) { // New Call
                $('#ctcselect').val(1); // Yes
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
                    alert('Emailed Test Notification');
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
            mod.toggleA($(this).data('order'), $(this).data('trans'));
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
        $.ajax({
            type: 'post',
            data: 'orderID='+oid+'&memNum='+cardno+'&upc='+upc+'&cases='+qty
        }).done(function(resp){
            $('#itemDiv').html(resp);
            mod.afterLoadItems();
        });
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
    mod.toggleA = function (oid,tid)
    {
        $.ajax({
            type: 'post',
            dataType: 'json',
            data: 'toggleStaff=1&orderID='+oid+'&transID='+tid
        }).done(function(resp) {
            if (resp.sentEmail) {
                alert('Emailed Arrival Notification');
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
                alert('Emailed Arrival Notification');
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

    return mod;

}(jQuery));

$(document).ready(function(){
	var initoid = $('#init_oid').val();
	$.ajax({
        type: 'get',
        data: 'customer=1&orderID='+initoid,
        dataType: 'json'
	}).done(function(resp){
        if (resp.customer) {
            $('#customerDiv').html(resp.customer);
            orderView.afterLoadCustomer();
        }
        if (resp.footer) {
            $('#footerDiv').html(resp.footer);
            $('#confirm-date').change(function(e) {
                orderView.saveConfirmDate(e.target.checked, $('#orderID').val());
            });
            $('#ctcselect').change(function() {
                orderView.saveCtC($(this).val(), $('#orderID').val());
            });
            $('.done-btn').click(function(e) {
                orderView.validateAndHome();
                e.preventDefault();
                return false;
            });
        }
		var oid = $('#orderID').val();
		$.ajax({
            type: 'get',
            data: 'items=1&orderID='+oid
		}).done(function(resp){
			$('#itemDiv').html(resp);
            orderView.afterLoadItems();
		});
	});
});

$(window).unload(function() {
	$('#nText').change();
});

