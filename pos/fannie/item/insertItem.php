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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include('../config.php');

include('prodFunction.php');
include_once('../src/mysql_connect.php');
$page_title = 'Fannie - Item Maintanence';
$header = 'Item Maintanence';
include('../src/header.html');

include_once('../auth/login.php');
$validatedUser = validateUserQuiet('pricechange');
$auditedUser = validateUserQuiet('audited_pricechange');
$logged_in = checkLogin();
refreshSession();

?>
<?php


$today = date("m-d-Y h:m:s");
echo $today;

$upc = str_pad($_REQUEST['upc'],'0',13,STR_PAD_LEFT);

$ins_array = array();
$ins_array['upc'] = $dbc->escape($_REQUEST['upc']);
$ins_array['tax'] = isset($_REQUEST['tax'])?$_REQUEST['tax']:0;
$ins_array['foodstamp'] = isset($_REQUEST['FS'])?1:0;
$ins_array['scale'] = isset($_REQUEST['Scale'])?1:0;
$ins_array['deposit'] = isset($_REQUEST['deposit'])?$_REQUEST['deposit']:0;
$ins_array['qttyEnforced'] = isset($_REQUEST['QtyFrc'])?1:0;
$ins_array['discount'] = isset($_REQUEST['NoDisc'])?0:1;
$ins_array['normal_price'] = isset($_REQUEST['price'])?$_REQUEST['price']:0;
$ins_array['description'] = $dbc->escape($_REQUEST['descript']);

/* set tax and FS to department defaults */
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

$del99Q = "DELETE FROM products WHERE upc = '$upc'";
$delISR = $dbc->query($del99Q);

$ins_array['pricemethod'] = 0;
$ins_array['groupprice'] = 0.00;
$ins_array['quantity'] = 0;
$ins_array['special_price'] = 0.00;
$ins_array['specialpricemethod'] = 0;
$ins_array['specialgroupprice'] = 0.00;
$ins_array['specialquantity'] = 0;
$ins_array['department'] = $_REQUEST['department'];
$ins_array['size'] = "''";
$ins_array['scaleprice'] = 0.00;
$ins_array['mixmatchcode'] = "'0'";
$ins_array['modified'] = $dbc->now();
$ins_array['advertised'] = 1;
$ins_array['tareweight'] = 0;
$ins_array['discounttype'] = 0;
$ins_array['unitofmeasure'] = "''";
$ins_array['wicable'] = 0;
$ins_array['idEnforced'] = 0;
$ins_array['cost'] = $_REQUEST['cost'];
$ins_array['inUse'] = 1;
$ins_array['subdept'] = $_REQUEST['subdepartment'];
$ins_array['local'] = 0;
$ins_array['start_date'] = "'1900-01-01'";
$ins_array['end_date'] = "'1900-01-01'";
$ins_array['numflag'] = 0;

if (isset($_REQUEST['likeCode']) && $_REQUEST['likeCode'] != -1){
	$dbc->query("DELETE FROM upcLike WHERE upc='$upc'");
	$lcQ = "INSERT INTO upcLike (upc,likeCode) VALUES ('$upc',{$_REQUEST['likeCode']})";
	$dbc->query($lcQ);	
}
// echo "<br>" .$query99. "<br>";
$resultI = $dbc->smart_insert('products',$ins_array);

if ($dbc->table_exists('prodExtra')){
	$pxarray = array(
	'upc' => $dbc->escape($upc),
	'distributor' => $dbc->escape($_REQUEST['distributor']),
	'manufacturer' => $dbc->escape($_REQUEST['manufacturer']),
	'cost' => $_REQUEST['cost'],
	'margin' => 0.00,
	'variable_pricing' => 0,
	'location' => $dbc->escape($_REQUEST['location']),
	'case_quantity' => "''",
	'case_cost' => 0.00,
	'case_info' => "''"
	);
	$dbc->query("DELETE FROM prodExtra WHERE upc='$upc'");
	$dbc->smart_insert('prodExtra',$pxarray);
}

