function catchange(){
	var did = $('#categoryselect').val();
	var vid = $('#vendorID').val();

	$('#contentarea').html('');
	if (did == ""){
		$('#brandselect').html("<option value=\"\">Select a department first...</option>");
	}
	else {
		$.ajax({
			url: 'BrowseVendorItems.php',
			type: 'post',
			timeout: 5000,
			data: 'vid='+vid+'&deptID='+did+'&action=getCategoryBrands',
			error: function(){
			alert('Error loading XML document');
			},
			success: function(resp){
				$('#brandselect').html(resp);
			}
		});
	}
}

function addToPos(upc){
	var vid = $('#vendorID').val();
	var price = $('#price'+upc).val();
	var dept = $('#dept'+upc).val();
	$.ajax({
		url: 'BrowseVendorItems.php',
		type: 'POST',
		timeout: 5000,
		data: 'upc='+upc+'&vid='+vid+'&price='+price+'&dept='+dept+'&action=addPosItem',
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			$('#price'+upc).parent().html('&nbsp;');
			$('#dept'+upc).parent().html('&nbsp;');
			$('#button'+upc).html('&nbsp;');
			var cssObj = { "background" : "#ffffcc" }
			$('#row'+upc).css(cssObj);
		}
	});
}

function brandchange() {
	var did = $('#categoryselect').val();
	var vid = $('#vendorID').val();
	var brand = $('#brandselect').val();

	if (brand == ""){
		$('#contentarea').html('');
	}
	else {
		$.ajax({
			url: 'BrowseVendorItems.php',
			type: 'post',
			data: 'vid='+vid+'&deptID='+did+'&brand='+brand+'&action=showCategoryItems',
			error: function(e1,e2){
				alert('Error loading XML document');
			},
			success: function(resp){
				$('#contentarea').html(resp);
			}
		});
	}
}
