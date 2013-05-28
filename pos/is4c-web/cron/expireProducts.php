<html>
<head></head>
<body>
<?php
include('../ini.php');
if (!function_exists("pDataConnect")) include($IS4C_PATH."lib/connect.php");

$query = "UPDATE products AS p INNER JOIN productExpires AS e
	ON p.upc=e.upc 
	SET p.inUse=0
	WHERE datediff(now(),e.expires) >= 0";
$db = pDataConnect();
$r = $db->query($query);

?>
</body></html>
