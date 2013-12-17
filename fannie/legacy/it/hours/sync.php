<?php

include('../../../config.php');
header('Location: '.$FANNIE_URL.'modules/plugins2.0/WfcHoursTracking/WfcHtSyncPage.php');
exit;
/*
include('db.php');

$USER_FILE = '/etc/passwd';
$EXCLUDE_EMAILS = array(
	'root'=>True,
	'finance'=>True,
	'pop'=>True,
	'quickbooks'=>True,
	'testuser'=>True,
	'printer'=>True,
	'games'=>True,
	'csc'=>True,
	'ldap'=>True,
	'relfvin'=>True,
	'jkresha'=>True
);

$new_accounts = array();

$db = hours_dbconnect();
$fp = fopen($USER_FILE,'r');
while( ($line = fgets($fp)) !== False ){
	$fields = explode(":",$line);
	$uid = $fields[2];
	$group = $fields[3];
	if ($group != "100") continue;

	$shortname = $fields[0];
	if (isset($EXCLUDE_EMAILS[$shortname])) continue;

	$tmp = explode(" ",$fields[4]);
	$name = "";
	for($i=1;$i<count($tmp);$i++)
		$name .= $tmp[$i]." ";
	if (count($tmp) > 1)
		$name = trim($name).", ";
	if (count($tmp) == 0)
		$name = $shortname;
	else
		$name .= $tmp[0];

	$chkQ = "SELECT empID FROM employees WHERE empID=".$uid;
	$chkR = $db->query($chkQ);
	if ($db->num_rows($chkR) == 0){
		$new_accounts[$uid] = $shortname;
		$insQ = sprintf("INSERT INTO employees VALUES (%d,%s,NULL,0,8,NULL,0)",
				$uid,$db->escape($name));
		$db->query($insQ);
		echo "Added ADP entry for $name<br />";
	}
}
fclose($fp);

include('../../db.php');
foreach($new_accounts as $uid => $uname){
	$uid = str_pad($uid,4,'0',STR_PAD_LEFT);
	$chkQ = "SELECT uid FROM Users WHERE uid='$uid'";
	$chkR = $dbc->query($chkQ);
	if ($dbc->num_rows($chkR) == 0){
		$insQ = sprintf("INSERT INTO Users VALUES (%s,'','',%s,'','')",
			$dbc->escape($uname),$dbc->escape($uid));
		$dbc->query($insQ);
		echo "Added user account for $uname<br />";
	}
}

if (count($new_accounts) == 0){
	echo '<i>No new employees found</i><br />';
}

?>
<p />
<a href="menu.php">Main Menu</a>
*/
