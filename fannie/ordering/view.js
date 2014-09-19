
$(document).ready(function(){
	var initoid = $('#init_oid').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=loadCustomer&orderID='+initoid,
	cache: false,
	error: function(e1,e2,e3){
		alert(e1);
		alert(e2);
		alert(e3);
	},
	success: function(resp){
		var tmp = resp.split("`");
		$('#customerDiv').html(tmp[0]);
		$('#footerDiv').html(tmp[1]);
		var oid = $('#orderID').val();
		$.ajax({
		url: 'ajax-calls.php',
		type: 'post',
		data: 'action=loadItems&orderID='+oid,
		cache: false,
		success: function(resp){
			$('#itemDiv').html(resp);
		}
		});
	}
	});
});

$(window).unload(function() {
	$('#nText').change();
	//$(':input').each(function(){
	//	$(this).change();
	//});
});


function confirmC(oid,tid){
	var t = new Array();
	t[7] = "Completed";
	t[8] = "Canceled";
	t[9] = "Inquiry";

	if (confirm("Are you sure you want to close this order as "+t[tid]+"?")){
		$.ajax({
		url: 'ajax-calls.php',
		type: 'post',
		data: 'action=closeOrder&orderID='+oid+'&status='+tid,
		cache: false,
		success: function(resp){
			//location = 'review.php?orderID='+oid;
			location = $('#redirectURL').val();
		}
		});
	}
}
function memNumEntered(){
	var oid = $('#orderID').val();
	var cardno = $('#memNum').val();	
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=reloadMem&orderID='+oid+'&memNum='+cardno,
	cache: false,
	success: function(resp){
		var tmp = resp.split("`");
		$('#customerDiv').html(tmp[0]);
		$('#footerDiv').html(tmp[1]);
	}
	});
}

function searchWindow(){
	window.open('search.php','Search',
		'width=350,height=400,status=0,toolbar=0,scrollbars=1');
}

