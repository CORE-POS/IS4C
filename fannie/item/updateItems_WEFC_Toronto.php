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
 * This piece of code does quite a few things. Items below are grepped from the listing.
*/
/* 1. Validate credentials of the operator.  */
/* 2. Insert or update per-coop products data  */
/* 3. Insert or update products */
/* 4. Apply HQ updates to non-HQ records */
/* 5. Insert or update productUser */
/* 6. Insert or update vendorItems */
/* 7. Insert or update prodExtra */
/* 8. Insert or update prodUpdate: audit trail */
/* 9. Insert or update scaleItems */
/* 10. Delete and re-add to product-related tables on the lanes.  */
/* 11. Display the post-update values and an input for the next edit.  */
/* 12. If requested on the capture form, pop a window for making a shelf tag. */

/*
 * This code INSERTs when the record for the fields doesn't exist,
 *  but see insertItem.php for the code used when addinig a new UPC/PLU.
 * There is code here to support the situations where the record being updated
 *  + is for a non-HQ store AND
 *    + no HQ record exists
 *    + an HQ record does exist
 *  + is for HQ and there are non-HQ store records for the item
*/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
//$FANNIE_ITEM_MODULES;
// This would be both whether-or-not and sequence-on-page
$Fannie_Item_Modules = array("Operations","ExtraInfo",
        "ThreeForDollar",
    "Cost","Sale","Margin", "LikeCode", "LaneStatus");
$dbc = FannieDB::get($FANNIE_OP_DB);

require_once('../auth/login.php');
$validatedUser = validateUserQuiet('pricechange');
$auditedUser = validateUserQuiet('audited_pricechange');
$logged_in = checkLogin();
refreshSession();

$page_title = 'Fannie - Item Maintenance WEFC_Toronto';
$header = 'Item Maintenance WEFC_Toronto';
include('../src/header.html');

include_once('prodFunction_WEFC_Toronto.php');

$upc = str_pad($_REQUEST['upc'],'0',13,STR_PAD_LEFT);

