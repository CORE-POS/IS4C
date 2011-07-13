<?php
include('../../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (isset($_GET['action'])){
	$out = $_GET['action']."`";
	switch($_GET['action']){
	case 'savePrice':
		$upc = $_GET['upc'];
		$price = $_GET['price'];

		$upQ = "update products set normal_price = $price where upc='$upc'";
		$upR = $sql->query($upQ);

		require('../../queries/laneUpdates.php');
		updateProductAllLanes($upc,$db);

		$out .= $upc."`";

		$query = "SELECT u.*,p.cost FROM unfi_all as u left join prodExtra as p on u.upc=p.upc
			WHERE u.upc = '$upc'";
		$result = $sql->query($query);
		$row = $sql->fetch_array($result);
		
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

		if($diff==1){
		 $bg = "ff6677";
		 $sort = 1;
		}else{
		 $bg = "#ccffcc";
		 $sort = 0;
		}
		$out .=  "<td bgcolor=$bg><a href=http://key/queries/productTest.php?upc=$pupc>$pupc</td><td bgcolor=$bg>$pdesc</td><td bgcolor=$bg>$cost</td><td bgcolor=$bg id=pricefield$pupc><a href=\"\" onclick=\"editPrice('$pupc'); return false;\">$pprice</a></td>";
		$out .=  "<td bgcolor=$bg>$ourMarg</td><td bgcolor=$bg><a href=\"\" onclick=\"editUnfiPrice('$upc'); return false;\">$uprice</a></td><td bgcolor=$bg>$unMarg</td><td bgcolor=$bg>$cat</td><td bgcolor=$bg>$sort</td><td bgcolor=$bg><input type=checkbox name=pricechange[] value=$pupc>UNFI";
		break;
	case 'saveUnfiPrice':
		$upc = $_GET['upc'];
		$price = $_GET['price'];
		$upQ = "update unfi_order set wfc_srp=$price where upcc='$upc'";
		$upR = $sql->query($upQ);
		break;
	case 'toggleVariable':
		$upc = $_GET['upc'];
		$val = ($_GET['toggle'] == "true") ? 1 : 0;
		$upQ = "update prodExtra set variable_pricing=$val where upc='$upc'";
		$upR = $sql->query($upQ);
		break;
	}
	echo $out;
	return;
}

//Get buyID from index form. Set to 99 (all buyers) if not set.
if(isset($_POST['buyer'])){
   $buyID = $_POST['buyer'];
}elseif(isset($_GET['buyer'])){
   $buyID = $_GET['buyer'];
}else{
   $buyID = '99';
}

$filter = isset($_REQUEST['filter'])?$_REQUEST['filter']:'';

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
}

//Click to create Excel page...
echo "<a href=price_compare.php?excel=1&buyer=$buyID&filter=$filter>Dump to Excel</a><br>";

//Connect to mysql server

$mysql = new SQLManager('nexus.wfco-op.store','MYSQL','IS4C','root');

if($buyID == 99){
   $getCatQ = "SELECT unfi_cat FROM unfi_cat";
}else{
   $getCatQ = "SELECT unfi_cat FROM unfi_cat WHERE buyer = $buyID";
}

//echo $getCatQ;

$getCatR = $mysql->query($getCatQ);

   $sort = isset($_GET['sort'])?$_GET['sort']:'';

   //create form page
   echo "<form action=price_update.php method=post>";
   echo "<tr><td><input type=submit name=submit value=submit></td><td><input type=reset value=reset name=reset></td></tr>";
   echo "<table><th>Our UPC<th>Our Desc<th>Cost<th>Our Price<th>Our Margin<th>UNFI SRP<th>UNFI Margin<th>";
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
   $i = 1;

$strCat = "(";

while($getCatW = $mysql->fetch_array($getCatR)){
   $cat = $getCatW['unfi_cat'];
   //echo $cat . "<br>";
   $strCat = $strCat.$cat.",";
}

$strCat = substr($strCat,0,-1);
$strCat = $strCat . ")";
//echo $strCat;

   //If sort is set (header has been clicked, test to see if we need to reverse
   //the sort of the last refresh.
   $query = "";
   if(isset($_GET['sort'])){
      if(substr($sort,-1,1) == 1){
         $sort = substr($sort,0,-1) . " DESC ";
      }
      //echo $sort;
     if(substr($sort,0,3) == 'cat'){
         $query = "SELECT u.*,p.cost,p.variable_pricing FROM $unfi_table as u left join prodExtra as p on u.upc=p.upc
		    WHERE cat IN$strCat order by $sort,department,u.upc";
     }
     else if (strstr("variable_pricing",$sort)){
	 $query = "SELECT u.*,p.cost,p.variable_pricing FROM $unfi_table as u left join prodExtra as p on u.upc=p.upc
		    WHERE cat IN$strCat order by $sort,cat,department,u.upc";
      }else{
        $query = "SELECT u.*,p.cost,p.variable_pricing FROM $unfi_table as u left join prodExtra as p on u.upc=p.upc
		  WHERE cat IN$strCat order by $sort,cat,department,u.upc";
      }
   }else{
      $query = "SELECT u.*,p.cost,p.variable_pricing FROM $unfi_table as u left join prodExtra as p on u.upc=p.upc
		 WHERE cat IN$strCat order by cat, department, u.upc";
   }
   $result = $sql->query($query);

   while($row = $sql->fetch_array($result)){
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
      echo  "<td bgcolor=$bg><a href=../../queries/productTest.php?upc=$pupc>$pupc</td><td bgcolor=$bg>$pdesc</td><td bgcolor=$bg>$cost</td><td bgcolor=$bg id=pricefield$pupc><a href=\"\" onclick=\"editPrice('$pupc'); return false;\">$pprice</a></td>";
      echo  "<td bgcolor=$bg>$ourMarg</td><td id=unfiprice$pupc bgcolor=$bg><a href=\"\" onclick=\"editUnfiPrice('$pupc'); return false;\">$uprice</a></td><td bgcolor=$bg>$unMarg</td><td bgcolor=$bg>$cat</td><td bgcolor=$bg>$sort</td>";
      echo "<td bgcolor=$bg><input type=checkbox id=var$pupc ";
      if ($var == 1)
	echo "checked ";
      echo "onclick=\"toggleVariable('$pupc');\" /></td>";
      echo "<td bgcolor=$bg><input type=checkbox name=pricechange[] value=$pupc>UNFI";
      echo "</tr>";
   }

echo "</table>";
echo "<input type=hidden value=$buyID name=buyID>";
echo "</form>";
?>
