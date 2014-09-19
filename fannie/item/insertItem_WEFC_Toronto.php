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
 * These lines grepped in from this listing: !!grep "^/. [0-9]*\." %
*/
/* 1. Insert or update coop-specific product data */
/* 2. Replace likecode.  */
/* 3. Insert or update productUser */
/* 4. Insert or update vendorItems */
/* 5. Insert to prodExtra */
/* 6. Insert to prodUpdate, an audit table. */
/* 7. Insert to scaleItem */
/* 8. Delete and re-add to product-related tables on the lanes. */
/* 9. Display the post-update values and an input for the next edit. */
/* 10. If requested on the capture form, pop a window for making a shelf tag. */

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
*/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$Fannie_Item_Modules = array("Operations","ExtraInfo",
"ThreeForDollar",
"Cost","Sale","Margin", "LikeCode", "LaneStatus");
include('prodFunction_WEFC_Toronto.php');
// include's SQLManager.php which contains smart_*()
$dbc = FannieDB::get($FANNIE_OP_DB);

include_once('../auth/login.php');
$validatedUser = validateUserQuiet('pricechange');
$auditedUser = validateUserQuiet('audited_pricechange');
$logged_in = checkLogin();
refreshSession();

$page_title = 'Fannie - Item Maintenance WEFC_Toronto';
$header = 'Item Maintenance WEFC_Toronto';
include('../src/header.html');

$upc = str_pad($_REQUEST['upc'],'0',13,STR_PAD_LEFT);

// Array where keys match products fieldnames, for insertion to products.
$ins_array = array();
$ins_array['upc'] = $dbc->escape($upc);
//$ins_array['upc'] = $dbc->escape($_REQUEST['upc']);
$ins_array['tax'] = isset($_REQUEST['tax'])?$_REQUEST['tax']:0;
$ins_array['foodstamp'] = isset($_REQUEST['FS'])?1:0;
$ins_array['scale'] = isset($_REQUEST['Scale'])?1:0;
$ins_array['deposit'] = isset($_REQUEST['deposit'])?$_REQUEST['deposit']:0;
$ins_array['qttyEnforced'] = isset($_REQUEST['QtyFrc'])?1:0;
$ins_array['discount'] = isset($_REQUEST['NoDisc'])?0:1;
$ins_array['normal_price'] = saveAsMoney($_REQUEST,'price');
$ins_array['description'] = $dbc->escape($_REQUEST['descript']);
// Package
$string_size = substr(trim($_REQUEST['size']),0,9);
$numeric_size = (is_numeric($string_size))?$string_size:0;
$ins_array['size'] = $dbc->escape($string_size);
$unitofmeasure = substr(trim($_REQUEST['unitofmeasure']),0,15);
$ins_array['unitofmeasure'] = $dbc->escape($unitofmeasure);

/* set tax and FS to department defaults */
/* But these fields can be edited on the form. Good to override? */
$deptSub = 0;
$taxfsQ = "select dept_tax,dept_fs,
    dept_discount,
    superID FROM
    departments as d left join MasterSuperDepts as s on d.dept_no=s.dept_ID";
$taxfsR = $dbc->query($taxfsQ);
if ($dbc->num_rows($taxfsR) > 0){
    $taxfsW = $dbc->fetch_array($taxfsR);
    $ins_array['tax'] = $taxfsW['dept_tax'];
    $ins_array['foodstamp'] = $taxfsW['dept_fs'];
    $ins_array['discount'] = $taxfsW['dept_discount'];
    $deptSub = $taxfsW['superID'];
}

// Authenticate now that deptSub can be reported.
/* AUTHENTICATION CLASS: pricechange OR audited_pricechange
 * Check which uid is trying to add an item. Users w/ pricechange
 * permission may have access to all items or only a range of
 * subdepartments.
 * Audited users can edit all items, but notifications are
 * generated immediately
 */
if (!$validatedUser && !$auditedUser && $logged_in){
  $validatedUser = validateUserQuiet('pricechange',$deptSub);
}
$uid = 0;
if ($validatedUser){
  $validatedUID = getUID($validatedUser);
  $uid = $validatedUID;
}
elseif ($auditedUser){
  $auditedUID = getUID($auditedUser);
  $uid = $auditedUID;
  require('audit.php');
  if (!empty($likeCode))
    audit($deptSub,$auditedUser,$upc,$descript,$price,$tax,$FS,$Scale,$NoDisc,$likeCode);
  else
    audit($deptSub,$auditedUser,$upc,$descript,$price,$tax,$FS,$Scale,$NoDisc);
}
if (!$validatedUser && !$auditedUser){
    echo "Please ";
    echo "<a href={$FANNIE_URL}auth/ui/loginform.php?redirect=/queries/productTest.php?upc=$upc>";
    echo "login</a> to add new items";
    return;
}

