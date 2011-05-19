<?php

include('../config.php');

$header = "Build Archives";
$page_title = "Build Archives";
include($FANNIE_ROOT.'src/header.html');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/mk_trans_archive.php');

if (isset($_REQUEST['rebuild'])){
	$startM = sprintf("%d",$_REQUEST['m1']);
	$endM = sprintf("%d",$_REQUEST['m2']);
	$startY = sprintf("%d",$_REQUEST['y1']);
	$endY = sprintf("%d",$_REQUEST['y2']);
	$redo = isset($_REQUEST['retable']) ? True : False;

	if (strlen($startY) == 2) 
		$startY = "20".$startY;
	else if	(strlen($startY) != 4)
		$startY = "20".substr($startY,-2);

	if (strlen($endY) == 2) 
		$endY = "20".$endY;
	else if	(strlen($endY) != 4)
		$endY = "20".substr($endY,-2);

	$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
			$FANNIE_ARCHIVE_DB,$FANNIE_SERVER_USER,
			$FANNIE_SERVER_PW);
	$endTS = mktime(0,0,0,$endM,1,$endY);
	$curTS = mktime(0,0,0,$startM,1,$startY);
	while($curTS <= $endTS){
		mk_trans_archive_table($startM,$startY,$sql,$redo);
		mk_trans_archive_views($startM,$startY,$sql);	
		$startM++;
		if ($startM > 12){
			$startM=1;
			$startY++;
		}
		$curTS = mktime(0,0,0,$startM,1,$startY);
	}
}
else {
	echo '<form action="archiveutil.php" method="get">
	<table cellspacing="0" cellpadding="4">
	<tr><th>Start Month</th><td><input type="text" size="3" name="m1" /></td>
	<th>Year</th><td><input type="text" size="4" name="y1" /></td></tr>
	<tr><th>End Month</th><td><input type="text" size="3" name="m2" /></td>
	<th>Year</th><td><input type="text" size="4" name="y2" /></td></tr>
	<tr><th colspan="2">Recreate tables</th>
	<td colspan="2"><input type="checkbox" name="retable" /></td></tr>
	<tr><td colspan="4"><input type="submit" name="rebuild" value="Go" /></td>
	</table>
	</form>';

}
include($FANNIE_ROOT.'src/footer.html');

?>
