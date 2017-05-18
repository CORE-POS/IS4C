function fetchOrders(){

	var dataStr = $('#orderStatus').val()+"=1";
    dataStr += '&month='+$('#viewMonth').val();
    dataStr += '&year='+$('#viewYear').val();
	if ($('#orderShow').val() == '1')
		dataStr += '&all=1';	
    dataStr += '&store=' + $('#storeID').val();
	$.ajax({
		url: 'ViewPurchaseOrders.php?'+dataStr,
		type: 'get'
    }).done(function(data){
        $('#ordersDiv').html(data);
        $('.tablesorter').tablesorter([[0, 1]]);
        $('.table-float').floatThead();
	});
}

function togglePlaced(orderID){
	var dataStr = 'id='+orderID+'&setPlaced=';
    console.log($('#receiveBtn').length);
	if ($('#placedCheckbox').prop('checked')) {
		dataStr += '1';
        $('.pending-only').hide();
        $('.placed-only').show();
	} else {
		dataStr += '0';
        $('.pending-only').show();
        $('.placed-only').hide();
    }

	$.ajax({
		url: 'ViewPurchaseOrders.php?',
		type: 'post',
		data: dataStr
    }).done(function(data){
        $('#orderPlacedSpan').html(data);
	});
}

function doExport(orderID){
	window.location = 'ViewPurchaseOrders.php?id='+orderID+'&export='+$('#exporterSelect').val();
}

function doSend(orderID){
	window.location = 'ViewPurchaseOrders.php?id='+orderID+'&sendAs='+$('#exporterSelect').val();
}

function deleteOrder(orderID)
{
    if (confirm('Delete this order?')) {
        $.ajax({
            type: 'delete',
            data: 'id=' + orderID
        }).done(function(result) {
            location='ViewPurchaseOrders.php?init=pending';
        });
    }
}

function receiveSKU()
{
    var dstr = $('#receive-form').serialize();
    $.ajax({
        type: 'get',
        data: dstr
    }).done(function(resp) {
        $('#item-area').html(resp);
        if ($('#item-area input').length > 0) {
            $('#item-area input[type!=hidden]:first').focus();
            $('#sku-in').val('');
        } else {
            $('#sku-in').focus();
        }
    });
}

function saveReceive()
{
    var dstr = $('#item-area :input').serialize();
    $.ajax({
        type: 'post',
        data: dstr
    }).done(function (resp) {
        $('#item-area').html('');
        $('#sku-in').focus();
    });
}

var autoTimeout;
function autoSaveNotes(oid, elem) {
    clearTimeout(autoTimeout);
    autoTimeout = setTimeout(function() {
        var dstr = 'id='+oid+'&note='+encodeURIComponent($(elem).val());
        $.ajax({
            type: 'post',
            data: dstr
        });

    }, 2000);
}

function isSO(oid, sku, isSO) {
    $.ajax({
        type: 'post',
        data: 'id='+oid+'&sku='+sku+'&isSO='+isSO
    });
}

function itemAdjust(orderID, sku, adjust, elem) {
    $.ajax({
        type: 'post',
        data: 'id='+orderID+'&sku='+sku+'&adjust='+adjust,
        dataType: 'json'
    }).done(function (resp) {
        if (resp.qty !== null) {
            $('#qty' + elem).html(resp.qty);
        }
    });
}

function itemInc(orderID, sku, elem) {
    itemAdjust(orderID, sku, 1, elem);
}

function itemDec(orderID, sku, elem) {
    itemAdjust(orderID, sku, -1, elem);
}