/* 1. Insert or update coop-specific product data */
/* For WEFC_Toronto only
 * Store the raw versions of products.description and productUser.description in products_WEFC_Toronto.
*/
if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == "WEFC_Toronto" ) {
    $table_name = "products_{$FANNIE_COOP_ID}";
    if ($dbc->table_exists("$table_name")){
        $del99Q = "DELETE FROM $table_name WHERE upc = '$upc'";
        $delISR = $dbc->query($del99Q);
        $coop_array = array("description" => $dbc->escape(substr($_REQUEST['descript'],0,255)),
        "search_description" => isset($_REQUEST['puser_description'])?$dbc->escape(substr($_REQUEST['puser_description'],0,255)):'');
        $coop_array['upc'] = $dbc->escape($upc);
        $dbc->smart_insert("$table_name",$coop_array);
    }
}

$package = "";
$sizing = "";
if ( $string_size != "" && $unitofmeasure != "" ) {
    $package = " $string_size$unitofmeasure";
    $sizing = "$string_size $unitofmeasure";
}

// Compose products.description and productUser.description if wanted.
if ( isset($FANNIE_COMPOSE_PRODUCT_DESCRIPTION) && $FANNIE_COMPOSE_PRODUCT_DESCRIPTION == "1" ) {
    $plen = strlen($package);
    $descp = $_REQUEST['descript'];
    $dlen = strlen($descp);
    $MAX_DESC_LEN = 30;
    $wlen = ($dlen + $plen);

    if ( $wlen <= $MAX_DESC_LEN ) {
        $ins_array['description'] = $dbc->escape($descp . $package);
    } else {
        $ins_array['description'] = $dbc->escape(substr($descp,0,($dlen - ($wlen - $MAX_DESC_LEN))) . $package);
    }
}
if ( isset($FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION) && $FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION == "1" ) {
    $puser_description = strtoupper($_REQUEST['manufacturer']) . ' | ' . $_REQUEST['puser_description'] . ' | ' . $package;
    $puser_description = substr($puser_description, 0, 255);
} else {
    $puser_description = substr($_REQUEST['puser_description'],0,255);
}

// Having passed authentication OK to delete both HQ and other-store records.
$del99Q = "DELETE FROM products WHERE upc = '$upc'";
$delISR = $dbc->query($del99Q);

$ins_array['department'] = $_REQUEST['department'];
$ins_array['subdept'] = $_REQUEST['subdepartment'];

$ins_array['cost'] = saveAsMoney($_REQUEST,'cost');

// ThreeForDollar fields
// 3 for $1 from fieldset
if ( array_search('ThreeForDollar',$Fannie_Item_Modules) !== False ) {
    $ins_array['pricemethod'] = is_numeric($_REQUEST['pricemethod'])?$_REQUEST['pricemethod']:0;
    $ins_array['groupprice'] = saveAsMoney($_REQUEST,'groupprice');
    //$ins_array['groupprice'] = is_numeric($_REQUEST['groupprice'])?$_REQUEST['groupprice']:0.00;
    $ins_array['quantity'] = is_numeric($_REQUEST['quantity'])?$_REQUEST['quantity']:0;
    // 22Feb13 Default to '0' seems odd.
    $ins_array['mixmatchcode'] = !empty($_REQUEST['mixmatchcode'])?$dbc->escape($_REQUEST['mixmatchcode']):"'0'";
} else {
    $ins_array['pricemethod'] = 0;
    $ins_array['groupprice'] = 0.00;
    $ins_array['quantity'] = 0;
    $ins_array['mixmatchcode'] = "'0'";
}

