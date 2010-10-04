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
require_once('../src/mysql_connect.php');
$page_title = 'Fannie - Item Maintanence';
$header = 'Item Maintanence';
include('../src/header.html');

require_once('../auth/login.php');
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
<?php
echo "<BODY onLoad='putFocus(0,0);'>";

foreach ($_POST AS $key => $value) {
    $$key = $value;
    //echo $key . ": " . $$key . "<br>";

    if($$key == 1){
       $key = 1;
    }elseif($$key == 2){
       $key = 2;
    }else{
       $key = 0;
    }

    if(!isset($key)){
	$value = 0;
    }

}

$today = date("m-d-Y h:m:s");

if(!isset($Scale)){
	$Scale = 0;
}

if(!isset($FS)){
	$FS=0;
}

if(!isset($NoDisc)){
	$NoDisc=1;
}

$inUse = (isset($inUse))?1:0;

if(!isset($QtyFrc)){
	$QtyFrc = 0;
}

if(!isset($deposit)){
	$deposit = 0;
}

if(!isset($tax)){
	$tax = 0;
}

$pricemethod=0;
$vol_price=0;
$vol_qtty=0;
if (isset($_REQUEST['doVolume']) && is_numeric($_REQUEST['vol_price']) && is_numeric($_REQUEST['vol_qtty'])){
	$pricemethod = $_REQUEST['pricemethod'];
	if ($pricemethod==0) $pricemethod=2;
	$vol_price = $_REQUEST['vol_price'];
	$vol_qtty = $_REQUEST['vol_qtty'];
}

$sID = $dbc->query("SELECT superID FROM MasterSuperDepts WHERE dept_ID=$department");
$sID = array_pop($dbc->fetch_row($sID));

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
  if (!empty($likeCode))
    audit($sID,$auditedUser,$upc,$descript,$price,$tax,$FS,$Scale,$NoDisc,$likeCode);
  else
    audit($sID,$auditedUser,$upc,$descript,$price,$tax,$FS,$Scale,$NoDisc);
}

$local = (isset($_REQUEST['local']))?1:0;

$descript = $dbc->escape($descript);
$query = "UPDATE products 
	SET description = $descript, 
	normal_price=$price,
	tax='$tax',
	scale='$Scale',
	foodstamp='$FS',
	department = '$department',
	subdept = '$subdepartment',
	inUse = '$inUse',
        qttyEnforced = '$QtyFrc',
        discount='$NoDisc',
	modified={$dbc->now()},
	deposit=$deposit,
	pricemethod=$pricemethod,
	groupprice=$vol_price,
	quantity=$vol_qtty,
	cost=$cost,
	local=$local
	where upc ='$upc'";
//echo $query;
$result = $dbc->query($query);

if ($dbc->table_exists('prodExtra')){
	$manu = $dbc->escape($_REQUEST['manufacturer']);
	$dist = $dbc->escape($_REQUEST['distributor']);
	$checkR = $dbc->query("SELECT upc FROM prodExtra WHERE upc='$upc'");
	if ($dbc->num_rows($checkR) == 0){
		$xInsQ = "INSERT INTO prodExtra (upc,distributor,manufacturer,cost,margin,variable_pricing,location,
						case_quantity,case_cost,case_info) VALUES
						('$upc',$dist,$manu,$cost,0.00,0,'$location','',0.00,'')";
		$dbc->query($xInsQ);
	}
	else {
		$xUpQ = "UPDATE prodExtra SET distributor=$dist,manufacturer=$manu,
			cost=$cost,location='$location'
			WHERE upc='$upc'";
		$dbc->query($xUpQ);
	}
}
if ($dbc->table_exists("prodUpdate")){
	$query = sprintf("INSERT INTO prodUpdate VALUES ('%s','%s',%f,%d,%d,%d,%d,
		%d,%s,%d,%d,%d,1)",$upc,$descript,$price,$department,$tax,$FS,$Scale,
		isset($_REQUEST['likeCode'])?$_REQUEST['likeCode']:0,$dbc->now(),
		0,$QtyFrc,$NoDisc);
	$result =  $dbc->query($query);
}
if(isset($_REQUEST['s_plu'])){
	$s_plu = substr($_REQUEST['s_plu'],3,4);
	$s_itemdesc = $descript;
	if (isset($_REQUEST['s_longdesc']) && !empty($_REQUEST['s_longdesc']))
		$s_itemdesc = $_REQUESt['s_longdesc'];
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

	$chk = $dbc->query("SELECT * FROM scaleItems WHERE plu='$upc'");
	$action = "ChangeOneItem";
	if ($dbc->num_rows($chk) == 0){
		$query = sprintf("INSERT INTO scaleItems (plu,price,itemdesc,
			exceptionprice,weight,bycount,tare,shelflife,text,
			class,label,graphics VALUES ('%s',%f,'%s',0.00,%d,%d,
			%f,%d,'%s','',%d,%d)",$upc,$price,$s_itemdesc,
			($s_type=="Random Weight")?0:1,$s_bycount,$s_tare,
			$s_shelflife,$s_text,$s_label,$s_graphics);
		$dbc->query($query);
		$action = "WriteOneItem";
	}
	else {
		$query = "UPDATE scaleItems SET
			price=".$price.",
			itemdesc='".$s_itemdesc."',
			weight=".(($s_type=="Random Weight")?0:1).",
			bycount=".$s_bycount.",
			tare=".$s_tare.",
			shelflife=".$s_shelflife.",
			text='".$s_text."',
			label=".$s_label.",
			graphics=".$s_graphics."
			WHERE plu='$upc'";
		$dbc->query($query);
	}

	include('hobartcsv/parse.php');
	parseitem($action,$s_plu,$s_itemdesc,$s_tare,$s_shelflife,$price,
		$s_bycount,$s_type,0.00,$s_text,$s_label,
		($s_graphics==1)?121:0);
}