/* Establish values for all products fields
    formatted for writing to the database
    either from the form or defaults suitable for a new record.
*/
$up_array = array();
// Text that must be numeric. saveCharInt()
$up_array['deposit'] = is_numeric($_REQUEST['deposit'])?$_REQUEST['deposit']:0;
$up_array['normal_price'] = saveAsMoney($_REQUEST,"price");
// Values controlled by select.
$up_array['tax'] = isset($_REQUEST['tax'])?$_REQUEST['tax']:0;
$up_array['department'] = $_REQUEST['department'];
// Checkboxes
$up_array['foodstamp'] = isset($_REQUEST['FS'])?1:0;
$up_array['scale'] = isset($_REQUEST['Scale'])?1:0;
$up_array['qttyEnforced'] = isset($_REQUEST['QtyFrc'])?1:0;
$up_array['discount'] = isset($_REQUEST['NoDisc'])?0:1;
// Checkboxes2
$up_array['inUse'] = isset($_REQUEST['inUse'])?1:0;
$up_array['subdept'] = $_REQUEST['subdepartment'];
$up_array['local'] = isset($_REQUEST['local'])?1:0;
// Text of a limited length.
$up_array['description'] = $dbc->escape(substr($_REQUEST['descript'],0,30));
// Package
$string_size = substr(trim($_REQUEST['size']),0,9);
$numeric_size = (is_numeric($string_size))?$string_size:0;
$up_array['size'] = $dbc->escape("$string_size");
$unitofmeasure = substr(trim($_REQUEST['unitofmeasure']),0,15);
$up_array['unitofmeasure'] = $dbc->escape("$unitofmeasure");
//
$up_array['cost'] = saveAsMoney($_REQUEST,'cost');
//
$up_array['modified'] = $dbc->now();
// hidden on form?
$up_array['store_id'] = isset($_REQUEST['store_id'])?$_REQUEST['store_id']:0;
// 22Feb13 EL Ones that are still not in the form.
$up_array['scaleprice'] = 0.00;
$up_array['advertised'] = 1;
$up_array['tareweight'] = 0;
$up_array['wicable'] = 0;
$up_array['idEnforced'] = 0;
// Item flags, bits
$up_array['numflag'] = (isset($_REQUEST['flags']))?setProductFlags($_REQUEST['flags']):0;
// 3 for $1 from fieldset
// Only use these if the fieldset they are in is displayed ...
if ( array_search('ThreeForDollar',$Fannie_Item_Modules) !== False ) {
    $up_array['pricemethod'] = is_numeric($_REQUEST['pricemethod'])?$_REQUEST['pricemethod']:0;
    $up_array['groupprice'] = saveAsMoney($_REQUEST,'groupprice');
    //$up_array['groupprice'] = is_numeric($_REQUEST['groupprice'])?$_REQUEST['groupprice']:0.00;
    $up_array['quantity'] = is_numeric($_REQUEST['quantity'])?$_REQUEST['quantity']:0;
}
// ... but use this regardless since it isn't edited in the "Volume" group.
//  Is in effect hidden if its block isn't displayed.
// 22Feb13 Default to '0' seems odd.
$up_array['mixmatchcode'] = !empty($_REQUEST['mixmatchcode'])?$dbc->escape($_REQUEST['mixmatchcode']):"'0'";
// Sale
$up_array['special_price'] = saveAsMoney($_REQUEST,'special_price');
// 0=not on sale; 1=on sale to all; 2=on sale to members
$up_array['discounttype'] = is_numeric($_REQUEST['discounttype'])?$_REQUEST['discounttype']:0;
$up_array['start_date'] = isset($_REQUEST['start_date'])?$dbc->escape($_REQUEST['start_date']):"'1900-01-01'";
$up_array['end_date'] = isset($_REQUEST['end_date'])?$dbc->escape($_REQUEST['end_date']):"'1900-01-01'";
// Sale 3for$1
$up_array['specialpricemethod'] = is_numeric($_REQUEST['specialpricemethod'])?$_REQUEST['specialpricemethod']:0;
$up_array['specialgroupprice'] = saveAsMoney($_REQUEST,'specialgroupprice');
//$up_array['specialgroupprice'] = is_numeric($_REQUEST['specialgroupprice'])?$_REQUEST['specialgroupprice']:0.00;
$up_array['specialquantity'] = is_numeric($_REQUEST['specialquantity'])?$_REQUEST['specialquantity']:0;
/* turn on volume pricing if specified, but don't
   alter pricemethod if it's already non-zero
   If volume pricing specified but method still 0 silently use method 2.
    1Mar13 EL The whole Volume option is only available if the ThreeForDollar fieldset is not available.
*/
if (isset($_REQUEST['doVolume']) && is_numeric($_REQUEST['vol_price']) && is_numeric($_REQUEST['vol_qtty'])){
    $up_array['pricemethod'] = $_REQUEST['vol_pricemethod'];
    if ($up_array['pricemethod']==0) $up_array['pricemethod']=2;
    $up_array['groupprice'] = saveAsMoney($_REQUEST,'vol_price');
    //$up_array['groupprice'] = $_REQUEST['vol_price'];
    $up_array['quantity'] = $_REQUEST['vol_qtty'];
}

/* pull the current, HQ values for all the editable fields
   and compare them to the submitted values
   Store actual changes in the array $CHANGES
*/
$currentQ = "SELECT tax,foodstamp,scale,deposit,qttyEnforced,discount,normal_price,
    description,pricemethod,groupprice,quantity,department,cost,subdept,local,
    mixmatchcode,size,unitofmeasure,
    special_price,specialpricemethod,specialgroupprice,specialquantity,discounttype,start_date,end_date
    FROM products WHERE upc='$upc' AND store_id=0";
$currentR = $dbc->query($currentQ);
$currentW = array();
if ($dbc->num_rows($currentR) > 0)
    $currentW = $dbc->fetch_row($currentR);
$CHANGES = array();
foreach($up_array as $column => $new_value){
    if (!isset($currentW[$column])) continue;
    if ($currentW[$column] != trim($new_value,"'")){
        $CHANGES[$column] = array(
            'old' => $currentW[$column],
            'new' => trim($new_value,"'")
        );
    }
}

$sR = $dbc->query("SELECT superID FROM MasterSuperDepts WHERE dept_ID=".$up_array['department']);
$sID = 0;
if ($dbc->num_rows($sR) > 0)
    $sID = array_pop($dbc->fetch_row($sR));

/* 1. Validate credentials of the operator.  */
$uid = 0;
if (!$validatedUser && !$auditedUser && $logged_in){
  $validatedUser = validateUserQuiet('pricechange',$subdepartment);
}
if ($validatedUser){
  $validatedUID = getUID($validatedUser);
  $uid = $validatedUID;
}
elseif ($auditedUser){
  $auditedUID = getUID($auditedUser);
  $uid = $auditedUID;
  include('audit.php');
/* 2el. Notify dept manager of the new values.  */
  if (!empty($likeCode))
    audit($sID,$auditedUser,$upc,$descript,$price,$tax,$FS,$Scale,$NoDisc,$likeCode);
  else
    audit($sID,$auditedUser,$upc,$descript,$price,$tax,$FS,$Scale,$NoDisc);
}

