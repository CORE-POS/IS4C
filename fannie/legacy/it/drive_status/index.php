<?php

if (isset($_GET['action'])){
	switch ($_GET['action']){
	case 'unmount':
		passthru('sudo /srv/www/htdocs/it/drive_status/umount.sh',$umount_o);
		break;
	case 'update':
		exec('sudo /srv/www/htdocs/it/drive_status/wrapper.sh > rsync.log 2>&1 &');	
		sleep(2);
		break;
	}
}

?>
<html>
<head><title>Backup hdd status page</title>
<style type="text/css">
.green {
	color: green;
}
.red {
	color: red;
}
a {
	color: blue;
}
</style>
</head>
<body>

<b>Mount status</b><br />
<?php

exec('sh mountcheck.sh',$mount_status);
foreach($mount_status as $l){
	if ($l == "The drive is not currently mounted")
		echo "<div class=green>$l";
	elseif ($l == "The drive is currently mounted"){
		echo "<div class=red>";
		echo $l;
		if (file_exists('op.lock'))
			echo " [ Update in progress ]";
		else 
			echo " [ <a href=index.php?action=unmount>Unmount</a> ]";
	}
	elseif  ($l == "The drive is mounted in the correct spot")
		echo "<div class=green>$l";
	else
		echo "<div class=red>$l";
	echo "</div>";
}

?>
<br />

<b>Update status</b><br />
<?php
	echo file_get_contents('date.log');
	if (file_exists('op.lock'))
		echo " [ Updating | <a href=index.php>Refresh</a> ]";
	else
		echo " [ <a href=index.php?action=update>Update now</a> ]";
	echo "<br />";
?>
<br />

<b>Rsync status</b><br />
<?php
	if (file_exists('op.lock')){
		echo "<div class=red>";
		echo "The drive is currently being updated by rsync [ <a href=index.php>Refresh</a> ]";
		echo "</div>";
	}
	else {
		echo "<div class=green>";
		echo "The drive is not in use";
		echo "</div>";
	}
?>

</body>
</html>