/* push updates to the lanes */
include('laneUpdates.php');
updateProductAllLanes($upc);

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
		while($upcsW = $dbc->fetch_row($upcsR)){
			$lcUpdateQ = str_replace("where upc ='$upc'","where upc='$upcsW[0]'",$query);
			$dbc->query($lcUpdateQ);
			updateProductAllLanes($upcsW[0]);
		}
	}
}
elseif (isset($_REQUEST['likeCode']) && $_REQUEST['likeCode'] == -1){
	$dbc->query("DELETE FROM upcLike WHERE upc='$upc'");
}


$query1 = "SELECT * FROM products WHERE upc = " .$upc;
$result1 = $dbc->query($query1);
$row = $dbc->fetch_array($result1);

echo "<table border=0>";
        echo "<tr><td align=right><b>UPC</b></td><td><font color='red'>".$row[0]."</font><input type=hidden value='$row[0]' name=upc></td>";
        echo "</tr><tr><td><b>Description</b></td><td>$row[1]</td>";
        echo "<td><b>Price</b></td><td>$$row[2]</td></tr></table>";
        echo "<table border=0><tr>";
        echo "<th>Dept<th>subDept<th>FS<th>Scale<th>QtyFrc<th>NoDisc<th>inUse<th>deposit</b>";
        echo "</tr>";
        echo "<tr>";
        $dept=$row[12];
        $query2 = "SELECT * FROM departments where dept_no = " .$dept;
        $result2 = $dbc->query($query2);
		$row2 = $dbc->fetch_array($result2);
		
		$subdept=$row["subdept"];
		$query2a = "SELECT * FROM subdepts WHERE subdept_no = " .$subdept;
		$result2a = $dbc->query($query2a);
		$row2a = $dbc->fetch_array($result2a);
		
		echo "<td>";
        echo $dept . ' ' . 
		$row2['dept_name'];
        echo " </td>";  

		echo "<td>";
		echo $subdept . ' ' .
		$row2a['subdept_name'];
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

        //echo "<tr><td>" . $row[4] . "</td><td>" . $row[5]. "</td><td>" . $row[6] ."</td><td>" . $row[7] . "</td><td>" . $row[8] . "</td></tr>";
        //echo "<tr><td>" . $row[9] . "</td><td>" . $row[10] . "</td><td>" . $row[11] . "</td><td>" . $row[12] . "</td>";
        
        echo "</table>";
        //echo "I am here.";
		echo "<hr>"; 
		echo "<form action='../item/itemMaint.php' method=post>";
        echo "<input name=upc type=text id=upc> Enter UPC/PLU here<br>";
        echo "<input name=submit type=submit value=submit>";
        echo "</form>";

//
//	PHP INPUT DEBUG FUNCTION -- very helpful!
//


// function debug_p($var, $title) 
// {
//     print "<h4>$title</h4><pre>";
//     print_r($var);
//     print "</pre>";
// }
// 
// debug_p($_REQUEST, "all the data coming in");


include('../src/footer.html');
?>
