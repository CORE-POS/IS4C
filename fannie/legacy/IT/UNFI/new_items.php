<?php
include('../../../config.php');

require($FANNIE_ROOT.'src/SQLManager.php');
include('../../db.php');
require($FANNIE_ROOT.'item/pricePerOunce.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

if (isset($_GET["action"])){
	$out = $_GET["action"]."`";
	switch($_GET["action"]){
	case 'getBrands':
		$cat = $_GET["catID"];
		$q = $sql->prepare("SELECT u.brand FROM vendorItems AS u
		      LEFT JOIN products AS p ON u.upc=p.upc
		      WHERE p.upc IS NULL AND u.vendorDept = ?
		      AND u.vendorID=1
		      GROUP BY u.brand ORDER BY u.brand");
		$r = $sql->execute($q, array($cat));
		$out .= "<option value=\"\">----------</option>";
		while($w = $sql->fetch_row($r)){
			$out .= "<option>".$w[0]."</option>";
		}
		break;
	}
	echo $out;
	return;
}

if (isset($_GET['cat'])){
	$catID = $_GET["cat"];
	$brand = "";
    $brandArgs = array();
	if (isset($_GET["brands"]) && $_GET["brands"] != "") {
		$brand = " AND v.brand=? ";
        $brandArgs[] = $_GET['brands'];
    }

	$mysql = new SQLManager('mysql.wfco-op.store','mysql','IS4C','is4c','is4c');
	$dsubs = " superID IN (";
    $dargs = array();
	$buyersP = $mysql->prepare("SELECT buyer FROM unfi_cat WHERE unfi_cat=?");
	$buyersR = $mysql->execute($buyersP, array($catID));
	$buyers = array();
	while($buyersW = $mysql->fetch_row($buyersR))
		array_push($buyers,$buyersW[0]);
	foreach($buyers as $b) {
		$dsubs .= "?,";
        $dargs[] = $b;
    }
	$dsubs = substr($dsubs,0,strlen($dsubs)-1).")";

	$SHELFTAG_PAGES = array(
		1 => "Bulk",
		2 => "Cool",
		3 => "Deli",
		4 => "Grocery",
		5 => "HBC",
		8 => "Meat",
		9 => "Gen Merch"
	);

	$deptP = $sql->prepare("SELECT dept_no,dept_name FROM departments AS d
		LEFT JOIN MasterSuperDepts AS m ON d.dept_no=m.dept_ID
		WHERE $dsubs ORDER BY superID,dept_no");
    $deptR = $sql->execute($deptP, $dargs);
	$depts = "<select name=dept[]><option value=-1></option>";
	while($deptW = $sql->fetch_row($deptR)){
		$depts .= sprintf("<option value=%s>%s %s</option>",
				$deptW["dept_no"],$deptW["dept_no"],$deptW["dept_name"]);
	}
	$depts .= "</select>";

	$dataQ = $sql->prepare("SELECT v.upc,brand,v.description,v.vendorDept,
		s.srp,v.cost, 
		v.size,v.units,v.sku FROM vendorItems AS v 
		LEFT JOIN products AS p ON v.upc = p.upc 
		left join vendorSRPs as s ON v.upc=s.upc AND v.vendorID=s.vendorID
		WHERE p.upc IS NULL and v.vendorID=1
		$brand AND v.vendorDept=? order by v.vendorDept,v.brand,v.description");
    $brandArgs[] = $catID;
	$dataR = $sql->execute($dataQ, $brandArgs);

	echo "<form action=new_items.php method=post>";
	echo "<b>Shelf tag page:</b> <select name=shelftagpage>";
	foreach($buyers as $b){
		printf("<option value=%s>%s</option>",$b,$SHELFTAG_PAGES[$b]);
	}
	echo "</select><p />";
	echo "<table cellspacing=0 cellpadding=5 border=1>";
	echo "<tr><th>UPC</th><th>Brand</th><th>Description</th>";
	echo "<th>UNFI Cat.</th><th>Cost</th><th>Dept</th><th>SRP</th></tr>";
	while ($dataW = $sql->fetch_row($dataR)){
		echo "<tr>";
		printf("<td>%s</td>",$dataW["upc"]);
		printf("<input type=hidden name=upc[] value=\"%s\" />",$dataW["upc"]);
		printf("<td>%s</td>",$dataW["brand"]);
		printf("<input type=hidden name=brand[] value=\"%s\" />",$dataW["brand"]);
		printf("<td>%s</td>",$dataW["description"]);
		printf("<input type=hidden name=desc[] value=\"%s\" />",$dataW["description"]);
		printf("<td>%s</td>",$dataW["vendorDept"]);
		printf("<td>%s</td>",$dataW["cost"]);
		printf("<input type=hidden name=cost[] value=\"%s\" />",$dataW["cost"]);
		echo "<td>$depts</td>";
		printf("<td><input type=text name=price[] size=4 value=\"%s\" /></td>",$dataW["srp"]);
		echo "</tr>";
		printf("<input type=hidden name=pack[] value=\"%s\" />",$dataW["size"]);
		printf("<input type=hidden name=size[] value=\"%s\" />",$dataW["units"]);
		printf("<input type=hidden name=sku[] value=\"%s\" />",$dataW["sku"]);
	}
	echo "</table>";
	echo "<input type=submit value=\"Add Items\" />";
	echo "</form>";

}
else if (isset($_POST["upc"])){
	$upcs = $_POST['upc'];
	$brands = $_POST["brand"];
	$descs = $_POST["desc"];
	$costs = $_POST["cost"];
	$depts = $_POST["dept"];
	$prices = $_POST["price"];
	$packs = $_POST["pack"];
	$sizes = $_POST["size"];
	$skus = $_POST["sku"];

	$bID = $_POST["shelftagpage"];

    $product = new ProductsModel($sql);
    $product->pricemethod(0);
    $product->groupprice(0);
    $product->quantity(0);
    $product->special_price(0);
    $product->specialpricemethod(0);
    $product->specialgroupprice(0);
    $product->specialquantity(0);
    $product->start_date('1900-01-01');
    $product->end_date('1900-01-01');
    $product->size(0);
    $product->scale(0);
    $product->mixmatchcode(0);
    $product->advertised(1);
    $product->tareweight(0);
    $product->discount(1);
    $product->discounttype(0);
    $product->unitofmeasure('lb');
    $product->wicable(0);
    $product->qttyEnforced(0);
    $product->inUse(1);
    $product->numflag(0);
    $product->subdept(0);
    $product->deposit(0);
    $product->local(0);
    $product->idEnforced(0);
    $product->scaleprice(0);
    $product->cost(0);

    $taxfsP = $sql->prepare("SELECT dept_tax,dept_fs FROM departments WHERE dept_no=?");
    $upP = $sql->prepare("INSERT INTO prodUpdate (upc,description,price,dept,tax,fs,scale,likeCode,modified,"
            .$sql->identifier_escape('user').",
            forceQty,noDisc,inUse) VALUES (?,?,?,?,?,?,0,0,".$sql->now().",-2,0,1,1)");
    $xtraP = $sql->prepare("INSERT INTO prodExtra (upc,distributor,manufacturer,cost,margin,variable_pricing,location)
        VALUES (?,'UNFI',?,?,0,0,'')");
    $barP = $sql->prepare("INSERT INTO shelftags (id,upc,description,normal_price,brand,sku,units,size,vendor,
        pricePerUnit) VALUES (?,?,?,?,?,?,?,?,'UNFI',?)");
for ($i=0; $i<count($depts); $i++){
		if ($depts[$i] == -1) continue;
		
		printf("Adding item <a href={$FANNIE_URL}legacy/queries/productTest.php?upc=%s>%s</a> %s<br />",$upcs[$i],$upcs[$i],$descs[$i]);
		flush();

		$taxfsR = $sql->execute($taxfsP, array($depts[$i]));
		$tax = 0;
		$fs = 0;
		while($taxfsW = $sql->fetch_row($taxfsR)){
			$tax = $taxfsW[0];
			$fs = $taxfsW[1];
		}		

        $product->upc($upcs[$i]);
        $product->description($descs[$i]);
        $product->normal_price($prices[$i]);
        $product->department($depts[$i]);
        $product->tax($tax);
        $product->foodstamp($fs);
        $product->cost($costs[$i]);
        $product->save();

		$sql->execute($upP, array($upcs[$i], $descs[$i], $prices[$i], $depts[$i], $tax, $fs));
		$sql->execute($xtraP, array($upcs[$i], $brands[$i], $costs[$i]));

		$ppo = pricePerOunce($prices[$i],$sizes[$i]);
		$sql->execute($barP, array($bID, $upcs[$i], $descs[$i], $prices[$i], $brands[$i], $skus[$i], $sizes[$i], $packs[$i], $ppo));

        $product->pushToLanes();
	}
	echo "Pushing products to the lanes<br />";
	flush();
	echo "<script type=text/javascript>window.top.location='new_items.php';</script>";
}
else {
	$dataR = $sql->query("SELECT categoryID,name FROM unfiCategories ORDER BY categoryID");
	echo "<html><head><title>Add UNFI items</title>";
	echo "<script type=text/javascript src=new_items.js></script>";
	echo "</head>";

	echo "Choose UNFI category<br />";
	echo "<form action=new_items.php method=get>";
	echo "<select name=cat size=25 onchange=\"getBrands(this.value)\">";
	while ($dataW = $sql->fetch_row($dataR)){
		printf("<option value=\"%s\">%s - %s</option>",
			$dataW["categoryID"],$dataW["categoryID"],$dataW["name"]);
	}
	echo "</select><p />";

	echo "(Optional) choose a brand<br />";
	echo "<select id=brands name=brands>";
	echo "<option value=\"\">----------</option>";
	echo "</select><p />";

	echo "<input type=submit value=Continue />";
	echo "</form>";
}

?>
