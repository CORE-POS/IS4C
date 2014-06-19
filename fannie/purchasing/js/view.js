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
	if ($('#placedCheckbox').prop('checked'))
		dataStr += '1';
	else
		dataStr += '0';

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
