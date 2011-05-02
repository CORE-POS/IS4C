<?php
include('../../config.php');
include('../../src/SQLManager.php');

$dbc = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$dh = opendir('new');
while( ($file = readdir($dh)) !== False){
	$exts = explode(".",$file);
	
	$e = strtolower(array_pop($exts));
	if ($e != "png" && $e != "gif" && $e != "jpg" && $e != "jpeg")
		continue;

	$u = array_pop($exts);
	if (!is_numeric($u)) continue;

	$upc = str_pad($u,13,'0',STR_PAD_LEFT);

	$q1 = "SELECT upc FROM productUser where upc='$upc'";
	$q2 = "SELECT upc FROM products WHERE upc='$upc'";
	
	$r1 = $dbc->query($q1);
	if ($dbc->num_rows($r1) > 0){
		echo "UPC $upc found in productUser\n";
		$upQ = sprintf("UPDATE productUser SET photo='%s'
			WHERE upc='%s'",$file,$upc);
		$upR = $dbc->query($upQ);
		rename('new/'.$file,'done/'.$file);
		rename('new/'.$u.'.thumb.'.$e,
			'done/'.$u.'.thumb.'.$e);
	}
	else {
		$r2 = $dbc->query($q2);
		if ($dbc->num_rows($r2) > 0){
			echo "UPC $upc found in products\n";	
		}
		else {
			echo "UPC $upc not found\n";
		}
	}
}

?>
