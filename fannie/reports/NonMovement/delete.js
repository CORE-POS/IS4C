function backgroundDelete(upc, description)
{
	if (!confirm('Delete '+upc+' '+description)){
		return false;
	}

	$.ajax({
		url: 'NonMovementReport.php',
		cache: false,
		data: 'deleteItem='+upc,
		success: function(data){
			$('#del'+upc).closest('tr').remove();
		}
	});
}

function backgroundDeactivate(upc)
{
	$.ajax({
		url: 'NonMovementReport.php',
		cache: false,
		data: 'deactivate='+upc,
		success: function(data) {
			$('#del'+upc).closest('tr').remove();
		}
	});
}

