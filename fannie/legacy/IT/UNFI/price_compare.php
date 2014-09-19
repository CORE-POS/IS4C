<?php
include('../../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');
if (!class_exists('FannieAPI'))
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

$UNFI_ALL_QUERY = "
    select p.upc,
    v.upc as upcc, 
    p.description,
    v.description as item_desc,
    v.cost * v.units as wholesale,
    v.cost * v.units as vd_cost,
    p.normal_price,
    v.sku as unfi_sku,
    s.srp as wfc_srp,
    v.vendorDept as cat,
    p.department,
    CASE WHEN p.normal_price = 0 THEN 0 ELSE
        CONVERT((p.normal_price - v.cost)/p.normal_price,decimal(10,2)) 
    END as our_margin,
    CONVERT((s.srp- v.cost)/ s.srp,decimal(10,2))
    as unfi_margin,
    case when s.srp > p.normal_price then 1 else 0 END as diff,
    x.cost AS cost,
    x.variable_pricing
    from vendorItems AS v
    INNER JOIN products as p ON v.upc=p.upc
    LEFT JOIN prodExtra AS x ON p.upc=x.upc
    LEFT JOIN vendorSRPs AS s ON v.vendorID=s.vendorID AND v.upc=s.upc
    where 
    v.vendorID=1";

if (isset($_GET['action'])){
	$out = $_GET['action']."`";
	switch($_GET['action']){
	case 'savePrice':
		$upc = $_GET['upc'];
		$price = $_GET['price'];

        $model = new ProductsModel($sql);
        $model->upc($upc);
        $model->normal_price($price);
        $model->save();
        $model->pushToLanes();

		$out .= $upc."`";

		$prep = $sql->prepare($UNFI_ALL_QUERY . ' AND p.upc = ?');
		$result = $sql->execute($prep, array($upc));
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
		$out .=  "<td bgcolor=$bg><a href=\"http://key/queries/productTest.php?upc=$pupc\" target=\"__unfi_pc\">$pupc</td><td bgcolor=$bg>$pdesc</td><td bgcolor=$bg>$cost</td><td bgcolor=$bg id=pricefield$pupc>$pprice</td>";
		$out .=  "<td bgcolor=$bg>$ourMarg</td><td bgcolor=$bg><a href=\"\" onclick=\"editUnfiPrice('$upc'); return false;\">$uprice</a></td><td bgcolor=$bg>$unMarg</td><td bgcolor=$bg>$cat</td><td bgcolor=$bg>$sort</td><td bgcolor=$bg><input type=checkbox name=pricechange[] id=\"check$pupc\" value=$pupc><label for=\"check$pupc\">UNFI</label>";
		break;
	case 'saveUnfiPrice':
		$upc = $_GET['upc'];
		$price = $_GET['price'];
		$upQ = $sql->prepare("update vendorSRPs set srp=? where vendorID=1 AND upc=?");
		$upR = $sql->execute($upQ, array($price, $upc));
		break;
	case 'toggleVariable':
		$upc = $_GET['upc'];
		$val = ($_GET['toggle'] == "true") ? 1 : 0;
		$upQ = $sql->prepare("update prodExtra set variable_pricing=? where upc=?");
		$upR = $sql->execute($upQ, array($val, $upc));
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

/** deprecating unfi_* tables
$unfi_table = "unfi_diff";
if ($filter == "Yes")
	$unfi_table = "unfi_all";
*/

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

$mysql = new SQLManager('mysql.wfco-op.store','MYSQL','IS4C','is4c','is4c');

$getCatQ = "SELECT unfi_cat FROM unfi_cat";
$getCatArgs = array();
if ($buyID != 99){
   $getCatQ = "SELECT unfi_cat FROM unfi_cat WHERE buyer = ?";
   $getCatArgs = array($buyID);
}

//echo $getCatQ;

$getCatP = $mysql->prepare($getCatQ);
$getCatR = $mysql->execute($getCatP, $getCatArgs);

   $sort = isset($_GET['sort'])?$_GET['sort']:'';
   // validate sort option
   switch($sort) {
       case 'cat':
       case 'diff':
       case 'variable_pricing':
            if ($sort === 0 || $sort === True) {
                $sort = 'cat';
            }
            $sort .= ' ASC';
            if ($sort != 'cat') {
                $sort .= ', cat';
            }
            break;
       case 'cat1':
       case 'diff1':
       case 'variable_pricing1':
            if ($sort === 0 || $sort === True) {
                $sort = 'cat1';
            }
            $sort = rtrim($sort, '1');
            $sort .= ' DESC';
            if ($sort != 'cat') {
                $sort .= ', cat';
            }
            break;
       default:
            $sort = 'cat ASC';
            break;
   }

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
     echo "<a href=price_compare.php?sort=variable_pricing1&buyer=$buyID&filter=$filter>Var</a>";
   }
   else {
     echo "<a href=price_compare.php?sort=variable_pricing&buyer=$buyID&filter=$filter>Var</a>";
   }
   $i = 1;

$strCat = "(";
$cat_args = array();
while($getCatW = $mysql->fetch_array($getCatR)){
   $cat = $getCatW['unfi_cat'];
   //echo $cat . "<br>";
   $strCat .= "?,";
   $cat_args[] = $cat;
}

$strCat = substr($strCat,0,-1);
$strCat = $strCat . ")";
//echo $strCat;

    /** deprecating unfi_* tables
   $prep = $sql->prepare("SELECT u.*,p.cost,p.variable_pricing FROM $unfi_table as u left join prodExtra as p on u.upc=p.upc
        WHERE cat IN$strCat order by $sort,department,u.upc");
   */
   if ($filter != 'Yes')
       $UNFI_ALL_QUERY .= ' AND p.normal_price <> s.srp ';
   $prep = $sql->prepare($UNFI_ALL_QUERY . " AND v.vendorDept IN $strCat ORDER BY $sort, p.department, p.upc");
   $result = $sql->execute($prep, $cat_args);

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
      echo  "<td bgcolor=$bg><a href=\"/queries/productTest.php?upc=$pupc\" target=\"__unfi_pc\">$pupc</td><td bgcolor=$bg>$pdesc</td><td bgcolor=$bg>$cost</td><td bgcolor=$bg id=pricefield$pupc>$pprice</td>";
      echo  "<td bgcolor=$bg>$ourMarg</td><td id=unfiprice$pupc bgcolor=$bg><a href=\"\" onclick=\"editUnfiPrice('$pupc'); return false;\">$uprice</a></td><td bgcolor=$bg>$unMarg</td><td bgcolor=$bg>$cat</td><td bgcolor=$bg>$sort</td>";
      echo "<td bgcolor=$bg><input type=checkbox id=var$pupc ";
      if ($var == 1)
	echo "checked ";
      echo "onclick=\"toggleVariable('$pupc');\" /></td>";
      echo "<td bgcolor=$bg><input type=checkbox name=pricechange[] id=\"check$pupc\" value=$pupc><label for=\"check$pupc\">UNFI</label>";
      echo "</tr>";
   }

echo "</table>";
echo "<input type=hidden value=$buyID name=buyID>";
echo "</form>";
?>
