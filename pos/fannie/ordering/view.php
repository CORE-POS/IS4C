<?php
include('../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

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
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=newUPC&orderID='+oid+'&memNum='+cardno+'&upc='+upc,
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
function saveText(oid,val){
	val = escape(val);
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=saveText&val='+val+'&orderID='+oid,
	cache: false,
	success: function(resp){alert(resp);}
	});
}
</script>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>
