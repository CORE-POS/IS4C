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
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include('../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

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
<input type="submit" value="Duplicate Order" 
    onclick="copyOrder(<?php echo $orderID; ?>); return false;" />
<fieldset>
<legend>Customer Information</legend>
<div id="customerDiv"></div>
</fieldset>
<fieldset>
<legend>Order Items</legend>
<div id="itemDiv"></div>
</fieldset>
<fieldset>
<legend>Order History</legend>
<div id="historyDiv"></div>
</fieldset>
<script type="text/javascript">
function copyOrder(oid){
    if (confirm("Copy this order?")){
        $.ajax({
        url:'ajax-calls.php',
        type:'post',
        data:'action=copyOrder&orderID='+oid,
        cache: false,
        error: function(e1,e2,e3){
            alert(e1);alert(e2);alert(e3);
        },
        success: function(resp){
            location='view.php?orderID='+resp;
        }
        });
    }
}
$(document).ready(function(){
    $.ajax({
    url: 'ajax-calls.php',
    type: 'post',
    data: 'action=loadCustomer&orderID=<?php echo $orderID; ?>&nonForm=yes',
    cache: false,
    error: function(e1,e2,e3){
        alert(e1);alert(e2);alert(e3);
    },
    success: function(resp){
        $('#customerDiv').html(resp);
        var oid = $('#orderID').val();
        $.ajax({
        url: 'ajax-calls.php',
        type: 'post',
        data: 'action=loadItems&orderID='+oid+'&nonForm=yes',
        cache: false,
        success: function(resp){
            $('#itemDiv').html(resp);
        }
        });
        $.ajax({
            url: 'ajax-calls.php',
            type: 'post',
            data: 'action=loadHistory&orderID='+oid,
            cache: false,
            success: function(resp){
                $('#historyDiv').html(resp);
            }
        });
    }
    });

});
</script>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>
