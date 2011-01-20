<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

if (isset($_GET['action'])){
	$out = $_GET['action']."`";
	switch($_GET['action']){
	case 'savePrice':
		$upc = $_GET['upc'];
		$price = $_GET['price'];

		$upQ = "update products set normal_price = $price where upc='$upc'";
		$upR = $dbc->query($upQ);

		require($FANNIE_ROOT.'item/laneUpdates.php');
		updateProductAllLanes($upc,$db);

		$out .= $upc."`";

		$query = "SELECT u.*,p.cost,p.variable_pricing FROM unfi_all as u left join prodExtra as p on u.upc=p.upc
			WHERE u.upc = '$upc'";
		$result = $dbc->query($query);
		$row = $dbc->fetch_array($result);
		
		$pupc = $row[0];
		$uupc = $row['upcc'];
		$pdesc = $row['description'];
		$udesc = $row['item_desc'];
		$pprice = $row['normal_price'];
		$uprice = $row['wfc_srp'];
		$cat = $row['cat'];
		$ourMarg = (100 * (float)$row['our_margin'])."%";
		$unMarg = (100 * (float)$row['unfi_margin'])."%";
		$dept = $row['department'];
		$diff = $row['diff'];
		$cost = $row['cost'];
		$var = $row['variable_pricing'];

		if($diff==1){
		 $bg = "ff6677";
		 $sort = 1;
		}else{
		 $bg = "#ccffcc";
		 $sort = 0;
		}
		$out .=  "<td bgcolor=$bg><a href=http://key/queries/productTest.php?upc=$pupc>$pupc</td><td bgcolor=$bg>$pdesc</td><td bgcolor=$bg>$cost</td><td bgcolor=$bg id=pricefield$pupc><a href=\"\" onclick=\"editPrice('$pupc'); return false;\">$pprice</a></td>";
		$out .=  "<td bgcolor=$bg>$ourMarg</td><td id=unfiprice$upc bgcolor=$bg><a href=\"\" onclick=\"editUnfiPrice('$upc'); return false;\">$uprice</a></td><td bgcolor=$bg>$unMarg</td><td bgcolor=$bg>$cat</td><td bgcolor=$bg>$sort</td>";
	        $out .= "<td bgcolor=$bg><input type=checkbox id=var$pupc ";
	        if ($var == 1)
			$out .= "checked ";
	        $out .= "onclick=\"toggleVariable('$pupc');\" /></td>";
		$out .= "<td bgcolor=$bg><input type=checkbox name=pricechange[] value=$pupc>UNFI</td>";
		break;
	case 'saveUnfiPrice':
		$upc = $_GET['upc'];
		$price = $_GET['price'];
		$upQ = "update unfi_order set wfc_srp=$price where upcc='$upc'";
		$upR = $dbc->query($upQ);
		break;
	case 'toggleVariable':
		$upc = $_GET['upc'];
		$val = ($_GET['toggle'] == "true") ? 1 : 0;
		$upQ = "update prodExtra set variable_pricing=$val where upc='$upc'";
		$upR = $dbc->query($upQ);
		break;
	}
	echo $out;
	return;
}

//Get buyID from index form. Set to 99 (all buyers) if not set.
$buyID = (isset($_REQUEST['buyer']))?$_REQUEST['buyer']:99;
$filter = (isset($_REQUEST['filter']))?$_REQUEST['filter']:'No';
$unfi_table = "unfi_diff";
if ($filter == "Yes")
	$unfi_table = "unfi_all";

//echo $buyID . "<br>";
//Test to see if we are dumping this to Excel. Apply Excel header if we are.
if(isset($_GET['excel'])){
   header('Content-Type: application/ms-excel');
   header('Content-Disposition: attachment; filename="UNFI_prices.xls"');
}
else {
?>
<html><head>
<style type=text/css>
a {
	color: blue;
}
</style>
<script src="price_compare.js" type="text/javascript"></script>
</head>
<?php
echo "<a href=price_compare.php?excel=1&buyer=$buyID&filter=$filter>Dump to Excel</a><br>";
}

//Connect to mysql server

$mysql = new SQLManager('nexus.wfco-op.store','MYSQL','IS4C','root');


   $sort = isset($_GET['sort'])?$_GET['sort']:'';

   //create form page
if (!isset($_GET['excel'])){
   echo "<form action=price_update.php method=post>";
   echo "<tr><td><input type=submit name=submit value=submit></td><td><input type=reset value=reset name=reset></td></tr>";
}
   echo "<table><th>Our UPC</th><th>Our Desc</th><th>Cost</th><th>Our Price</th><th>Our Margin</th><th>UNFI SRP</th><th>UNFI Margin</th><th>";
