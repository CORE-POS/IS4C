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

$page_title = "Special Order :: Review";
$header = "Review Special Order";
include($FANNIE_ROOT.'src/header.html');

$orderID = isset($_REQUEST['orderID'])?$_REQUEST['orderID']:'';
if ($orderID === ''){
	echo 'Error: no order specified';
	include($FANNIE_ROOT.'src/footer.html');
	exit;
}
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
	data: 'action=loadCustomer&orderID=<?php echo $orderID; ?>&nonForm=yes',
	cache: false,
	success: function(resp){
		$('#customerDiv').html(resp);
		var oid = $('#orderID').val();
		$.ajax({
		url: 'ajax-calls.php',
		dataType: 'post',
		data: 'action=loadItems&orderID='+oid+'&nonForm=yes',
		cache: false,
		success: function(resp){
			$('#itemDiv').html(resp);
		}
		});
	}
	});

});
</script>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>
