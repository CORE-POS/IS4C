function deleteCat(num,name){
	var vid = $('#vendorID').val();
	if (confirm('Delete '+name+' ('+num+')?')){
		$.ajax({
			url: 'VendorDepartmentEditor.php',
			type: 'POST',
            dataType: 'json',
			timeout: 5000,
			data: 'deptID='+num+'&vid='+vid+'&action=deleteCat',
			error: function(){
                showBootstrapAlert('#alert-area', 'danger', 'Network error deleting #' + num);
			},
			success: function(resp){
                if (resp.error) {
                    showBootstrapAlert('#alert-area', 'danger', resp.error);
                } else {
                    $('#row-'+num).hide();
                }
			}
		});
	}
}

function newdept(){
	var vid = $('#vendorID').val();
	var num = $('#newno').val();
	var name = $('#newname').val();

	$.ajax({
		url: 'VendorDepartmentEditor.php',
		type: 'POST',
        dataType: 'json',
		timeout: 5000,
		data: 'deptID='+num+'&vid='+vid+'&name='+name+'&action=createCat',
		error: function(){
            showBootstrapAlert('#alert-area', 'danger', 'Network error creating department');
		},
		success: function(resp){
            if (resp.error) {
                showBootstrapAlert('#alert-area', 'danger', resp.error);
            } else if (resp.row) {
                $('.table').append(resp.row);
                $('#newform').hide();
                $('#newform :input').each(function(){
                    $(this).val('');
                });
            } else {
                showBootstrapAlert('#alert-area', 'danger', 'Error: invalid response from server');
            }
		}
	});
}

function edit(did)
{
	var name = $('#nametd'+did).html();
	var margin = $('#margintd'+did).html();

	$('#nametd'+did).html("<input id=in"+did+" type=text class=form-control value=\""+name+"\" />");
	$('#margintd'+did).html("<input id=im"+did+" type=text class=form-control value=\""+margin+"\" />");

    $('#button'+did+' .edit-link').hide();
    $('#button'+did+' .save-link').show();
}

function save(did)
{
	var name = $('#in'+did).val();
	var margin = $('#im'+did).val();
	var vid = $('#vendorID').val();

	$('#nametd'+did).html(name);
	$('#margintd'+did).html(margin);

    $('#button'+did+' .edit-link').show();
    $('#button'+did+' .save-link').hide();

	name = encodeURIComponent(name);
	$.ajax({
		url: 'VendorDepartmentEditor.php',
		type: 'POST',
        dataType: 'json',
		timeout: 5000,
		data: 'deptID='+did+'&vid='+vid+'&name='+name+'&margin='+margin+'&action=updateCat',
		error: function(){
            showBootstrapAlert('#alert-area', 'danger', 'Network error saving #' + did);
		},
		success: function(resp){
            if (resp.error) {
                showBootstrapAlert('#alert-area', 'danger', resp.error);
            }
		}
	});
}
