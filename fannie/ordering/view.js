
$(document).ready(function(){
	var initoid = $('#init_oid').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=loadCustomer&orderID='+initoid,
	cache: false,
	error: function(e1,e2,e3){
		window.alert(e1);
		window.alert(e2);
		window.alert(e3);
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
	var t = [];
	t[7] = "Completed";
	t[8] = "Canceled";
	t[9] = "Inquiry";

	if (window.confirm("Are you sure you want to close this order as "+t[tid]+"?")){
		$.ajax({
		url: 'OrderAjax.php',
		type: 'post',
		data: 'id='+oid+'&close='+tid,
		cache: false,
		success: function(resp){
			//location = 'review.php?orderID='+oid;
			window.location = $('#redirectURL').val();
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
		if ($('#newqty').length) {
			$('#newqty').focus();	
        }
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
function saveDesc(new_desc,tid)
{
    saveByTransID(tid, 'saveDesc', 'desc', new_desc);
}
function savePrice(new_price,tid){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	type: 'post',
	data: 'action=savePrice&orderID='+oid+'&transID='+tid+'&price='+new_price,
	cache: false,
	success: function(resp){
		if ($('#discPercent'+tid).html() !== 'Sale') {
			$('#discPercent'+tid).html(resp+"%");
        }
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
		var fields = resp.split('`');
		$('#srp'+tid).val(fields[1]);
		$('#act'+tid).val(fields[2]);
		if ($('#discPercent'+tid).html() !== 'Sale') {
			$('#discPercent'+tid).html(fields[0]+"%");
        }
	}
	});
}
function saveCtC(val,oid){
    $.ajax({
        url: 'OrderAjax.php',
        type: 'post',
        data: 'id='+oid+'&ctc='+val
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
function saveByOrderID(oid, action, field_name, field_value)
{
    var dstr = 'action=' + action
        + '&orderID=' + oid
        + '&' + field_name + '=' + field_value;
    silentSave(dstr);
}
function saveByTransID(tid, action, field_name, field_value)
{
	var oid = $('#orderID').val();
    var dstr = 'action=' + action
        + '&orderID=' + oid
        + '&transID=' + tid
        + '&' + field_name + '=' + field_value;
    silentSave(dstr);
}
function silentSave(dstr)
{
    $.ajax({
        url: 'ajax-calls.php',
        type: 'post',
        data: dstr,
        success: function(resp){}
    });
}
function saveDept(new_dept,tid)
{
    saveByTransID(tid, 'saveDept', 'dept', new_dept);
}
function saveVendor(new_vendor,tid)
{
    saveByTransID(tid, 'saveVendor', 'vendor', new_vendor);
}
function saveFN(oid,val){
    saveByOrderID(oid, 'saveFN', 'fn', val);
}
function saveLN(oid,val){
    saveByOrderID(oid, 'saveLN', 'ln', val);
}
function saveCity(oid,val){
    saveByOrderID(oid, 'saveCity', 'city', val);
}
function saveState(oid,val){
    saveByOrderID(oid, 'saveState', 'state', val);
}
function saveZip(oid,val){
    saveByOrderID(oid, 'saveZip', 'zip', val);
}
function savePh(oid,val){
    saveByOrderID(oid, 'savePh', 'ph', val);
}
function savePh2(oid,val){
    saveByOrderID(oid, 'savePh2', 'ph2', val);
}
function saveEmail(oid,val){
    saveByOrderID(oid, 'saveEmail', 'email', val);
}
function saveAddr(oid){
	var addr1 = $('#t_addr1').val();
	var addr2 = $('#t_addr2').val();
	var dstr = 'action=saveAddr&addr1='+addr1+'&addr2='+addr2+'&orderID='+oid;
    silentSave(dstr);
}
function saveNoteDept(oid,val){
    saveByOrderID(oid, 'saveNoteDept', 'val', val);
}
function saveText(oid,val){
	val = escape(val);
    saveByOrderID(oid, 'saveText', 'val', val);
}
function savePN(oid,val){
    $.ajax({
        url: 'OrderAjax.php',
        type: 'post',
        data: 'pn='+val+'&id='+oid
    });
}
function saveConfirmDate(val,oid){
	if (val){
		$.ajax({
		url: 'OrderAjax.php',
		type: 'post',
        data: 'id='+oid+'&confirm=1',
		cache: false,
		success: function(resp){
			$('#confDateSpan').html('Confirmed '+resp);
		}
		});
	}
	else {
		$.ajax({
		url: 'OrderAjax.php',
		type: 'post',
        data: 'id='+oid+'&confirm=0',
		cache: false,
		success: function(resp){
			$('#confDateSpan').html('Not confirmed');
		}
		});
	}
}
function togglePrint(username,oid){
    $.ajax({
        url: 'OrderViewPage.php',
        type: 'post',
        data: 'togglePrint=1&orderID='+oid
    });
}
function toggleO(oid,tid){
    $.ajax({
        url: 'OrderViewPage.php',
        type: 'post',
        data: 'toggleMemType=1&orderID='+oid+'&transID='+tid
    });
}
function toggleA(oid,tid){
    $.ajax({
        url: 'OrderViewPage.php',
        type: 'post',
        data: 'toggleStaff=1&orderID='+oid+'&transID='+tid
    });
}
function doSplit(oid,tid){
	var dcheck=false;
	$('select.editDept').each(function(){
		if ($(this).val() === '0'){
			dcheck=true;
		}
	});

	if (dcheck){
		window.alert("Item(s) don't have a department set");
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
		if ($(this).val() === '0'){
			dcheck=true;
		}
	});

	if (dcheck){
		window.alert("Item(s) don't have a department");
		return false;
	}

	var CtC = $('#ctcselect').val();
	if (CtC === '2'){
		window.alert("Choose Call to Confirm option");
		return false;
	}

	var nD = $('#nDept').val();
	var nT = $('#nText').val();
	if (nT !== "" && nD === '0') {
		window.alert("Assign your notes to a department");
		return false;
    }

    if ($('#oStoreID').val() == 0) {
        window.alert('Choose a store');
	} else {
		window.location = $('#redirectURL').val();
    }

	return false;
}
function updateStatus(oid,val){
	$.ajax({
	url: 'OrderAjax.php',
	type: 'post',
    data: 'id='+oid+'&status='+val,
    dataType: 'json',
	cache: false,
	success: function(resp){
		$('#statusdate'+oid).html(resp.tdate);
	}
	});
}
function updateStore(oid,val){
    $.ajax({
        url: 'OrderAjax.php',
        type: 'post',
        data: 'id='+oid+'&store='+val
    });
}