function addUPC(){
	var oid = $('#orderID').val();
	var cardno = $('#memNum').val();
	var upc = $('#newupc').val();
	var qty = $('#newcases').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=newUPC&orderID='+oid+'&memNum='+cardno+'&upc='+upc+'&cases='+qty,
	cache: false,
	success: function(resp){
		$('#itemDiv').html(resp);
		if ($('#newqty').length)
			$('#newqty').focus();	
	}
	});
}
function deleteID(orderID,transID){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=deleteID&orderID='+orderID+'&transID='+transID,
	cache: false,
	success: function(resp){
		$('#itemDiv').html(resp);
	}
	});
}
function deleteUPC(orderID,upc){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=deleteUPC&orderID='+orderID+'&upc='+upc,
	cache: false,
	success: function(resp){
		$('#itemDiv').html(resp);
	}
	});
}
function saveDesc(new_desc,tid){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveDesc&orderID='+oid+'&transID='+tid+'&desc='+new_desc,
	cache: false,
	success: function(resp){
	}
	});
}
function savePrice(new_price,tid){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=savePrice&orderID='+oid+'&transID='+tid+'&price='+new_price,
	cache: false,
	success: function(resp){
		if ($('#discPercent'+upc).html() != 'Sale')
			$('#discPercent'+upc).html(resp+"%");
	}
	});
}
function saveSRP(new_price,tid){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveSRP&orderID='+oid+'&transID='+tid+'&srp='+new_price,
	cache: false,
	success: function(resp){
		var fields = resp.split('`')
		$('#srp'+tid).val(fields[1])	
		$('#act'+tid).val(fields[2])	
		if ($('#discPercent'+tid).html() != 'Sale')
			$('#discPercent'+tid).html(fields[0]+"%");
	}
	});
}
function saveCtC(val,oid){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveCtC&orderID='+oid+'&val='+val,
	cache: false,
	success: function(resp){
	}
	});
}
function saveQty(new_qty,tid){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveQty&orderID='+oid+'&transID='+tid+'&qty='+new_qty,
	cache: false,
	success: function(resp){
		var tmp = resp.split('`');
		$('#srp'+tid).val(tmp[0]);
		$('#act'+tid).val(tmp[1]);
	}
	});
}
function saveUnit(new_unit,tid){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveUnit&orderID='+oid+'&transID='+tid+'&unitPrice='+new_unit,
	cache: false,
	success: function(resp){
		var tmp = resp.split('`');
		$('#srp'+tid).val(tmp[0]);
		$('#act'+tid).val(tmp[1]);
	}
	});
}
function newQty(oid,tid){
	var qty = $('#newqty').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=newQty&orderID='+oid+'&transID='+tid+'&qty='+qty,
	cache: false,
	success: function(resp){
		$('#itemDiv').html(resp);
	}
	});
}
function newDept(oid,tid){
	var d = $('#newdept').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=newDept&orderID='+oid+'&transID='+tid+'&dept='+d,
	cache: false,
	success: function(resp){
		$('#itemDiv').html(resp);
	}
	});
}
function saveDept(new_dept,tid){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveDept&orderID='+oid+'&transID='+tid+'&dept='+new_dept,
	cache: false,
	success: function(resp){
	}
	});
}
function saveVendor(new_vendor,tid){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveVendor&orderID='+oid+'&transID='+tid+'&vendor='+new_vendor,
	cache: false,
	success: function(resp){
	}
	});
}
function saveFN(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveFN&orderID='+oid+'&fn='+val,
	cache: false,
	success: function(resp){}
	});
}
function saveLN(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveLN&orderID='+oid+'&ln='+val,
	cache: false,
	success: function(resp){}
	});
}
function saveCity(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveCity&orderID='+oid+'&city='+val,
	cache: false,
	success: function(resp){}
	});
}
function saveState(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveState&orderID='+oid+'&state='+val,
	cache: false,
	success: function(resp){}
	});
}
function saveZip(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveZip&orderID='+oid+'&zip='+val,
	cache: false,
	success: function(resp){}
	});
}
function savePh(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=savePh&orderID='+oid+'&ph='+val,
	cache: false,
	success: function(resp){}
	});
}
function savePh2(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=savePh2&orderID='+oid+'&ph2='+val,
	cache: false,
	success: function(resp){}
	});
}
function saveEmail(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveEmail&orderID='+oid+'&email='+val,
	cache: false,
	success: function(resp){}
	});
}
function saveAddr(oid){
	var addr1 = $('#t_addr1').val();
	var addr2 = $('#t_addr2').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveAddr&addr1='+addr1+'&addr2='+addr2+'&orderID='+oid,
	cache: false,
	success: function(resp){}
	});
}
function saveNoteDept(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveNoteDept&val='+val+'&orderID='+oid,
	cache: false,
	success: function(resp){}
	});
}
function saveText(oid,val){
	val = escape(val);
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=saveText&val='+val+'&orderID='+oid,
	cache: false,
	success: function(resp){}
	});
}
function savePN(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=savePN&val='+val+'&orderID='+oid,
	cache: false,
	success: function(resp){}
	});
}
function saveConfirmDate(val,oid){
	if (val){
		$.ajax({
		url: 'ajax-calls.php',
		type: 'post',
		data: 'action=confirmOrder&orderID='+oid,
		cache: false,
		success: function(resp){
			$('#confDateSpan').html('Confirmed '+resp);
		}
		});
	}
	else {
		$.ajax({
		url: 'ajax-calls.php',
		type: 'post',
		data: 'action=unconfirmOrder&orderID='+oid,
		cache: false,
		success: function(resp){
			$('#confDateSpan').html('Not confirmed');
		}
		});
	}
}
function togglePrint(username,oid){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=UpdatePrint&orderID='+oid+'&user='+username,
	cache: false,
	success: function(resp){}
	});
}
function toggleO(oid,tid){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=UpdateItemO&orderID='+oid+'&transID='+tid,
	cache: false,
	success: function(resp){}
	});
}
function toggleA(oid,tid){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=UpdateItemA&orderID='+oid+'&transID='+tid,
	cache: false,
	success: function(resp){}
	});
}
function doSplit(oid,tid){
	var dcheck=false;
	$('select.editDept').each(function(){
		if ($(this).val() == 0){
			dcheck=true;
		}
	});

	if (dcheck){
		alert("Item(s) don't have a department set");
		return false;
	}

	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=SplitOrder&orderID='+oid+'&transID='+tid,
	cache: false,
	success: function(resp){
		$('#itemDiv').html(resp);
	}
	});

}
function validateAndHome(){
	var dcheck=false;
	$('select.editDept').each(function(){
		if ($(this).val() == 0){
			dcheck=true;
		}
	});

	if (dcheck){
		alert("Item(s) don't have a department");
		return false;
	}

	var CtC = $('#ctcselect').val();
	if (CtC == 2){
		alert("Choose Call to Confirm option");
		return false;
	}

	var nD = $('#nDept').val();
	var nT = $('#nText').val();
	if (nT != "" && nD == 0)
		alert("Assign your notes to a department");
	else
		location = $('#redirectURL').val();

	return false;
}
function updateStatus(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=UpdateStatus&orderID='+oid+'&val='+val,
	cache: false,
	success: function(resp){
		$('#statusdate'+oid).html(resp);	
	}
	});
}