if (!isset($_GET['excel'])){
   if($sort=='cat'){
      echo "<a href=price_compare.php?sort=cat1&buyer=$buyID&filter=$filter>Cat<a><th>";
   }else{
      echo "<a href=price_compare.php?sort=cat&buyer=$buyID&filter=$filter>Cat<a><th>";
   }
   if($sort == 'diff'){
      echo "<a href=price_compare.php?sort=diff1&buyer=$buyID&filter=$filter>Diff<a><th>";
   }else{
     echo "<a href=price_compare.php?sort=diff&buyer=$buyID&filter=$filter>Diff<a><th>";
   }
   if($sort=='variable_pricing'){
     echo "<a href=price_compare.php?sort=variable_pricing&buyer=$buyID&filter=$filter>Var</a>";
   }
   else {
     echo "<a href=price_compare.php?sort=variable_pricing&buyer=$buyID&filter=$filter>Var</a>";
   }
}
else
   echo "Cat</th><th>Diff</th><th>Var</th></tr>";
   $i = 1;

   //If sort is set (header has been clicked, test to see if we need to reverse
   //the sort of the last refresh.
   $query = "";
   $where = ($buyID==99)?'':"WHERE s.superID = $buyID";
   $join = ($buyID==99)?'':"LEFT JOIN superdepts as s ON q.department=s.dept_ID";
   if(isset($_GET['sort'])){
      if(substr($sort,-1,1) == 1){
         $sort = substr($sort,0,-1) . " DESC ";
      }
      //echo $sort;
     if(substr($sort,0,3) == 'cat'){
         $query = "SELECT u.*,p.cost,p.variable_pricing FROM $unfi_table as u left join prodExtra as p on u.upc=p.upc
		    LEFT JOIN products AS q ON q.upc=u.upc $join
		    $where order by $sort,u.department,u.upc";
     }
     else if (strstr("variable_pricing",$sort)){
	 $query = "SELECT u.*,p.cost,p.variable_pricing FROM $unfi_table as u left join prodExtra as p on u.upc=p.upc
		    LEFT JOIN products AS q ON q.upc=u.upc $join
		    $where order by $sort,cat,u.department,u.upc";
      }else{
        $query = "SELECT u.*,p.cost,p.variable_pricing FROM $unfi_table as u left join prodExtra as p on u.upc=p.upc
		    LEFT JOIN products AS q ON q.upc=u.upc $join
		  $where order by $sort,cat,u.department,u.upc";
      }
   }else{
      $query = "SELECT u.*,p.cost,p.variable_pricing FROM $unfi_table as u left join prodExtra as p on u.upc=p.upc
		    LEFT JOIN products AS q ON q.upc=u.upc $join
		 $where order by cat, u.department, u.upc";
   }
   //echo $query;
   $result = $dbc->query($query);

   while($row = $dbc->fetch_array($result)){
      $pupc = $row[0];
      $uupc = $row['upcc'];
      $pdesc = $row['description'];
      $udesc = $row['item_desc'];
      $pprice = $row['normal_price'];
      $uprice = $row['wfc_srp'];
      $cat = $row['cat'];
      $ourMarg = (100 * (float)$row['our_margin'])."%";
      $unMarg = (100 * (float)$row['unfi_margin'])."%";
      $dept = $row['department'];
      $diff = $row['diff'];
      $cost = $row['cost'];
      $var = $row['variable_pricing'];
   
      if($diff==1){
         $bg = "ff6677";
         $sort = 1;
      }else{
         $bg = "#ccffcc";
         $sort = 0;
      }
      echo "<tr id=row$pupc>";
      if (!isset($_REQUEST['excel'])){
	      echo  "<td bgcolor=$bg><a href={$FANNIE_URL}item/itemMaint.php?upc=$pupc>$pupc</td><td bgcolor=$bg>$pdesc</td><td bgcolor=$bg>$cost</td><td bgcolor=$bg id=pricefield$pupc><a href=\"\" onclick=\"editPrice('$pupc'); return false;\">$pprice</a></td>";
	      echo  "<td bgcolor=$bg>$ourMarg</td><td id=unfiprice$pupc bgcolor=$bg><a href=\"\" onclick=\"editUnfiPrice('$pupc'); return false;\">$uprice</a></td><td bgcolor=$bg>$unMarg</td><td bgcolor=$bg>$cat</td><td bgcolor=$bg>$sort</td>";
	      echo "<td bgcolor=$bg><input type=checkbox id=var$pupc ";
	      if ($var == 1)
		echo "checked ";
	      echo "onclick=\"toggleVariable('$pupc');\" /></td>";
	      echo "<td bgcolor=$bg><input type=checkbox name=pricechange[] value=$pupc>UNFI";
      }
      else {
		echo "<td bgcolor=$bg>$pupc</td>";
		echo "<td bgcolor=$bg>$pdesc</td>";
		echo "<td bgcolor=$bg>$cost</td>";
		echo "<td bgcolor=$bg>$pprice</td>";
		echo "<td bgcolor=$bg>$ourMarg</td>";
		echo "<td bgcolor=$bg>$uprice</td>";
		echo "<td bgcolor=$bg>$unMarg</td>";
		echo "<td bgcolor=$bg>$cat</td>";
		echo "<td bgcolor=$bg>$sort</td>";
		echo "<td bgcolor=$bg>".($var==1?'X':'')."</td>";
		echo "<td bgcolor=$bg>UNFI</td>";
      }
      echo "</tr>";
   }

echo "</table>";
if (!isset($_GET['excel'])){
	echo "<input type=hidden value=$buyID name=buyID>";
	echo "</form>";
}
?>
