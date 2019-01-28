<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}
if (!function_exists('checkLogin')) {
    include(__DIR__ . '/../auth/login.php');
}
$dbc = FannieDB::get($FANNIE_OP_DB);

if (!checkLogin()){
    $url = $FANNIE_URL."auth/ui/loginform.php";
    $rd = $FANNIE_URL."ordering/";
    header("Location: $url?redirect=$rd");
    return;
}

$page_title = "Special Order :: Review";
$header = "Review Special Order";
include(__DIR__ . '/../src/header.html');

$orderID = isset($_REQUEST['orderID'])?$_REQUEST['orderID']:'';
if ($orderID === ''){
    echo 'Error: no order specified';
    include(__DIR__ . '/../src/footer.html');
    return;
}
$dbc = FannieDB::get($FANNIE_TRANS_DB);
$orderP = $dbc->prepare('SELECT * FROM ' . FannieDB::fqn('SpecialOrders', 'trans') . ' WHERE specialOrderID=?');
$order = $dbc->getRow($orderP, array($orderID));
$nodupe = '';
$checked = '';
if ($order['noDuplicate']) {
    $nodupe = 'disabled title="This order cannot be duplicated"';
    $checked = 'checked';
}
$new = 'OrderReviewPage.php';
if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) {
    $new .= '?' . $_SERVER['QUERY_STRING'];
}
?>
<div style="text-align: center; background: #00aa00;" class="alert alert-info"><a style="color:#fff" href="<?php echo $new; ?>">Newer Version</a></div>
    <input type="submit" value="Duplicate Order" <?php echo $nodupe; ?>
    onclick="copyOrder(<?php echo $orderID; ?>); return false;" />
    &nbsp;&nbsp;&nbsp;&nbsp;
    <input type="checkbox" disabled <?php echo $checked; ?> />
    Duplication disabled for this order
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
include(__DIR__ . '/../src/footer.html');

