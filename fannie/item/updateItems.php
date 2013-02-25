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
require_once($FANNIE_ROOT.'classlib2.0/data/controllers/ProductsController.php');
include('laneUpdates.php');

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
$up_array['description'] = isset($_REQUEST['descript'])?$_REQUEST['descript']:'';
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

/* turn on volume pricing if specified, but don't
   alter pricemethod if it's already non-zero */
if (isset($_REQUEST['doVolume']) && is_numeric($_REQUEST['vol_price']) && is_numeric($_REQUEST['vol_qtty'])){
	$up_array['pricemethod'] = $_REQUEST['pricemethod'];
	if ($up_array['pricemethod']==0) $up_array['pricemethod']=2;
	$up_array['groupprice'] = $_REQUEST['vol_price'];
	$up_array['quantity'] = $_REQUEST['vol_qtty'];
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

//$dbc->smart_update('products',$up_array,"upc='$upc'");
ProductsController::update($upc, $up_array);

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

include(dirname(__FILE__).'/modules/ScaleItemModule.php');
$mod = new ScaleItemModule();
$mod->SaveFormData($upc);

include(dirname(__FILE__).'/modules/ItemFlagsModule.php');	
$mod = new ItemFlagsModule();
$mod->SaveFormData($upc);

include(dirname(__FILE__).'/modules/LikeCodeModule.php');	
$mod = new LikeCodeModule();
$mod->SaveFormData($upc);

/* push updates to the lanes */
updateProductAllLanes($upc);

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