/* 2. Insert or update per-coop products data  */
/* For WEFC_Toronto only
 * Store the raw versions of products.description and productUser.description in products_WEFC_Toronto.
*/
if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == "WEFC_Toronto" ) {
    $table_name = "products_{$FANNIE_COOP_ID}";
    if ($dbc->table_exists("$table_name")){
        $coop_array = array("description" => $dbc->escape(substr($_REQUEST['descript'],0,255)),
        "search_description" => isset($_REQUEST['puser_description'])?$dbc->escape(substr($_REQUEST['puser_description'],0,255)):'');
        $checkR = $dbc->query("SELECT upc FROM $table_name WHERE upc='$upc'");
        if ($dbc->num_rows($checkR) == 0){
            $coop_array['upc'] = $dbc->escape($upc);
            $dbc->smart_insert("$table_name",$coop_array);
        }
        else {
            $dbc->smart_update("$table_name",$coop_array,"upc='$upc'");
        }
    }
}

// Compose "package" strings.
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
    $maxDescLen = 30;
    $wlen = ($dlen + $plen);

    if ( $wlen <= $maxDescLen ) {
        $up_array['description'] = $dbc->escape($descp . $package);
    } else {
        $up_array['description'] = $dbc->escape(substr($descp,0,($dlen - ($wlen - $maxDescLen))) . $package);
    }
}
if ( isset($FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION) && $FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION == "1" ) {
    $plen = strlen($package);
    $mlen = strlen($_REQUEST['manufacturer']);
    $descp = $_REQUEST['puser_description'];
    $dlen = strlen($descp);
    $maxDescLen = 255;
    $wlen = ($dlen + 3 + $mlen + 3 + $plen);
    if ( $wlen <= $maxDescLen ) {
        $puser_description = strtoupper($_REQUEST['manufacturer']) . ' | ' . $_REQUEST['puser_description'] . ' | ' . $package;
    } else {
        $puser_description = strtoupper($_REQUEST['manufacturer']) . ' | ' .
            substr($descp,0,($dlen - ($wlen - $maxDescLen)) . ' | ' . $package);
    }
} else {
    $puser_description = substr($_REQUEST['puser_description'],0,255);
}

/* 3. Insert or update products */
if ($up_array['store_id'] == $FANNIE_STORE_ID){
    // record exists so update it
    $dbc->smart_update('products',$up_array,"upc='$upc'");
}
else if($up_array['store_id']==0 && count($CHANGES) > 0){
    // only the HQ record exists and we are not HQ
    // so it has to be an insert
    // only create a new record if changes really exist
    // 22Feb13 EL store_id is set again below.
    $up_array['store_id'] = $FANNIE_STORE_ID;
    $up_array['upc'] = $dbc->escape($_REQUEST['upc']);
    // 22Feb13 EL Not certain about using entered values instead of these defaults
    //  when inserting to HQ.  Leave as-is for now.
    $up_array['special_price'] = 0.00;
    $up_array['specialpricemethod'] = 0;
    $up_array['specialgroupprice'] = 0.00;
    $up_array['specialquantity'] = 0;
    $up_array['start_date'] = "'1900-01-01'";
    $up_array['end_date'] = "'1900-01-01'";
    $up_array['mixmatchcode'] = "'0'";
    $up_array['discounttype'] = 0;
    $up_array['store_id'] = 0;
    $dbc->smart_insert('products',$up_array);
}

/* 4. Apply HQ updates to non-HQ records */
// apply HQ updates to non-HQ records
//  where the current value matches the old HQ value
if ($FANNIE_STORE_ID==0 && count($CHANGES) > 0){
    foreach($CHANGES as $col => $values){
        $v_old = is_numeric($values['old']) ? $values['old'] : $dbc->escape($values['old']);
        $v_new = is_numeric($values['new']) ? $values['new'] : $dbc->escape($values['new']);
        $upQ = sprintf("UPDATE products SET %s=%s,modified=%s
            WHERE %s=%s AND upc=%s AND store_id > 0",
            $col,$v_new,
            $dbc->now(),
            $col,$v_old,
            $dbc->escape($upc));
        $upR = $dbc->query($upQ);
    }
}