// Sale fields
if ( array_search('Sale',$Fannie_Item_Modules) !== False ) {
    $ins_array['special_price'] = saveAsMoney($_REQUEST,'special_price');
    // 0=not on sale; 1=on sale to all; 2=on sale to members
    $ins_array['discounttype'] = is_numeric($_REQUEST['discounttype'])?$_REQUEST['discounttype']:0;
    $ins_array['start_date'] = isset($_REQUEST['start_date'])?$dbc->escape($_REQUEST['start_date']):"'1900-01-01'";
    $ins_array['end_date'] = isset($_REQUEST['end_date'])?$dbc->escape($_REQUEST['end_date']):"'1900-01-01'";
    // Sale 3for$1
    $ins_array['specialpricemethod'] = is_numeric($_REQUEST['specialpricemethod'])?$_REQUEST['specialpricemethod']:0;
    $ins_array['specialgroupprice'] = saveAsMoney($_REQUEST,'specialgroupprice');
    //$ins_array['specialgroupprice'] = is_numeric($_REQUEST['specialgroupprice'])?$_REQUEST['specialgroupprice']:0.00;
    $ins_array['specialquantity'] = is_numeric($_REQUEST['specialquantity'])?$_REQUEST['specialquantity']:0;
} else {
    $ins_array['special_price'] = 0.00;
    $ins_array['discounttype'] = 0;
    $ins_array['start_date'] = "'1900-01-01'";
    $ins_array['end_date'] = "'1900-01-01'";
    $ins_array['specialpricemethod'] = 0;
    $ins_array['specialgroupprice'] = 0.00;
    $ins_array['specialquantity'] = 0;
}

// Odds & ends
$ins_array['local'] = isset($_REQUEST['local'])?1:0;
$ins_array['inUse'] = isset($_REQUEST['inUse'])?1:0;
$ins_array['modified'] = $dbc->now();
//  2Mar13 Not enterable in the form but used in the system.
// hidden on form? Done this way on update.
$ins_array['store_id'] = isset($_REQUEST['store_id'])?$_REQUEST['store_id']:0;
//  2Mar13 Not enterable in the form and not used in the system.
$ins_array['scaleprice'] = 0.00;
$ins_array['advertised'] = 1;
$ins_array['tareweight'] = 0;
$ins_array['wicable'] = 0;
$ins_array['idEnforced'] = 0;
$ins_array['numflag'] = 0;

/* since the item doesn't exist at all, just insert a master record */
$resultI = $dbc->smart_insert('products',$ins_array);
/* if we do persistent per-store records
if ($FANNIE_STORE_ID != 0){
    $ins_array['store_id'] = $FANNIE_STORE_ID;
    $resultI = $dbc->smart_insert('products',$ins_array);
}
*/

/* 2. Replace likecode.  */
// 2Mar13 moved after products insert.
if (isset($_REQUEST['likeCode']) && $_REQUEST['likeCode'] != -1){
    $dbc->query("DELETE FROM upcLike WHERE upc='$upc'");
    $lcQ = "INSERT INTO upcLike (upc,likeCode) VALUES ('$upc',{$_REQUEST['likeCode']})";
    $dbc->query($lcQ);
}

/* 3. Insert or update productUser */
if ($dbc->table_exists('productUser')){
    $puser_array = array();
    $puser_array['brand'] = $dbc->escape($_REQUEST['manufacturer']);
    $puser_array['description'] = $dbc->escape($puser_description);
    $puser_array['sizing'] = $dbc->escape($sizing);
    $puser_array['upc'] = $dbc->escape($upc);
    // Some productUser fields are not edited in itemMaint.php
    $puser_array['enableOnline'] = 0;
    $dbc->query("DELETE FROM productUser WHERE upc='$upc'");
    $dbc->smart_insert('productUser',$puser_array);
}

/* 4. Insert or update vendorItems */
if ($dbc->table_exists('vendorItems')){
    $vi_array = array();
    $vi_array['sku'] = $dbc->escape(substr($_REQUEST['sku'], 0, 10));
    $vi_array['brand'] = $dbc->escape(substr($_REQUEST['manufacturer'], 0, 50));
    $vi_array['description'] = $dbc->escape(substr($_REQUEST['descript'], 0, 50));

    $vi_array['cost'] = saveAsMoney($_REQUEST,'case_cost');
    $vi_array['size'] = $dbc->escape($sizing);
    if ( is_numeric($_REQUEST['case_quantity']) && $_REQUEST['case_quantity'] > 0 )
        $vi_array['units'] = $_REQUEST['case_quantity'];
    else
        $vi_array['units'] = 'NULL';
    // Several vendorItems fields are not edited in itemMaint.php
    // vendorItems is the only table where a record for the upc may legitimately already exist,
    //  i.e. do not delete an existing record.
    $checkR = $dbc->query("SELECT upc FROM vendorItems WHERE upc='$upc'");
    if ($dbc->num_rows($checkR) == 0){
        $vi_array['upc'] = $dbc->escape($upc);
        // vendorID
        $checkR = $dbc->query("SELECT vendorID FROM vendorItems WHERE brand={$vi_array['brand']}");
        if ( $dbc->num_rows($checkR) > 0 ) {
            $vi_row = $dbc->fetch_row($checkR);
            $vi_array['vendorID'] = $vi_row['vendorID'];
        }
        else {
            $vi_array['vendorID'] = 0;
        }
        $dbc->smart_insert('vendorItems',$vi_array);
    }
    else {
        $dbc->smart_update('vendorItems',$vi_array,"upc='$upc'");
    }
// vendorItems
}

