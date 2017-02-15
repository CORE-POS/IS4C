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
use COREPOS\Fannie\API\lib\Store;
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
$edit = FannieAuth::validateUserQuiet('ordering_edit');
if ((Store::getIdByIp() == 2 || $edit || FannieConfig::config('SO_UI') === 'bootstrap') && count($_GET) === 0) {
    header('Location: OrderViewPage.php');
    return;
}
if (!function_exists('checkLogin')) {
    include($FANNIE_ROOT.'auth/login.php');
}
$dbc = FannieDB::get($FANNIE_OP_DB);

if (!checkLogin()){
    $url = $FANNIE_URL."auth/ui/loginform.php";
    $rd = $FANNIE_URL."ordering/";
    header("Location: $url?redirect=$rd");
    return;
}

if (session_id() == '' && !headers_sent()) {
    session_start();
}

$page_title = "Special Order :: Create";
$header = "Create Special Order";
include($FANNIE_ROOT.'src/header.html');

$orderID = isset($_REQUEST['orderID'])?$_REQUEST['orderID']:'';
$return_path = (isset($_SERVER['HTTP_REFERER']) && strstr($_SERVER['HTTP_REFERER'],'fannie/ordering/clearinghouse.php')) ? $_SERVER['HTTP_REFERER'] : '';
if (!empty($return_path)) $_SESSION['specialOrderRedirect'] = $return_path;
else if (isset($_SESSION['specialOrderRedirect'])) $return_path = $_SESSION['specialOrderRedirect'];
else $return_path = $FANNIE_URL."ordering/";
printf("<input type=hidden id=redirectURL value=\"%s\" />",$return_path);

$prev = -1;
$next = -1;
$found = False;
$cachepath = sys_get_temp_dir()."/ordercache/";
if (isset($_REQUEST['k']) && file_exists($cachepath.$_REQUEST['k'])){
    $fp = fopen($cachepath.$_REQUEST['k'],'r');
    while (($buffer = fgets($fp, 4096)) !== false) {
        if ((int)$buffer == $orderID) $found = True;
        else if (!$found) $prev = (int)$buffer;
        else if ($found) {
            $next = (int)$buffer;
            break;
        }
    }
    fclose($fp);

    echo '<div><div style="float:left;width:48%">';
    if ($prev == -1)
        echo 'Prev';
    else
        printf('<a href="view.php?orderID=%d&k=%s">Prev</a>',$prev,$_REQUEST['k']);
    echo '</div><div style="text-align:right;float:right;width:48%">';
    if ($next == -1)
        echo 'Next';
    else
        printf('<a href="view.php?orderID=%d&k=%s">Next</a>',$next,$_REQUEST['k']);
    echo '</div></div>';
    echo '<div style="clear:both"></div>';
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
<div id="footerDiv"></div>
<script type="text/javascript" src="view.js?date=20160513">
</script>
<?php
printf("<input type=hidden value=\"%d\" id=\"init_oid\" />",$orderID);
include($FANNIE_ROOT.'src/footer.html');

