<?php
include('../../config.php');
include('../../src/mysql_connect.php');

$dh = opendir('new');
while( ($file = readdir($dh)) !== False){

	$exts = explode('.',$file);
	$e = strtolower(array_pop($exts));
	if ($e != "png" && $e != "gif" && $e != "jpg" && $e != "jpeg")
		continue;

	$u = array_pop($exts);
	if (!is_numeric($u)) continue;

	$upc = str_pad($u,13,'0',STR_PAD_LEFT);

	$q1 = "SELECT upc FROM productUser where upc='$upc'";
	$q2 = "SELECT upc FROM products WHERE upc='$upc'";
	
	$r1 = $dbc->query($q1);
	if ($dbc->num_rows($r1) > 0)
		continue;

	$r2 = $dbc->query($q2);
	if ($dbc->num_rows($r2) > 0)
		continue;

	echo "<b>UPC:</b> $u<br />";
	echo "<a href=new/$file><img src=new/$u.thumb.$e /></a>";
	echo "<hr />";
}
?>