if ($dbc->table_exists("prodUpdate")){
	$puarray = array(
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
	$dbc->smart_insert('prodUpdate',$puarray);
}
if (isset($_REQUEST['s_plu'])){
	$s_plu = substr($upc,3,4);
	$s_itemdesc = $descript;
	if (isset($_REQUEST['s_longdesc']) && !empty($_REQUEST['s_longdesc']))
		$s_itemdesc = $dbc->escape($_REQUEST['s_longdesc']);
	$tare = isset($_REQUEST['s_tare'])?$_REQUEST['s_tare']:0;
	$shelflife = isset($_REQUEST['s_shelflife'])?$_REQUEST['s_shelflife']:0;
	$s_bycount = isset($_REQUEST['s_bycount'])?1:0;
	$s_graphics = isset($_REQUEST['s_graphics'])?1:0;
	$s_type = isset($_REQUEST['s_type'])?$_REQUEST['s_type']:'Random Weight';
	$s_text = isset($_REQUEST['s_text'])?$_REQUEST['s_text']:'';

	$_label = isset($_REQUEST['s_label'])?$_REQUEST['s_label']:'horizontal';	
	if ($s_label == "horizontal" && $s_type == "Random Weight")
		$s_label = 133;
	elseif ($s_label == "horizontal" && $s_type == "Fixed Weight")
		$s_label = 63;
	elseif ($s_label == "vertical" && $s_type == "Random Weight")
		$s_label = 103;
	elseif ($s_label == "vertical" && $s_type == "Fixed Weight")
		$s_label = 23;

	/* apostrophe filter */
	$s_itemdesc = str_replace("'","",$s_itemdesc);
	$s_text = str_replace("'","",$s_text);
	$s_itemdesc = str_replace("\"","",$s_itemdesc);
	$s_text = str_replace("\"","",$s_text);

	$dbc->query("DELETE FROM scaleItems WHERE plu='$upc'");
	$query = sprintf("INSERT INTO scaleItems (plu,price,itemdesc,
		exceptionprice,weight,bycount,tare,shelflife,text,
		class,label,graphics VALUES ('%s',%f,%s,0.00,%d,%d,
		%f,%d,'%s','',%d,%d)",$upc,$price,$s_itemdesc,
		($s_type=="Random Weight")?0:1,$s_bycount,$s_tare,
		$s_shelflife,$s_text,$s_label,$s_graphics);
	$dbc->query($query);

	$action = "WriteOneItem";
	include('hobartcsv/parse.php');
	parseitem($action,$s_plu,$s_itemdesc,$s_tare,$s_shelflife,$price,
		$s_bycount,$s_type,0.00,$s_text,$s_label,
		($s_graphics==1)?121:0);
}

include('laneUpdates.php');
updateProductAllLanes($upc);

$prodQ = "SELECT * FROM products WHERE upc = ".$upc;
//echo $prodQ;
$prodR = $dbc->query($prodQ);
$row = $dbc->fetch_array($prodR);

		echo "<table border=0>";
        echo "<tr><td align=right><b>UPC</b></td><td><font color='red'>".$upc."</font><input type=hidden value='".$upc."' name=upc></td>";
        echo "</tr><tr><td><b>Description</b></td><td>".$_REQUEST['descript']."</td>";
        echo "<td><b>Price</b></td><td>".$_REQUEST['price']."</td></tr></table>";
        echo "<table border=0><tr>";
        echo "<th>Dept<th>subDept<th>FS<th>Scale<th>QtyFrc<th>NoDisc<th>inUse<th>deposit</b>";
        echo "</tr>";
        echo "<tr>";
       
		$dept = $row["department"];
        $query2 = "SELECT * FROM departments where dept_no = ".$row["department"];
        $result2 = $dbc->query($query2);
		$row2 = $dbc->fetch_array($result2);
		
		$subdept = $row["subdept"];
		$query2a = "SELECT * FROM subdepts WHERE subdept_no = ".$row["subdept"];
		$result2a = $dbc->query($query2a);
		$row2a = $dbc->fetch_array($result2a);
		
		echo "<td>";
        echo $dept . ' ' . $row2[1];
        echo " </td>";  

		echo "<td>";
		echo $subdept . ' ' . $row2a[1];
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
    echo "<form action=itemMaint.php method=post>"; 
    echo "<input name=upc type=text id=upc> Enter UPC/PLU here<br>";
    echo "<input name=submit type=submit value=submit>";
    echo "</form>";

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


