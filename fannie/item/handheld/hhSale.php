<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Community Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);
include('../laneUpdates.php');

if (isset($_REQUEST['submitPC'])){
    $upc = $_REQUEST['upc'];
    $batchID = $_REQUEST['batchID'];
    $price = $_REQUEST['sprice'];

    $prodQ = $dbc->prepare_statement("UPDATE products SET special_price=?
            WHERE upc=?");
    $dbc->exec_statement($prodQ, array($price,$upc));

    $batchQ = $dbc->prepare_statement("UPDATE batchList SET salePrice=?
            WHERE upc=? AND batchID=?");
    $dbc->exec_statement($batchQ,array($price,$upc,$batchID));

    updateProductAllLanes($upc);    
    header("Location: handheld.php?submit=Submit&upc=".$upc);
    return;
}
elseif (isset($_REQUEST['submitUnsale'])){
    $upc = $_REQUEST['upc'];
    $batchID = $_REQUEST['batchID'];
    
    $prodQ = $dbc->prepare_statement("UPDATE products SET special_price=0,
        discounttype=0,start_date='1900-01-01',
        end_date='1900-01-01' WHERE upc=?");
    $dbc->exec_statement($prodQ, array($upc));

    $batchQ = $dbc->prepare_statement("DELETE FROM batchList WHERE
        upc=? AND batchID=?");
    $dbc->exec_statement($batchQ, array($upc,$batchID));

    updateProductAllLanes($upc);    
    header("Location: handheld.php?submit=Submit&upc=".$upc);
    return;
}

?>
<html>
<head><title>Edit Sale Price</title>
<style>
a {
    color:blue;
}
</style>
</head>
<body>
<?php
if (!isset($_REQUEST['upc'])){
    echo "<i>Error: No item</i>";
    return;
}

$upc = BarcodeLib::padUPC(FormLib::get('upc'));

$descQ = $dbc->prepare_statement("SELECT description,discounttype,special_price,start_date,end_date
     FROM products WHERE upc=?");
$descR = $dbc->exec_statement($descQ, array($upc));
$row = $dbc->fetch_row($descR);

if ($row['discounttype'] == 0){
    echo "<i>Error: Item $upc doesn't appear to be one sale</i>";
}

$batchQ = $dbc->prepare_statement("SELECT l.batchID,b.batchName FROM batchList as l
    INNER JOIN batches AS b ON b.batchID=l.batchID
    WHERE l.upc=? AND "
    .$dbc->datediff($dbc->now(),'b.startdate')." >= 0 AND "
    .$dbc->datediff($dbc->now(),'b.enddate')." <= 0 ");
$batchR = $dbc->exec_statement($batchQ, array($upc));
if ($dbc->num_rows($batchR) == 0){
    $lcQ = $dbc->prepare_statement("SELECT likeCode FROM upcLike WHERE upc=?");
    $lcR = $dbc->exec_statement($lcQ, array($upc));
    $lc = $dbc->num_rows($lcR)>0?array_pop($dbc->fetch_row($lcR)):0;

    $batchQ = $dbc->prepare_statement("SELECT l.batchID,b.batchName FROM batchList as l
        INNER JOIN batches AS b ON b.batchID=l.batchID
        WHERE l.upc=? AND "
        .$dbc->datediff($dbc->now(),'b.startdate')." >= 0 AND "
        .$dbc->datediff($dbc->now(),'b.enddate')." <= 0 ");
    $batchR = $dbc->exec_statement($batchQ, array('LC'.$lc));
}

if ($dbc->num_rows($batchR) == 0){
    echo "<i>Error: can't find a batch containing item $upc</i>";
    return;
}

$batchW = $dbc->fetch_row($batchR);

echo "<form action=hhSale.php method=get>";
printf("<span style=\"color:red;\">%s</span> %s<br />",$upc,$row['description']);
printf("<b>Sale Price</b>: \$<input type=text name=sprice size=6 value=%.2f id=sprice />
    <br />",$row['special_price']);
printf("<b>Starts</b>: %s<br /><b>Ends</b>: %s<br />",$row['start_date'],$row['end_date']);
printf("<b>Batch</b>: %s",$batchW['batchName']);
printf("<input type=hidden name=upc value=%s />",$upc);
printf("<input type=hidden name=batchID value=%d /><br />",$batchW['batchID']);

echo "<input type=submit value=\"Change Sale Price\"
    name=submitPC style=\"width:350px;height:40px;font-size:110%;\" />";

echo "<br />";

echo "<input type=submit value=\"Take Off Sale\"
    onclick=\"return confirm('Confirm Sale Removal');\" 
    name=submitUnsale style=\"width:350px;height:40px;font-size:110%;\" />";

echo "<br />";

echo "<input type=submit value=\"Back\"
    name=back style=\"width:350px;height:40px;font-size:110%;\" 
    onclick=\"top.location='handheld.php?submit=Submit&upc=$upc';return false;\"
    />";

echo "</form>";



?>
</body>
</html>