/* 5. Insert to prodExtra */
if ($dbc->table_exists('prodExtra')){
    $px_array = array(
    'upc' => $dbc->escape($upc),
    'distributor' => $dbc->escape($_REQUEST['distributor']),
    'manufacturer' => $dbc->escape($_REQUEST['manufacturer']),
    'variable_pricing' => 0,
    'location' => $dbc->escape($_REQUEST['location']),
    'case_info' => "''"
    );
    if ( array_search('Cost',$Fannie_Item_Modules) !== False ) {
        $px_array['cost'] = saveAsMoney($_REQUEST,'cost');
        //$px_array['cost'] = is_numeric($_REQUEST['cost'])?sprintf("%.2f",$_REQUEST['cost']):0.00;
        $px_array['case_cost'] = saveAsMoney($_REQUEST,'case_cost');
        //$px_array['case_cost'] = is_numeric($_REQUEST['case_cost'])?sprintf("%.2f",$_REQUEST['case_cost']):0;
        $px_array['case_quantity'] = is_numeric($_REQUEST['case_quantity'])?$dbc->escape($_REQUEST['case_quantity']):"''";
        if ( $ins_array['cost'] != 0 && $ins_array['normal_price'] != 0 ) {
            $px_array['margin'] = sprintf("%.2f", 1 -($ins_array['cost'] / $ins_array['normal_price']));
        } else {
            $px_array['margin'] = 0.00;
        }
    } else {
        $px_array['cost'] = 0.00;
        $px_array['case_quantity'] = "''";
        $px_array['case_cost'] = 0.00;
        $px_array['margin'] = 0.00;
    }
    $dbc->query("DELETE FROM prodExtra WHERE upc='$upc'");
    $dbc->smart_insert('prodExtra',$px_array);
}

/* 6. Insert to prodUpdate, an audit table. */
if ($dbc->table_exists("prodUpdate")){
    $pu_array = array(
    'upc' => $dbc->escape($upc),
    'description' => $ins_array['description'],
    'price' => $ins_array['normal_price'],
    'dept' => $ins_array['department'],
    'tax' => $ins_array['tax'],
    'fs' => $ins_array['foodstamp'],
    'scale' => $ins_array['scale'],
    'likeCode' => isset($_REQUEST['likeCode'])?$_REQUEST['likeCode']:0,
    'modified' => $dbc->now(),
    'user' => $uid,
    'forceQty' => $ins_array['qttyEnforced'],
    'noDisc' => $ins_array['discount'],
    'inUse' => $ins_array['inUse']
    );
    $dbc->smart_insert('prodUpdate',$pu_array);
}

/* 7. Insert to scaleItem */
if (isset($_REQUEST['s_plu'])){
    $s_plu = substr($upc,3,4);
    $scale_array = array();
    $scale_array['plu'] = $upc;
    $scale_array['itemdesc'] = $ins_array['description'];
    $scale_array['price'] = $ins_array['normal_price'];
    if (isset($_REQUEST['s_longdesc']) && !empty($_REQUEST['s_longdesc']))
        $scale_array['itemdesc'] = $dbc->escape($_REQUEST['s_longdesc']);
    $scale_array['tare'] = isset($_REQUEST['s_tare'])?$_REQUEST['s_tare']:0;
    $scale_array['shelflife'] = isset($_REQUEST['s_shelflife'])?$_REQUEST['s_shelflife']:0;
    $scale_array['bycount'] = isset($_REQUEST['s_bycount'])?1:0;
    $scale_array['graphics'] = isset($_REQUEST['s_graphics'])?1:0;
    $s_type = isset($_REQUEST['s_type'])?$_REQUEST['s_type']:'Random Weight';
    $scale_array['weight'] = ($s_type=="Random Weight")?0:1;
    $scale_array['text'] = isset($_REQUEST['s_text'])?$dbc->escape($_REQUEST['s_text']):"''";

    $s_label = isset($_REQUEST['s_label'])?$_REQUEST['s_label']:'horizontal';
    if ($s_label == "horizontal" && $s_type == "Random Weight")
        $s_label = 133;
    elseif ($s_label == "horizontal" && $s_type == "Fixed Weight")
        $s_label = 63;
    elseif ($s_label == "vertical" && $s_type == "Random Weight")
        $s_label = 103;
    elseif ($s_label == "vertical" && $s_type == "Fixed Weight")
        $s_label = 23;

    $scale_array['label'] = $s_label;
    $scale_array['excpetionprice'] = 0.00;
    $scale_array['class'] = "''";

    $dbc->query("DELETE FROM scaleItems WHERE plu='$upc'");
    $dbc->smart_insert("scaleItems",$scale_array);

    $action = "WriteOneItem";
    include('hobartcsv/parse.php');
    parseitem($action,$s_plu,trim($scale_array["itemdesc"],"'"),
        $scale_array['tare'],$scale_array['shelflife'],$scale_array['price'],
        $scale_array['bycount'],$s_type,0.00,trim($scale_array['text'],"'"),
        $scale_array['label'],($scale_array['graphics']==1)?121:0);
}

