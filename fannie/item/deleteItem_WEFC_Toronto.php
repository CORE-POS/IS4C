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

/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * Modes:
 * + If called directly: Prompt for upc to delete.
 * + If called from here or another script before confirmation: Ask for confirmation.
 * + If called from here after confirmation: Perform deletion and report upshot.
*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * 10Mar13 EL Add deletion from productUser and products_WEFC_Toronto
 *            Do not delete from vendorItems.
 *            Fixed link from confirmation dialog to editor.
 *            Use multi-table deletion option for lanes.
 *            Use standard next-operation prompt from prodFunction post-deletion.
*/

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
    header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}item/deleteItem_WEFC_Toronto.php");
    exit;
}
$user = validateUserQuiet('delete_items');
if (!$user){
    echo "Not allowed";
    exit;
}

include('prodFunction_WEFC_Toronto.php');
$page_title = 'Fannie - Item Maintenance WEFC_Toronto';
$header = 'Item Maintenance WEFC_Toronto';
include('../src/header.html');
?>
<script type"text/javascript" src=ajax.js></script>

<?php

echo "<h1 style=\"color:red;\">Delete Product Tool</h1>";

if (isset($_REQUEST['upc']) && !isset($_REQUEST['deny'])){
    $upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);

    if (isset($_REQUEST['submit'])){
        $rp = $dbc->query(sprintf("SELECT * FROM products WHERE upc=%s",$dbc->escape($upc)));
        if ($dbc->num_rows($rp) == 0){
            printf("No item found for <b>%s</b><p />",$upc);
            echo "<a href=\"deleteItem_WEFC_Toronto.php\">Go back</a>";
        }
        else {
            $rw = $dbc->fetch_row($rp);
            echo "<form action=deleteItem_WEFC_Toronto.php method=post>";
            echo "<b>Delete this item?</b><br />";
            echo "<table cellpadding=4 cellspacing=0 border=1>";
            echo "<tr><th>UPC</th><th>Description</th><th>Price</th></tr>";
            printf("<tr><td><a href='itemMaint_WEFC_Toronto.php?upc=%s' target='_new%s' title='Display details in another tab/window'>
                %s</a></td><td>%s</td><td>%.2f</td></tr>",$rw['upc'],
                $rw['upc'],$rw['upc'],$rw['description'],$rw['normal_price']);
            echo "</table><br />";
            printf("<input type=hidden name=upc value=\"%s\" />",$upc);
            echo "<input type=submit name=confirm value=\"Yes, delete this item\" />";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
            echo "<input type=submit name=deny value=\"No, keep this item\" />";
        }
    }
    else if (isset($_REQUEST['confirm'])){
        $plu = substr($upc,3,4);
        $upc = $dbc->escape($upc);
        $delQ = sprintf("DELETE FROM products WHERE upc=%s",$upc);
        $dbc->query($delQ);
        $delxQ = sprintf("DELETE FROM prodExtra WHERE upc=%s",$upc);
        $dbc->query($delxQ);
        if ($dbc->table_exists("scaleItems")){
            $scaleQ = sprintf("DELETE FROM scaleItems WHERE plu=%s",$upc);
            $dbc->query($scaleQ);
            include('hobartcsv/parse.php');
            deleteitem($plu);
        }
        $lane_tables = array("products");
        if ($dbc->table_exists("productUser")){
            $deluQ = sprintf("DELETE FROM productUser WHERE upc=%s",$upc);
            $dbc->query($deluQ);
            $lane_tables[] = "productUser";
        }

        /* For WEFC_Toronto only
        */
        if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == "WEFC_Toronto" ) {
            $table_name = "products_{$FANNIE_COOP_ID}";
            if ($dbc->table_exists("$table_name")){
                $deluQ = sprintf("DELETE FROM $table_name WHERE upc=%s",$upc);
                $dbc->query($deluQ);
            }
        }

        include('laneUpdates_WEFC_Toronto.php');
        deleteAllLanes(str_replace("'","",$upc), $lane_tables);
        //For table products only.
        //deleteProductAllLanes(str_replace("'","",$upc));

        printf("Item %s has been deleted<br />",$upc);
        echo "<a href=\"deleteItem_WEFC_Toronto.php\">Delete another item</a>";
        // General maintenance form.
        echo "<hr>";
        echo "<form action='itemMaint_WEFC_Toronto.php' method=post>";
                echo promptForUPC();
        echo "</form>";
    }
}else{
        echo "<form action=deleteItem_WEFC_Toronto.php method=post>";
        echo "<input name=upc type=text id=upc> Enter UPC/PLU to delete here<br><br>";
        echo "<input name=submit type=submit value=submit>";
        echo "</form>";
        echo "<hr>";
        echo "<form action='itemMaint_WEFC_Toronto.php' method=post>";

            echo promptForUPC();

            echo "</form>";

    echo "<script type=\"text/javascript\">
        \$(document).ready(function(){ \$('#upc').focus(); });
        </script>";
}

include ('../src/footer.html');

?>
