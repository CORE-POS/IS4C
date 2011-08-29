<html>
<head></head>
<body>
<?php
include('../ini.php');
if (!function_exists("pDataConnect")) include($IS4C_PATH."lib/connect.php");

$db = tDataConnect();
$oldCartsQ = "DELETE FROM localtemptrans WHERE datediff(curdate(),datetime) > 1";
$db->query($oldCartsQ);

$clearQ = "DELETE FROM localtrans_today WHERE datediff(curdate(),datetime) <> 0";
$db->query($clearQ);

?>
</body>
</html>
