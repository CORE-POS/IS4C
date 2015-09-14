<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 21Mar2013 EL Assign all fields on update, not just price, ppu. OK per AT.
    *               This routine will be replaced by Andy's of March 18 soon.

*/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);

$id = 0;
$upc = $_REQUEST['upc'];
$description = $_REQUEST['description'];
$brand = $_REQUEST['brand'];
$units = $_REQUEST['units'];
if ( $units == '' )
    $units = 'NULL';
$size = $_REQUEST['size'];
$ppo = $_REQUEST['ppo'];
$vendor = $_REQUEST['vendor'];
$sku = $_REQUEST['sku'];
$price = $_REQUEST['price'];
$id = $_REQUEST['subID'];
$count = FormLib::get('count', 1);

$shelftag = new ShelftagsModel($dbc);
$shelftag->id($id);
$shelftag->upc($upc);
$shelftag->normal_price($price);
$shelftag->pricePerUnit($ppo);
$shelftag->description($description);
$shelftag->brand($brand);
$shelftag->sku($sku);
$shelftag->size($size);
$shelftag->units($units);
$shelftag->vendor($vendor);
$shelftag->count($count);
$insR = $shelftag->save();
?>
<!doctype html>
<html>
    <head>
        <title>Add Shelf Tag</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" type="text/css" href="../src/javascript/bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="../src/javascript/bootstrap-default/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="../src/javascript/bootstrap-default/css/bootstrap-theme.min.css">
        <script type="text/javascript" src="../src/javascript/jquery/jquery.min.js"></script>
        <script type="text/javascript" src="../src/javascript/bootstrap/js/bootstrap.min.js"></script>
    </head>
<body>
<?php
if ($insR == false) {
    echo '<div class="alert alert-danger">Error creating tag</div>';
} else {
    echo '<div class="alert alert-success">Created Tag</div>';
}
?>
</body>
</html>
