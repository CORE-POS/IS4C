<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
include('InstallUtilities.php');
?>
<html>
<head>
<title>Manage Configuration via Database</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2>IT CORE Lane Installation: Config Management</h2>

<div class="alert success"><em>
<?php if (isset($_POST['writeLocal'])){
	InstallUtilities::writeConfFromDb();
	echo 'Wrote to ini.php';
}
else if (isset($_POST['sendToServer'])){
	InstallUtilities::sendConfToServer();
	echo 'Shipped settings to server';
}
else if (isset($_POST['fetchFromServer'])){
	InstallUtilities::getConfFromServer();
	echo 'Got settings to server<br />';
	echo 'Wrote to ini.php';
}
?>
</em></div>

<?php
/**
  Check for connectivity, configurations to
  determine available options
*/

$local_db = InstallUtilities::dbTestConnect($CORE_LOCAL->get('localhost'),
		$CORE_LOCAL->get('DBMS'),
		$CORE_LOCAL->get('pDatabase'),
		$CORE_LOCAL->get('localUser'),
		$CORE_LOCAL->get('localPass'));
$local_config = False;
if ($local_db){
	$test = $local_db->query('SELECT keycode, value FROM lane_config');
	if ($test) $local_config = $local_db->num_rows($test);
}

$remote_db = InstallUtilities::dbTestConnect($CORE_LOCAL->get('mServer'),
		$CORE_LOCAL->get('mDBMS'),
		$CORE_LOCAL->get('mDatabase'),
		$CORE_LOCAL->get('mUser'),
		$CORE_LOCAL->get('mPass'));
$remote_config = False;
if ($remote_db){
	$test = $remote_db->query('SELECT keycode, value FROM lane_config');
	if ($test) $remote_config = $remote_db->num_rows($test);
}
?>

<form action=db-ini.php method=post>

<?php if ($local_db !== False && $local_config){ ?>
<p>
Write settings from the lane's configuration database to ini.php.
<br />
<input type="submit" name="writeLocal"
	value="Write <?php echo $local_config; ?> Settings to ini.php" />
</p>
<?php } else if ($local_db === False){?>
Cannot connect to local database
<?php } else if (!$local_config){ ?>
Local configuration database is empty
<?php } ?>
<hr />

<?php if ($local_db !== False && $local_config && $remote_db !== False){ ?>
<p>
Send this lane's configuration database to the server.
<br />
<input type="submit" name="sendToServer"
	value="Write <?php echo $local_config; ?> Settings to Server" />
</p>
<?php } else if ($local_db === False){?>
Cannot connect to local database
<?php } else if ($remote_db === False){?>
Cannot connect to server database
<?php } else if (!$local_config){ ?>
Local configuration database is empty
<?php } ?>
<hr />

<?php if ($local_db !== False && $remote_config && $remote_db !== False){ ?>
<p>
Get configuration settings from the server database.
<br />
<input type="submit" name="fetchFromServer"
	value="Get <?php echo $remote_config; ?> Settings from Server" />
</p>
<?php } else if ($local_db === False){?>
Cannot connect to local database
<?php } else if ($remote_db === False){?>
Cannot connect to server database
<?php } else if (!$remote_config){ ?>
Server configuration database is empty
<?php } ?>
<hr />
