<?php
/*******************************************************************************

    Copyright 2005,2009 Whole Foods Community Co-op

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
require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);

include($FANNIE_ROOT.'auth/login.php');
$name = checkLogin();
if (!$name){
    header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}item/scaleDelete.php");
    exit;
}
$user = validateUserQuiet('delete_items');
if (!$user){
    echo "Not allowed";
    exit;
}

$page_title = 'Fannie - Item Maintenance';
$header = 'Item Maintenance';
include('../src/header.html');
?>
<script type"text/javascript" src=ajax.js></script>

<?php

echo "<h1 style=\"color:red;\">Delete Scale PLU Tool</h1>";

if (isset($_REQUEST['upc']) && !isset($_REQUEST['deny'])){
    $upc = BarcodeLib::padUPC(FormLib::get('upc'));
    
    if (isset($_REQUEST['submit'])){
        $p = $dbc->prepare_statement("SELECT * FROM scaleItems WHERE plu=?");
        $rp = $dbc->exec_statement($p,array($upc));
        if ($dbc->num_rows($rp) == 0){
            printf("No item found for <b>%s</b><p />",$upc);
            echo "<a href=\"scaleDelete.php\">Go back</a>";
        }
        else {
            $rw = $dbc->fetch_row($rp);
            echo "<form action=scaleDelete.php method=post>";
            echo "<b>Delete this item?</b><br />";
            echo "<table cellpadding=4 cellspacing=0 border=1>";
            echo "<tr><th>UPC</th><th>Description</th><th>Price</th></tr>";
            printf("<tr><td><a href=\"itemMain.php?upc=%s\" target=\"_new%s\">
                %s</a></td><td>%s</td><td>%.2f</td></tr>",$rw['plu'],
                $rw['plu'],$rw['plu'],$rw['itemdesc'],$rw['price']);
            echo "</table><br />";
            printf("<input type=hidden name=upc value=\"%s\" />",$upc);
            echo "<input type=submit name=confirm value=\"Yes, delete this item\" />";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
            echo "<input type=submit name=deny value=\"No, keep this item\" />";
        }
    }
    else if (isset($_REQUEST['confirm'])){
        $plu = substr($upc,3,4);
        $p = $dbc->prepare_statement("DELETE FROM scaleItems WHERE plu=?");
        $rp = $dbc->exec_statement($p,array($upc));
        include('hobartcsv/parse.php');
        deleteitem($plu);

        include('laneUpdates.php');
        printf("Item %s has been deleted<br /><br />",$upc);
        echo "<a href=\"scaleDelete.php\">Delete another item</a>";
    }
}else{
    echo "<form action=scaleDelete.php method=post>";
    echo "<input name=upc type=text id=upc> Enter UPC/PLU here<br><br>";

    echo "<input name=submit type=submit value=submit>";
    echo "</form>";
    echo "<script type=\"text/javascript\">
        \$(document).ready(function(){ \$('#upc').focus(); });
        </script>";
}

include ('../src/footer.html');

?>
