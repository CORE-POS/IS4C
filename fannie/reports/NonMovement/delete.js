function backgroundDelete(upc, description){
	if (!confirm('Delete '+upc+' '+description)){
		return false;
	}

	$.ajax({
		url: 'NonMovementReport.php',
		cache: false,
		data: 'deleteItem='+upc,
		success: function(data){
			$('#del'+upc).html('DELETED');
		}
	});
}
