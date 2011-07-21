<?php

header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="votenet.csv"');

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

$NL = "\r\n";
$query = "SELECT cardno,firstname,lastname FROM custdata
	WHERE type = 'PC' AND personnum=1 AND lastname not like '%NEW MEMBER%' ORDER BY convert(int,cardno)";
$result = $sql->query($query);

echo "username,password,firstname,lastname,email".$NL;
while($row = $sql->fetch_row($result)){
	$row[2] = str_replace(","," ",$row[2]);
	$uname = strtolower($row[1][0] . $row[2]);
	echo $uname.",".trim($row[0]).",".$row[1].",".$row[2].",".$NL;
}

?>
