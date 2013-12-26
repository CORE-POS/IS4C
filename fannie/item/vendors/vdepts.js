function deleteCat(num,name){
	var vid = $('#vendorID').val();
	if (confirm('Delete '+name+' ('+num+')?')){
		$.ajax({
			url: 'VendorDepartmentEditor.php',
			type: 'POST',
			timeout: 5000,
			data: 'deptID='+num+'&vid='+vid+'&action=deleteCat',
			error: function(){
			alert('Error loading XML document');
			},
			success: function(resp){
				alert(resp);
				top.location='VendorDepartmentEditor.php?vid='+vid;
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
		timeout: 5000,
		data: 'deptID='+num+'&vid='+vid+'&name='+name+'&action=createCat',
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			alert(resp);
			if (resp == "Department created")
				top.location='VendorDepartmentEditor.php?vid='+vid;
		}
	});
}

function edit(did){
	var name = $('#nametd'+did).html();
	var margin = $('#margintd'+did).html();
	var path = $('#urlpath').val();

	$('#nametd'+did).html("<input id=in"+did+" type=text size=25 value=\""+name+"\" />");
	$('#margintd'+did).html("<input id=im"+did+" type=text size=6 value=\""+margin+"\" />");

	var newbutton = "<a href=\"\" onclick=\"save("+did+"); return false;\">";
	newbutton += "<img src=\""+path+"src/img/buttons/b_save.png\" ";
	newbutton += "alt=\"Save\" border=0 /></a>";
	$('#button'+did).html(newbutton);	
}

function save(did){
	var name = $('#in'+did).val();
	var margin = $('#im'+did).val();
	var path = $('#urlpath').val();
	var vid = $('#vendorID').val();

	$('#nametd'+did).html(name);
	$('#margintd'+did).html(margin);

	var newbutton = "<a href=\"\" onclick=\"edit("+did+"); return false;\">";
	newbutton += "<img src=\""+path+"src/img/buttons/b_edit.png\" ";
	newbutton += "alt=\"Edit\" border=0 /></a>";
	$('#button'+did).html(newbutton);	
	
	name = encodeURIComponent(name);
	$.ajax({
		url: 'VendorDepartmentEditor.php',
		type: 'POST',
		timeout: 5000,
		data: 'deptID='+did+'&vid='+vid+'&name='+name+'&margin='+margin+'&action=updateCat',
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			// do nothing
		}
	});
}
