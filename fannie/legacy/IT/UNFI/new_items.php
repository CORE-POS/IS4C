<?php
include('../../../config.php');

require($FANNIE_ROOT.'src/SQLManager.php');
include('../../db.php');
require($FANNIE_ROOT.'item/pricePerOunce.php');
require('../../queries/laneUpdates.php');

if (isset($_GET["action"])){
	$out = $_GET["action"]."`";
	switch($_GET["action"]){
	case 'getBrands':
		$cat = $_GET["catID"];
		$q = "SELECT u.brand FROM vendorItems AS u
		      LEFT JOIN products AS p ON u.upc=p.upc
		      WHERE p.upc IS NULL AND u.vendorDept = $cat
		      GROUP BY u.brand ORDER BY u.brand";
		$r = $sql->query($q);
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
	if (isset($_GET["brands"]) && $_GET["brands"] != "")
		$brand = " AND v.brand='".$_GET["brands"]."' ";

	$mysql = new SQLManager('mysql.wfco-op.store','MYSQL','IS4C','is4c','is4c');
	$dsubs = " superID IN (";
	$buyersR = $mysql->query("SELECT buyer FROM unfi_cat WHERE unfi_cat=$catID");
	$buyers = array();
	while($buyersW = $mysql->fetch_row($buyersR))
		array_push($buyers,$buyersW[0]);
	foreach($buyers as $b)
		$dsubs .= $b.",";
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

	$deptR = $sql->query("SELECT dept_no,dept_name FROM departments AS d
		LEFT JOIN MasterSuperDepts AS m ON d.dept_no=m.dept_ID
		WHERE $dsubs ORDER BY superID,dept_no");
	$depts = "<select name=dept[]><option value=-1></option>";
	while($deptW = $sql->fetch_row($deptR)){
		$depts .= sprintf("<option value=%s>%s %s</option>",
				$deptW["dept_no"],$deptW["dept_no"],$deptW["dept_name"]);
	}
	$depts .= "</select>";

	$dataQ = "SELECT v.upc,brand,v.description,v.vendorDept,
		s.srp,v.cost, 
		v.size,v.units,v.sku FROM vendorItems AS v 
		LEFT JOIN products AS p ON v.upc = p.upc 
		left join vendorSRPs as s ON v.upc=s.upc AND v.vendorID=s.vendorID
		WHERE p.upc IS NULL and v.vendorID=1
		AND v.vendorDept=$catID $brand order by v.vendorDept,v.brand,v.description";
	$dataR = $sql->query($dataQ);

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

	for ($i=0; $i<count($depts); $i++){
		if ($depts[$i] == -1) continue;
		
		printf("Adding item <a href={$FANNIE_URL}legacy/queries/productTest.php?upc=%s>%s</a> %s<br />",$upcs[$i],$upcs[$i],$descs[$i]);
		flush();

		$taxfsR = $sql->query("SELECT dept_tax,dept_fs FROM departments WHERE dept_no=$depts[$i]");
		$tax = 0;
		$fs = 0;
		while($taxfsW = $sql->fetch_row($taxfsR)){
			$tax = $taxfsW[0];
			$fs = $taxfsW[1];
		}		

		$prodQ = "INSERT INTO products (upc,description,normal_price,pricemethod,groupprice,quantity,
			special_price,specialpricemethod,specialgroupprice,specialquantity,start_date,end_date,
			department,size,tax,foodstamp,scale,mixmatchcode,modified,advertised,tareweight,
			discount,discounttype,unitofmeasure,wicable,qttyEnforced,inUse,numflag,subdept,
			deposit,local,idEnforced,scaleprice,cost) VALUES (
			'$upcs[$i]','$descs[$i]',$prices[$i],0,0,0,
			0,0,0,0,'1900-01-01','1900-01-01',
			$depts[$i],0,$tax,$fs,0,0,getdate(),1,0,
			1,0,'lb',0,0,1,0,0,0.00,0,0,0,0.00)";

		$upQ = "INSERT INTO prodUpdate (upc,description,price,dept,tax,fs,scale,likeCode,modified,[user],
				forceQty,noDisc,inUse) VALUES ('$upcs[$i]','$descs[$i]',$prices[$i],$depts[$i],
				$tax,$fs,0,0,getdate(),-2,0,1,1)";
	
		$xtraQ = "INSERT INTO prodExtra (upc,distributor,manufacturer,cost,margin,variable_pricing,location)
			VALUES ('$upcs[$i]','UNFI','$brands[$i]',$costs[$i],0,0,'')";

		$ppo = pricePerOunce($prices[$i],$sizes[$i]);
		$barQ = "INSERT INTO shelftags (id,upc,description,normal_price,brand,sku,units,size,vendor,
			pricePerUnit) VALUES ($bID,'$upcs[$i]','$descs[$i]',$prices[$i],'$brands[$i]','$skus[$i]','$sizes[$i]',
			'$packs[$i]','UNFI','$ppo')";

		$sql->query($prodQ);
		$sql->query($upQ);
		$sql->query($xtraQ);
		$sql->query($barQ);
	}
	echo "Pushing products to the lanes<br />";
	flush();
	syncProductsAllLanes();
	//exec("php fork.php sync products");
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
