<?php
include('../../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (isset($_GET['updated'])){
	$updated = $_GET['updated'];
	$upc = str_pad($_GET['upc'],13,'0',STR_PAD_LEFT);

	$upQ = "update products set description=q.description,
				    normal_price=q.normal_price,
				    pricemethod=q.pricemethod,
				    groupprice=q.groupprice,
				    quantity=q.quantity,
				    special_price=q.special_price,
				    special_price_method=q.special_price_method,
				    special_group_price=q.special_group_price,
				    special_quantity=q.special_quantity,
				    start_date=q.start_date,
				    end_date=q.end_date,
				    department=q.department,
				    size=q.size,
				    tax=q.tax,
				    foodstamp=q.foodstamp,
				    Scale=q.Scale,
				    mixmatchcode=q.mixmatchcode,
				    modified=q.modified,
				    advertised=q.advertised,
				    tareweight=q.tareweight,
				    discount=q.discount,
				    discounttype=q.discounttype,
				    unitofmeasure=q.unitofmeasure,
				    wicable=q.wicable,
				    qttyEnforced=q.qttyEnforced,
				    inUse=q.inUse
		from products as p, prodUpdateFull as q
		where p.upc = '$upc' and q.upc='$upc' and q.updated='$updated'";
	echo $upQ;
	//$upR = $sql->query($upQ);
}
else if (isset($_GET['upc'])){
	$upc = str_pad($_GET['upc'],13,'0',STR_PAD_LEFT);

	$prodQ = "select upSty,updated,modifiedby,upc,description,normal_price,
		  special_price,datepart(ss,updated),datepart(ms,updated) 
		  from prodUpdateFull where upc='$upc' order by updated desc";
	$prodR = $sql->query($prodQ);

	if ($sql->num_rows($prodR) == 0){
		echo "<b>Bad UPC</b>";
		return;
	}

	$prodW = $sql->fetch_array($prodR);
	$types = array('NEW ITEM','PRICE CHANGE','DELETED','BATCH START','BATCH END');	

	echo "<b>$prodW[3] : $prodW[4]</b><br />";
	echo "<table cellspacing=2 cellpadding=0 border=1>";
	echo "<tr><th>Type</th><th>Date</th><th>Price</th><th>Sale Price</th></tr>";
	do {
		echo "<tr>";
		echo "<td>{$types[$prodW[0]]}</td>";
		$str = datefix($prodW[1],$prodW[7],$prodW[8]);
		echo "<td>$str</td>";
		echo "<td>$prodW[5]</td>";
		echo "<td>$prodW[6]</td>";
		echo "<td><a href=index2.php?upc=$upc&updated=".urlencode($str).">Revert</a></td>";
		echo "</tr>";
	} while ($prodW = $sql->fetch_array($prodR));
}
else {
?>
<html><head>
<title>Product reversal</title>
</head>
<body>
<form method=get action=index2.php>
<b>UPC</b>: <input type=text name=upc /> 
<input type=submit value=Submit />
</form>
</body>
</html>
<?php
}

/* 
	$sql->query returns an approximation of date times to PHP
	reformat the string return by sql to correct format (YYYY-MM-DD HH:MM)
	and tack on seconds and milliseconds (YYYY-MM-DD HH:MM:SS.MMM)
	the returned string can be compared directly in a query
	e.g., where updated = '$str'
*/
function datefix($sql_str,$seconds=0,$milliseconds=0){
	$matches = array();
	preg_match("/(.*?) (\d\d) (\d\d\d\d) (\d\d):(\d\d)(.*?)/",$sql_str,$matches);

	$months = array(
		"Jan" => 1,
		"Feb" => 2,
		"Mar" => 3,
		"Apr" => 4,
		"May" => 5,
		"Jun" => 6,
		"Jul" => 7,
		"Aug" => 8,
		"Sep" => 9,
		"Oct" => 10,
		"Nov" => 11,
		"Dec" => 12
		);

	$datestring = $matches[3]."-";
	$datestring .= str_pad($months[$matches[1]],2,'0',STR_PAD_LEFT)."-";
	$datestring .= $matches[2]." ";
	$hour = (int)$matches[4];
	if ($matches[6] == "PM" and $hour != 12){
		$hour += 12 ;
	}
	else if ($matches[6] == "AM" and $hour == 12){
		$hour = 0;
	}
	$datestring .= str_pad($hour,2,'0',STR_PAD_LEFT);
	$datestring .= ":".$matches[5].":";
	$datestring .= str_pad($seconds,2,'0',STR_PAD_LEFT).".";
	$datestring .= str_pad($milliseconds,3,'0',STR_PAD_LEFT);

	return $datestring;
}

?>
