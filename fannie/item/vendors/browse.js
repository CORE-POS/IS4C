function catchange(){
	var did = $('#categoryselect').val();
	var vid = $('#vendorID').val();

	$('#contentarea').html('');
	if (did == ""){
		$('#brandselect').html("<option value=\"\">Select a subcategory first...</option>");
	} else {
		$.ajax({
			url: 'BrowseVendorItems.php',
			type: 'post',
			timeout: 5000,
			data: 'vid='+vid+'&deptID='+did+'&action=getCategoryBrands'
        }).fail(function(){
			alert('Error loading XML document');
        }).done(function(resp){
            $('#brandselect').html(resp);
            $('#brandselect').focus();
		});
	}
}

function addToPos(upc){
	var vid = $('#vendorID').val();
	var price = $('#price'+upc).val();
	var dept = $('#dept'+upc).val();
    var tags = $('#shelftags').val();
	$.ajax({
		url: 'BrowseVendorItems.php',
		type: 'POST',
		timeout: 5000,
		data: 'upc='+upc+'&vid='+vid+'&price='+price+'&dept='+dept+'&tags='+tags+'&action=addPosItem'
    }).fail(function(){
		alert('Error loading XML document');
    }).done(function(resp){
        $('#price'+upc).parent().html('&nbsp;');
        $('#dept'+upc).parent().html('&nbsp;');
        $('#button'+upc).html('&nbsp;');
        var cssObj = { "background" : "#ffffcc" }
        $('#row'+upc).css(cssObj);
	});
}

function brandchange() {
	var did = $('#categoryselect').val();
	var vid = $('#vendorID').val();
	var brand = $('#brandselect').val();

	if (brand == ""){
		$('#contentarea').html('');
	} else {
        $('#loading-bar').show();
		$.ajax({
			url: 'BrowseVendorItems.php',
			type: 'post',
			data: 'vid='+vid+'&deptID='+did+'&brand='+brand+'&action=showCategoryItems',
            dataType: 'json'
        }).fail(function() {
            $('#loading-bar').hide();
            showBootstrapAlert('#contentarea', 'danger', 'Error loading items');
        }).done(function(resp){
            $('#loading-bar').hide();
            if (resp.items) {
                $('#contentarea').html(resp.items);
            }
            if (resp.tags && resp.tags != -999) {
                $('#shelftags').val(resp.tags);
            }
		});
	}
}
