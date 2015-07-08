
$(document).ready(function(){
	var initoid = $('#init_oid').val();
	$.ajax({
	type: 'get',
	data: 'customer=1&orderID='+initoid,
    dataType: 'json',
	cache: false,
	error: function(e1,e2,e3){
		alert(e1);
		alert(e2);
		alert(e3);
	},
	success: function(resp){
        if (resp.customer) {
            $('#customerDiv').html(resp.customer);
            $('.contact-field').change(saveContactInfo);
        }
        if (resp.footer) {
            $('#footerDiv').html(resp.footer);
        }
		var oid = $('#orderID').val();
		$.ajax({
		type: 'get',
		data: 'items=1&orderID='+oid,
		cache: false,
		success: function(resp){
			$('#itemDiv').html(resp);
            $('.item-field').change(saveItem);
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

function saveContactInfo()
{
    var dstr = $('.contact-field').serialize();
    dstr += '&orderID='+$('#orderID').val();
    $.ajax({
        type: 'post',
        data: dstr,
        dataType: 'json',
        success: function(resp) {
            console.log(resp);
        }
    });
}

function saveItem()
{
    var dstr = $(this).closest('tbody').find('.item-field').serialize();
    dstr += '&orderID='+$('#orderID').val();
    dstr += '&changed='+$(this).attr('name');
    console.log(dstr);
}


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
	type: 'get',
	data: 'customer=1&orderID='+oid+'&memNum='+cardno,
    dataType: 'json',
	cache: false,
	success: function(resp){
        if (resp.customer) {
            $('#customerDiv').html(resp.customer);
            $('.contact-field').change(saveContactInfo);
        }
        if (resp.footer) {
            $('#footerDiv').html(footer);
        }
	}
	});
}

function searchWindow(){
	window.open('search.php','Search',
		'width=350,height=400,status=0,toolbar=0,scrollbars=1');
}

function addUPC()
{
	var oid = $('#orderID').val();
	var cardno = $('#memNum').val();
	var upc = $('#newupc').val();
	var qty = $('#newcases').val();
	$.ajax({
	type: 'post',
	data: 'orderID='+oid+'&memNum='+cardno+'&upc='+upc+'&cases='+qty,
	cache: false,
	success: function(resp){
		$('#itemDiv').html(resp);
        $('.item-field').change(saveItem);
		if ($('#newqty').length)
			$('#newqty').focus();	
	}
	});
}
function deleteID(orderID,transID)
{
	$.ajax({
	data: '_method=delete&orderID='+orderID+'&transID='+transID,
	cache: false,
	success: function(resp){
		$('#itemDiv').html(resp);
        $('.item-field').change(saveItem);
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
        $('.item-field').change(saveItem);
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
		if ($('#discPercent'+tid).html() != 'Sale')
			$('#discPercent'+tid).html(resp+"%");
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
	type: 'post',
	data: 'orderID='+oid+'&transID='+tid+'&qty='+qty,
	cache: false,
	success: function(resp){
		$('#itemDiv').html(resp);
        $('.item-field').change(saveItem);
	}
	});
}
function newDept(oid,tid){
	var d = $('#newdept').val();
	$.ajax({
	type: 'post',
	data: 'orderID='+oid+'&transID='+tid+'&dept='+d,
	cache: false,
	success: function(resp){
		$('#itemDiv').html(resp);
        $('.item-field').change(saveItem);
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
function togglePrint(username,oid)
{
	$.ajax({
	dataType: 'post',
	data: 'togglePrint=1&orderID='+oid+'&user='+username,
	cache: false,
	success: function(resp){}
	});
}
function toggleO(oid,tid)
{
	$.ajax({
	dataType: 'post',
	data: 'toggleMemType=1&orderID='+oid+'&transID='+tid,
	cache: false,
	success: function(resp){}
	});
}
function toggleA(oid,tid)
{
	$.ajax({
	dataType: 'post',
	data: 'toggleStaff=1&orderID='+oid+'&transID='+tid,
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
        $('.item-field').change(saveItem);
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
