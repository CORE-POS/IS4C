<html>
<head></head>
<body>
<?php
include('../ini.php');
if (!function_exists("pDataConnect")) include($IS4C_PATH."lib/connect.php");

$db = tDataConnect();

$db->query("LOCK TABLES pendingtrans WRITE, dtransactions WRITE, 
	localtrans_today WRITE, localtrans WRITE");

// get upcs & quantities from pending
$data = array();
$result = $db->query("SELECT upc,sum(quantity) as qty FROM pendingtrans
		WHERE trans_type='I'");
while($row = $db->fetch_row($result)){
	$data[$row['upc']] = $row['qty'];
}

// shuffle contents to final trans tables
$db->query("INSERT INTO dtransactions SELECT * FROM pendingtrans");
$db->query("INSERT INTO localtrans_today SELECT * FROM pendingtrans");
$db->query("INSERT INTO localtrans SELECT * FROM pendingtrans");

// clear pending
$db->query("DELETE FROM pendingtrans");

$db->query("UNLOCK TABLES");

// update limits based on amounts sold
$db2 = pDataConnect();
foreach($data as $upc=>$qty){
	$q = sprintf("UPDATE productOrderLimits 
		SET available=available-%d
		WHERE upc='%s'",$qty,$upc);
	$r = $db2->query($q);
}

$endQ = "UPDATE products AS p INNER JOIN
	productOrderLimits AS l ON p.upc=l.upc
	SET p.inUse=0
	WHERE l.available <= 0";
$db2->query($endQ);

?>
</body>
</html>
