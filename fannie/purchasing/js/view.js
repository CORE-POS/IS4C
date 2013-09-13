function fetchOrders(){

	var dataStr = $('#orderStatus').val()+"=1";
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