/* 5. Insert or update productUser */
if ($dbc->table_exists('productUser')){
    $puser_array = array();
    $puser_array['brand'] = $dbc->escape($_REQUEST['manufacturer']);
    $puser_array['description'] = $dbc->escape($puser_description);
    $puser_array['sizing'] = $dbc->escape($sizing);
    $checkR = $dbc->query("SELECT upc FROM productUser WHERE upc='$upc'");
    if ($dbc->num_rows($checkR) == 0){
        // if productUser record doesn't exist, needs more values
        $puser_array['upc'] = $dbc->escape($upc);
        $puser_array['enableOnline'] = 0;
        $dbc->smart_insert('productUser',$puser_array);
    }
    else {
        $dbc->smart_update('productUser',$puser_array,"upc='$upc'");
    }
}

/* 6. Insert or update vendorItems */
/* Changing vendorItems at all should perhaps be an option. */
if ($dbc->table_exists('vendorItems')){
    $vi_array = array();
    $vi_array['sku'] = $dbc->escape(substr($_REQUEST['sku'], 0, 10));
    $vi_array['brand'] = $dbc->escape(substr($_REQUEST['manufacturer'], 0, 50));
    $vi_array['description'] = $dbc->escape(substr($_REQUEST['descript'], 0, 50));
    $vi_array['cost'] = saveAsMoney($_REQUEST,'case_cost');
    $vi_array['size'] = $dbc->escape("$sizing");
    if ( is_numeric($_REQUEST['case_quantity']) && $_REQUEST['case_quantity'] > 0 )
        $vi_array['units'] = $_REQUEST['case_quantity'];
    else
        $vi_array['units'] = 'NULL';
    // Several vendorItems fields are not edited in itemMaint.php
    $checkR = $dbc->query("SELECT upc FROM vendorItems WHERE upc='$upc'");
    if ($dbc->num_rows($checkR) == 0){
        $vi_array['upc'] = $dbc->escape($upc);
        // vendorID
        $checkR = $dbc->query("SELECT vendorID FROM vendorItems WHERE brand='{$vi_array['brand']}'");
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
}

/* 7. Insert or update prodExtra */
if ($dbc->table_exists('prodExtra')){
    $px_array = array();
    $px_array['manufacturer'] = $dbc->escape($_REQUEST['manufacturer']);
    $px_array['distributor'] = $dbc->escape($_REQUEST['distributor']);
    $px_array['cost'] = $up_array['cost'];
    $px_array['location'] = $dbc->escape($_REQUEST['location']);
    $px_array['case_cost'] = saveAsMoney($_REQUEST,'case_cost');
    $px_array['case_quantity'] = is_numeric($_REQUEST['case_quantity'])?$dbc->escape($_REQUEST['case_quantity']):"''";
    if ( $up_array['cost'] != 0 && $up_array['normal_price'] != 0 ) {
        $px_array['margin'] = sprintf("%.2f", 1 -($up_array['cost'] / $up_array['normal_price']));
    } else {
        $px_array['margin'] = 0.00;
    }
    $checkR = $dbc->query("SELECT upc FROM prodExtra WHERE upc='$upc'");
    if ($dbc->num_rows($checkR) == 0){
        // if prodExtra record doesn't exist, needs more values
        $px_array['upc'] = $dbc->escape($upc);
        $px_array['variable_pricing'] = 0;
        $px_array['case_info'] = "''";
        $dbc->smart_insert('prodExtra',$px_array);
    }
    else {
        $dbc->smart_update('prodExtra',$px_array,"upc='$upc'");
    }
}

/* 8. Insert or update prodUpdate: audit trail */
if ($dbc->table_exists("prodUpdate")){
    $pu_array = array(
    'upc' => $dbc->escape($upc),
    'description' => $up_array['description'],
    'price' => $up_array['normal_price'],
    'dept' => $up_array['department'],
    'tax' => $up_array['tax'],
    'fs' => $up_array['foodstamp'],
    'scale' => $up_array['scale'],
    'likeCode' => isset($_REQUEST['likeCode'])?$_REQUEST['likeCode']:0,
    'modified' => $dbc->now(),
    'user' => $uid,
    'forceQty' => $up_array['qttyEnforced'],
    'noDisc' => $up_array['discount'],
    'inUse' => $up_array['inUse']
    );
    $dbc->smart_insert('prodUpdate',$pu_array);
}

/* 9. Insert or update scaleItems */
if(isset($_REQUEST['s_plu'])){
    $s_plu = substr($_REQUEST['s_plu'],3,4);
    $scale_array = array();
    $scale_array['plu'] = $upc;
    $scale_array['itemdesc'] = $up_array['description'];
    $scale_array['price'] = $up_array['normal_price'];
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

    $chk = $dbc->query("SELECT * FROM scaleItems WHERE plu='$upc'");
    $action = "ChangeOneItem";
    if ($dbc->num_rows($chk) == 0){
        $dbc->smart_insert('scaleItems',$scale_array);
        $action = "WriteOneItem";
    }
    else {
        unset($scale_array['plu']);
        $dbc->smart_update('scaleItems',$scale_array,"plu='$upc'");
        $action = "ChangeOneItem";
    }

    include('hobartcsv/parse.php');
    parseitem($action,$s_plu,trim($scale_array["itemdesc"],"'"),
        $scale_array['tare'],$scale_array['shelflife'],$scale_array['price'],
        $scale_array['bycount'],$s_type,0.00,trim($scale_array['text'],"'"),
        $scale_array['label'],($scale_array['graphics']==1)?121:0);
}

/* 10. Delete and re-add to product-related tables on the lanes.  */
/* push updates to the lanes */
include('laneUpdates_WEFC_Toronto.php');
updateAllLanes($upc, array("products", "productUser"));

// $dbc is looking at lane db now, so change it back.
// What is the DB function to do this? FannieDB::get()?
//   We're not in the right environment for that.
$dbc = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
/* 'i9el. Update likecodes */
/* update the item's likecode if specified
   also update other items in the likecode
   if the appropriate box isn't checked */
if (isset($_REQUEST['likeCode']) && $_REQUEST['likeCode'] != -1){
    $dbc->query("DELETE FROM upcLike WHERE upc='$upc'");
    $lcQ = "INSERT INTO upcLike (upc,likeCode) VALUES ('$upc',{$_REQUEST['likeCode']})";
    $dbc->query($lcQ);

    if (!isset($_REQUEST['update'])){
        $upcsQ = "SELECT upc FROM upcLike WHERE likeCode={$_REQUEST['likeCode']} AND upc <> '$upc'";
        $upcsR = $dbc->query($upcsQ);
        unset($up_array['description']);
        while($upcsW = $dbc->fetch_row($upcsR)){
            $dbc->smart_update('products',$up_array,
                "upc='$upcsW[0]' AND store_id=$FANNIE_STORE_ID");
            updateProductAllLanes($upcsW[0]);
        }
    }
}
elseif (isset($_REQUEST['likeCode']) && $_REQUEST['likeCode'] == -1){
    $dbc->query("DELETE FROM upcLike WHERE upc='$upc'");
}


/* 11. Display the post-update values and an input for the next edit.  */
/* Display some of the post-update values and an input for the next edit.
 * The page contains form elements but there is no submit for the them.
 * The record-select input is also displayed in a proper form with a submit.
*/
$query1 = "SELECT upc,description,normal_price,department,subdept,
        foodstamp,scale,qttyEnforced,discount,inUse,deposit
         FROM products WHERE upc = '$upc'";
$result1 = $dbc->query($query1);
$row = $dbc->fetch_array($result1);

echo "<table border=0>";
        echo "<tr><td align=right><b>UPC</b></td><td><font color='red'>".$row['upc']."</font><input type=hidden value='{$row['upc']}' name=upc></td>";
        echo "</tr><tr><td><b>Description</b></td><td>{$row['description']}</td>";
        echo "<td><b>Price</b></td><td>\${$row['normal_price']}</td></tr></table>";
        echo "<table border=0><tr>";
        echo "<th>Dept<th>subDept<th>FS<th>Scale<th>QtyFrc<th>NoDisc<th>inUse<th>deposit</b>";
        echo "</tr>";
        echo "<tr>";
        $dept=$row['department'];
        if (is_numeric($dept)) {
            $query2 = "SELECT dept_name FROM departments where dept_no = " .$dept;
            $result2 = $dbc->query($query2);
            $row2 = $dbc->fetch_array($result2);
        } else {
            $row2 = array('dept_name' => "");
        }

        $subdept=$row["subdept"];
        if (is_numeric($subdept)) {
            $query2a = "SELECT subdept_name FROM subdepts WHERE subdept_no = " .$subdept;
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
        echo "></td><td align=center><input type=text value=\"".$row["deposit"]."\" name=deposit size='5'";
        echo "></td></tr>";

    echo "</table>";
        echo "<hr>";
        echo "<form action='itemMaint_WEFC_Toronto.php' method=post>";

                echo promptForUPC($upc);

        echo "</form>";

        /* 12. If requested on the capture form, pop a window for making a shelf tag. */
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
