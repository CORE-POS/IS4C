<?php

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
$sql->query("TRUNCATE TABLE likeCodes",$FANNIE_OP_DB);
$sql->query("TRUNCATE TABLE upcLike",$FANNIE_OP_DB);

$sql->add_connection('129.103.2.10','MSSQL','WedgePOS','sa',$FANNIE_SERVER_PW);
$sql->transfer("WedgePOS", "SELECT * FROM likeCodes",
	$FANNIE_OP_DB, "INSERT INTO likeCodes"); 
$sql->transfer("WedgePOS", "SELECT * FROM upcLike",
	$FANNIE_OP_DB, "INSERT INTO upcLike"); 
echo "<li>Like Codes synched</li>";

?>
