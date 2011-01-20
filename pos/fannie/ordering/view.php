<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include('../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

include($FANNIE_ROOT.'auth/login.php');
if (!checkLogin()){
	$url = $FANNIE_URL."auth/ui/loginform.php";
	$rd = $FANNIE_URL."ordering/";
	header("Location: $url?redirect=$rd");
	exit;
}

$page_title = "Special Order :: Create";
$header = "Create Special Order";
include($FANNIE_ROOT.'src/header.html');

$orderID = isset($_REQUEST['orderID'])?$_REQUEST['orderID']:'';
?>
<fieldset>
<legend>Customer Information</legend>
<div id="customerDiv"></div>
</fieldset>
<fieldset>
<legend>Order Items</legend>
<div id="itemDiv"></div>
</fieldset>
<script type="text/javascript">
$(document).ready(function(){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=loadCustomer&orderID=<?php echo $orderID; ?>',
	cache: false,
	success: function(resp){
		$('#customerDiv').html(resp);
		var oid = $('#orderID').val();
		$.ajax({
		url: 'ajax-calls.php',
		dataType: 'post',
		data: 'action=loadItems&orderID='+oid,
		cache: false,
		success: function(resp){
			$('#itemDiv').html(resp);
		}
		});
	}
	});

});
function confirmC(oid,tid){
	var t = new Array();
	t[7] = "Completed";
	t[8] = "Canceled";
	t[9] = "Inquiry";

	if (confirm("Are you sure you want to close this order as "+t[tid]+"?")){
		$.ajax({
		url: 'ajax-calls.php',
		dataType: 'post',
		data: 'action=closeOrder&orderID='+oid+'&status='+tid,
		cache: false,
		success: function(resp){
			location = 'review.php?orderID='+oid;
		}
		});
	}
}
function memNumEntered(){
	var oid = $('#orderID').val();
	var cardno = $('#memNum').val();	
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=reloadMem&orderID='+oid+'&memNum='+cardno,
	cache: false,
	success: function(resp){
		$('#customerDiv').html(resp);
	}
	});
}

function addUPC(){
	var oid = $('#orderID').val();
	var cardno = $('#memNum').val();
	var upc = $('#newupc').val();
	var qty = $('#newcases').val();
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=newUPC&orderID='+oid+'&memNum='+cardno+'&upc='+upc+'&cases='+qty,
	cache: false,
	success: function(resp){
		$('#itemDiv').html(resp);
	}
	});
	
}
function deleteUPC(orderID,upc){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=deleteUPC&orderID='+orderID+'&upc='+upc,
	cache: false,
	success: function(resp){
		$('#itemDiv').html(resp);
	}
	});
}
function saveDesc(new_desc,upc){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveDesc&orderID='+oid+'&upc='+upc+'&desc='+new_desc,
	cache: false,
	success: function(resp){
	}
	});
}
function savePrice(new_price,upc){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=savePrice&orderID='+oid+'&upc='+upc+'&price='+new_price,
	cache: false,
	success: function(resp){
		if ($('#discPercent'+upc).html() != 'Sale')
			$('#discPercent'+upc).html(resp+"%");
	}
	});
}
function saveSRP(new_price,upc){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveSRP&orderID='+oid+'&upc='+upc+'&srp='+new_price,
	cache: false,
	success: function(resp){
		if ($('#discPercent').html() != 'Sale')
			$('#discPercent').html(resp+"%");
	}
	});
}
function saveCtC(val,oid){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveCtC&orderID='+oid+'&val='+val,
	cache: false,
	success: function(resp){
	}
	});
}
function saveQty(new_qty,upc){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveQty&orderID='+oid+'&upc='+upc+'&qty='+new_qty,
	cache: false,
	success: function(resp){
	}
	});
}
function saveDept(new_dept,upc){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveDept&orderID='+oid+'&upc='+upc+'&dept='+new_dept,
	cache: false,
	success: function(resp){
	}
	});
}
function saveVendor(new_vendor,upc){
	var oid = $('#orderID').val();
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveVendor&orderID='+oid+'&upc='+upc+'&vendor='+new_vendor,
	cache: false,
	success: function(resp){
	}
	});
}
function saveFN(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveFN&orderID='+oid+'&fn='+val,
	cache: false,
	success: function(resp){}
	});
}
function saveLN(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveLN&orderID='+oid+'&ln='+val,
	cache: false,
	success: function(resp){}
	});
}
function saveCity(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveCity&orderID='+oid+'&city='+val,
	cache: false,
	success: function(resp){}
	});
}
function saveState(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveState&orderID='+oid+'&state='+val,
	cache: false,
	success: function(resp){}
	});
}
function saveZip(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveZip&orderID='+oid+'&zip='+val,
	cache: false,
	success: function(resp){}
	});
}
function savePh(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=savePh&orderID='+oid+'&ph='+val,
	cache: false,
	success: function(resp){}
	});
}
function savePh2(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=savePh2&orderID='+oid+'&ph2='+val,
	cache: false,
	success: function(resp){}
	});
}
function saveEmail(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
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
	dataType: 'post',
	data: 'action=saveAddr&addr1='+addr1+'&addr2='+addr2+'&orderID='+oid,
	cache: false,
	success: function(resp){}
	});
}
function saveNoteDept(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveNoteDept&val='+val+'&orderID='+oid,
	cache: false,
	success: function(resp){}
	});
}
function saveText(oid,val){
	val = escape(val);
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveText&val='+val+'&orderID='+oid,
	cache: false,
	success: function(resp){}
	});
}
function savePN(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=savePN&val='+val+'&orderID='+oid,
	cache: false,
	success: function(resp){}
	});
}
function saveConfirmDate(val,oid){
	if (val){
		$.ajax({
		url: 'ajax-calls.php',
		dataType: 'post',
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
		dataType: 'post',
		data: 'action=unconfirmOrder&orderID='+oid,
		cache: false,
		success: function(resp){
			$('#confDateSpan').html('Not confirmed');
		}
		});
	}
}
function validateAndHome(){
	var nD = $('#nDept').val();
	var nT = $('#nText').val();
	if (nT != "" && nD == 0)
		alert("Assign your notes to a department");
	else
		location = 'index.php';
}
</script>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>
