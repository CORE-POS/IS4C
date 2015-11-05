function fetchOrders(){

	var dataStr = $('#orderStatus').val()+"=1";
    dataStr += '&month='+$('#viewMonth').val();
    dataStr += '&year='+$('#viewYear').val();
	if ($('#orderShow').val() == '1')
		dataStr += '&all=1';	
	$.ajax({
		url: 'ViewPurchaseOrders.php?'+dataStr,
		type: 'get',
		success: function(data){
			$('#ordersDiv').html(data);
            $('.tablesorter').tablesorter([[0, 1]]);
		}
	});
}

function fetchPage(pager) {
	var dataStr = $('#orderStatus').val()+"=1";
	if ($('#orderShow').val() == '1')
		dataStr += '&all=1';	
    dataStr += '&pager='+pager;
	$.ajax({
		url: 'ViewPurchaseOrders.php?'+dataStr,
		type: 'get',
		success: function(data){
			$('#ordersDiv').html(data);
            window.scrollTo(0,0);
		}
    });
}

function togglePlaced(orderID){
	var dataStr = 'id='+orderID+'&setPlaced=';
    console.log($('#receiveBtn').length);
	if ($('#placedCheckbox').prop('checked')) {
		dataStr += '1';
        $('#receiveBtn').show();
	} else {
		dataStr += '0';
        $('#receiveBtn').hide();
    }

	$.ajax({
		url: 'ViewPurchaseOrders.php?',
		type: 'post',
		data: dataStr,
		success: function(data){
			$('#orderPlacedSpan').html(data);
		}
	});
}

function doExport(orderID){
	window.location = 'ViewPurchaseOrders.php?id='+orderID+'&export='+$('#exporterSelect').val();
}

function deleteOrder(orderID)
{
    if (confirm('Delete this order?')) {
        $.ajax({
            type: 'delete',
            data: 'id=' + orderID,
            success: function(result) {
                location='ViewPurchaseOrders.php?init=pending';
            }
        });
    }
}

function receiveSKU()
{
    var dstr = $('#receive-form').serialize();
    $.ajax({
        type: 'get',
        data: dstr,
        success: function(resp) {
            $('#item-area').html(resp);
            if ($('#item-area input').length > 0) {
                $('#item-area input[type!=hidden]:first').focus();
                $('#sku-in').val('');
            } else {
                $('#sku-in').focus();
            }
        }
    });
}

function saveReceive()
{
    var dstr = $('#item-area :input').serialize();
    console.log(dstr);
    $.ajax({
        type: 'post',
        data: dstr,
        success: function (resp) {
            $('#item-area').html('');
            $('#sku-in').focus();
        }
    });
}

