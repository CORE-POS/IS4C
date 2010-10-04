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
<html>
<head>
<SCRIPT LANGUAGE="JavaScript">

<!-- This script and many more are available free online at -->
<!-- The JavaScript Source!! http://javascript.internet.com -->
<!-- John Munn  (jrmunn@home.com) -->

<!-- Begin
 function putFocus(formInst, elementInst) {
  if (document.forms.length > 0) {
   document.forms[formInst].elements[elementInst].focus();
  }
 }
// The second number in the "onLoad" command in the body
// tag determines the form's focus. Counting starts with '0'
//  End -->
</script>

</head>
<BODY onLoad='putFocus(0,0);'>

<?php


foreach ($_POST AS $key => $value) {
    $$key = $value;
}


$today = date("m-d-Y h:m:s");
echo $today;

if(!isset($tax)){
	$tax = 0;
}	
if(!isset($FS)){
	$FS = 0;
}
if(!isset($Scale)){
	$Scale = 0;
}
if(!isset($deposit) || is_null($deposit)){
	$deposit = 0;
}
if(!isset($QtyFrc)){
	$QtyFrc = 0;
}
$discount = (isset($_REQUEST['NoDisc']))?0:1;

if (!$price) $price = 0;
$descript = $dbc->escape($descript);

/* set tax and FS by department defaults */
$deptSub = 0;
$taxfsQ = "select dept_tax,dept_fs,
	dept_discount,
	superID FROM
	departments as d left join MasterSuperDepts as s on d.dept_no=s.dept_ID";
$taxfsR = $dbc->query($taxfsQ);
if ($dbc->num_rows($taxfsR) > 0){
	$taxfsW = $dbc->fetch_array($taxfsR);
	$tax = $taxfsW[0];
	$FS = $taxfsW[1];
	$discount = $taxfsW[2];
	$deptSub = $taxfsW[3];
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
	echo "<a href=/auth/ui/loginform.php?redirect=/queries/productTest.php?upc=$upc>";
	echo "login</a> to add new items";
	return;
}

$del99Q = "DELETE FROM products where upc = '$upc'";
$delISR = $dbc->query($del99Q);

$query99 = "INSERT INTO Products (upc,description,normal_price,pricemethod,groupprice,quantity,
	special_price,specialpricemethod,specialgroupprice,specialquantity,
	department,size,tax,foodstamp,scale,scaleprice,mixmatchcode,modified,advertised,tareweight,discount,
	discounttype,unitofmeasure,wicable,qttyEnforced,idEnforced,cost,inUse,subdept,deposit,local,
	start_date,end_date,numflag)
VALUES('$upc',$descript,$price,0,0.00,0,0.00,0,0.00,0,$department,'',$tax,$FS,$Scale,0,0,{$dbc->now()},
1,0,$discount,0,'',0,$QtyFrc,0,$cost,1,$subdepartment,$deposit,0,'1900-01-01','1900-01-01',0)";

if (isset($_REQUEST['likeCode']) && $_REQUEST['likeCode'] != -1){
	$dbc->query("DELETE FROM upcLike WHERE upc='$upc'");
	$lcQ = "INSERT INTO upcLike (upc,likeCode) VALUES ('$upc',{$_REQUEST['likeCode']})";
	$dbc->query($lcQ);	
}
// echo "<br>" .$query99. "<br>";
$resultI = $dbc->query($query99);

if ($dbc->table_exists('prodExtra')){
	$manu = $dbc->escape($_REQUEST['manufacturer']);
	$dist = $dbc->escape($_REQUEST['distributor']);
	$dbc->query("DELETE FROM prodExtra WHERE upc='$upc'");
	$xInsQ = "INSERT INTO prodExtra (upc,distributor,manufacturer,cost,margin,variable_pricing,location,
					case_quantity,case_cost,case_info) VALUES
					('$upc',$dist,$manu,$cost,0.00,0,'$location','',0.00,'')";
	$dbc->query($xInsQ);
}

if ($dbc->table_exists("prodUpdate")){
	$query = sprintf("INSERT INTO prodUpdate VALUES ('%s',%s,%f,%d,%d,%d,%d,
		%d,%s,%d,%d,%d,1)",$upc,$descript,$price,$department,$tax,$FS,$Scale,
		isset($_REQUEST['likeCode'])?$_REQUEST['likeCode']:0,$dbc->now(),
		$uid,$QtyFrc,$discount);
	$result =  $dbc->query($query);
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
        echo "</tr><tr><td><b>Description</b></td><td>".$descript."</td>";
        echo "<td><b>Price</b></td><td>".$price."</td></tr></table>";
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
//
//	PHP INPUT DEBUG SCRIPT -- very useful!
//

/*
function debug_p($var, $title) 
{
    print "<h4>$title</h4><pre>";
    print_r($var);
    print "</pre>";
}

debug_p($_REQUEST, "all the data coming in");
*/

include('../src/footer.html');
?>


