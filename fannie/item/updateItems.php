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
include('../config.php');
require_once('../src/mysql_connect.php');

require_once('../auth/login.php');
$validatedUser = validateUserQuiet('pricechange');
$auditedUser = validateUserQuiet('audited_pricechange');
$logged_in = checkLogin();
refreshSession();

$page_title = 'Fannie - Item Maintanence';
$header = 'Item Maintanence';
include('../src/header.html');

$upc = str_pad($_REQUEST['upc'],'0',13,STR_PAD_LEFT);

$up_array = array();
$up_array['tax'] = isset($_REQUEST['tax'])?$_REQUEST['tax']:0;
$up_array['foodstamp'] = isset($_REQUEST['FS'])?1:0;
$up_array['scale'] = isset($_REQUEST['Scale'])?1:0;
$up_array['deposit'] = isset($_REQUEST['deposit'])?$_REQUEST['deposit']:0;
$up_array['qttyEnforced'] = isset($_REQUEST['QtyFrc'])?1:0;
$up_array['discount'] = isset($_REQUEST['NoDisc'])?0:1;
$up_array['normal_price'] = isset($_REQUEST['price'])?$_REQUEST['price']:0;
$up_array['description'] = $dbc->escape($_REQUEST['descript']);
$up_array['pricemethod'] = 0;
$up_array['groupprice'] = 0.00;
$up_array['quantity'] = 0;
$up_array['department'] = $_REQUEST['department'];
$up_array['size'] = "''";
$up_array['scaleprice'] = 0.00;
$up_array['modified'] = $dbc->now();
$up_array['advertised'] = 1;
$up_array['tareweight'] = 0;
$up_array['unitofmeasure'] = "''";
$up_array['wicable'] = 0;
$up_array['idEnforced'] = 0;
$up_array['cost'] = $_REQUEST['cost'];
$up_array['inUse'] = 1;
$up_array['subdept'] = $_REQUEST['subdepartment'];
$up_array['local'] = isset($_REQUEST['local'])?1:0;
$up_array['store_id'] = isset($_REQUEST['store_id'])?$_REQUEST['store_id']:0;
$up_array['numflag'] = 0;
if (isset($_REQUEST['flags']) && is_array($_REQUEST['flags'])){
	foreach($_REQUEST['flags'] as $f){
		if ($f != (int)$f) continue;
		$up_array['numflag'] = $up_array['numflag'] | (1 << ($f-1));
	}
}

/* turn on volume pricing if specified, but don't
   alter pricemethod if it's already non-zero */
if (isset($_REQUEST['doVolume']) && is_numeric($_REQUEST['vol_price']) && is_numeric($_REQUEST['vol_qtty'])){
	$up_array['pricemethod'] = $_REQUEST['pricemethod'];
	if ($up_array['pricemethod']==0) $up_array['pricemethod']=2;
	$up_array['groupprice'] = $_REQUEST['vol_price'];
	$up_array['quantity'] = $_REQUEST['vol_qtty'];
}

/* pull the current, HQ values for all the editable fields
   and compare them to the submitted values
   Store actual changes in the array $CHANGES
*/
$currentQ = "SELECT tax,foodstamp,scale,deposit,qttyEnforced,discount,normal_price,
	description,pricemethod,groupprice,quantity,department,cost,subdept,local
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

if ($up_array['store_id'] == $FANNIE_STORE_ID){
	// record exists so update it
	$dbc->smart_update('products',$up_array,"upc='$upc'");
}
else if($up_array['store_id']==0 && count($CHANGES) > 0){
	// only the HQ record exists and this is not HQ
	// so it has to be an insert
	// only create a new record if changes really exist
	$up_array['store_id'] = $FANNIE_STORE_ID;
	$up_array['upc'] = $dbc->escape($_REQUEST['upc']);
	$up_array['special_price'] = 0.00;
	$up_array['specialpricemethod'] = 0;
	$up_array['specialgroupprice'] = 0.00;
	$up_array['specialquantity'] = 0;
	$up_array['mixmatchcode'] = "'0'";
	$up_array['discounttype'] = 0;
	$up_array['start_date'] = "'1900-01-01'";
	$up_array['end_date'] = "'1900-01-01'";
	$up_array['numflag'] = 0;
	$up_array['store_id'] = 0;
	$dbc->smart_insert('products',$up_array);
}

// apply HQ updates to non-HQ records
// where the current value matches the old
// HQ value
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

if ($dbc->table_exists('prodExtra')){
	$arr = array();
	$arr['manufacturer'] = $dbc->escape($_REQUEST['manufacturer']);
	$arr['distributor'] = $dbc->escape($_REQUEST['distributor']);
	$arr['cost'] = $up_array['cost'];
	$arr['location'] = $dbc->escape($_REQUEST['location']);

	$checkR = $dbc->query("SELECT upc FROM prodExtra WHERE upc='$upc'");
	if ($dbc->num_rows($checkR) == 0){
		// if prodExtra record doesn't exist, needs more values
		$arr['upc'] = $dbc->escape($upc);
		$arr['variable_pricing'] = 0;
		$arr['margin'] = 0;
		$arr['case_quantity'] = "''";
		$arr['case_cost'] = 0.00;
		$arr['case_info'] = "''";
		$dbc->smart_insert('prodExtra',$arr);
	}
	else {
		$dbc->smart_update('prodExtra',$arr,"upc='$upc'");
	}
}
if ($dbc->table_exists("prodUpdate")){
	$puarray = array(
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
	$dbc->smart_insert('prodUpdate',$puarray);
}
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
        $query2 = "SELECT dept_name FROM departments where dept_no = " .$dept;
        $result2 = $dbc->query($query2);
		$row2 = $dbc->fetch_array($result2);
		
		$subdept=$row["subdept"];
		$query2a = "SELECT subdept_name FROM subdepts WHERE subdept_no = " .$subdept;
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

        
        echo "</table>";
		echo "<hr>"; 
		echo "<form action='itemMaint.php' method=post>";
        echo "<input name=upc type=text id=upc> Enter UPC/PLU here<br>";
        echo "<input name=submit type=submit value=submit>";
        echo "</form>";
?>
<script type="text/javascript">
$(document).ready(function(){
	$('#upc').focus();
});
</script>
<?php
include('../src/footer.html');
?>
