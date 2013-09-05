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