/* 8. Delete and re-add to product-related tables on the lanes.  */
/* push updates to the lanes */
include('laneUpdates_WEFC_Toronto.php');
updateAllLanes($upc, array("products", "productUser"));

/* 9. Display the post-update values and an input for the next edit.  */
/* Display some of the post-update values and an input for the next edit.
 * The page contains form elements but there is no submit for the them.
 * The record-select input is also displayed in a proper form with a submit.
*/
// $dbc may be looking at lane db now, so be sure it is looking at Fannie.
$dbc = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
$prodQ = "SELECT * FROM products WHERE upc = ".$upc;
$prodR = $dbc->query($prodQ);
$row = $dbc->fetch_array($prodR);

        echo "<table border=0>";
        echo "<tr><td align=right><b>UPC</b></td><td><font color='red'>".$upc."</font><input type=hidden value='".$upc."' name=upc></td>";
        echo "</tr><tr><td><b>Description</b></td><td>".$row['description']."</td>";
        echo "<td><b>Price</b></td><td>\$ ".$row['normal_price']."</td></tr></table>";
        echo "<table border=0><tr>";
        echo "<th>Dept<th>subDept<th>FS<th>Scale<th>QtyFrc<th>NoDisc<th>inUse<th>deposit</b>";
        echo "</tr>";
        echo "<tr>";
 
        $dept = $row["department"];
        if (is_numeric($dept)) {
            $query2 = "SELECT * FROM departments where dept_no = ".$row["department"];
            $result2 = $dbc->query($query2);
            $row2 = $dbc->fetch_array($result2);
        } else {
            $row2 = array('dept_name' => "");
        }

        $subdept = $row["subdept"];
        if (is_numeric($subdept)) {
            $query2a = "SELECT * FROM subdepts WHERE subdept_no = ".$subdept;
            $result2a = $dbc->query($query2a);
            $row2a = $dbc->fetch_array($result2a);
        } else {
            $row2a = array('subdept_name' => "");
        }

        echo "<td>";
        echo $dept . ' ' .  $row2['dept_name'];
        echo " </td>"; 

        echo "<td>";
        echo $subdept . ' ' .  $row2a['subdept_name'];
        echo " </td>";

        echo "<td align=center><input type=checkbox value=1 name=FS";
                if($row["foodstamp"]==1){
                        echo " checked";
                }
        echo "></td><td align=center><input type=checkbox value=1 name=Scale";
                if($row["scale"]==1){
                        echo " checked";
                }
        echo "></td><td align=center><input type=checkbox value=1 name=QtyFrc";
                if($row["qttyEnforced"]==1){
                        echo " checked";
                }
        echo "></td><td align=center><input type=checkbox value=0 name=NoDisc";
                if($row["discount"]==0){
                        echo " checked";
                }
        echo "></td><td align=center><input type=checkbox value=1 name=inUse";
                if($row["inUse"]==1){
                        echo " checked";
                }
        echo "></td><td align=center><input type=text value=";
        echo $row["deposit"]. " name='deposit' size='5'";
        echo "></td></tr>";
 
        echo "</table>";
        echo "<hr>";
        echo "<form action='itemMaint_WEFC_Toronto.php' method=post>";
                echo promptForUPC($upc);

        echo "</form>";

        /* 10. If requested on the capture form, pop a window for making a shelf tag. */
    if (isset($_REQUEST['newshelftag'])){
        echo "<script type=\"text/javascript\">";
        echo "testwindow= window.open (\"addShelfTag.php?upc=$upc\", \"New Shelftag\",\"location=0,status=1,scrollbars=1,width=300,height=220\");";
        echo "testwindow.moveTo(50,50);";
        echo "</script>";
    }
    ?>
    <script type="text/javascript">
    $(document).ready(function(){
        $('#upc').focus();
    });
        </script>
<?php

include('../src/footer.html');
?>


