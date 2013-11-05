<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
include('InstallUtilities.php');

ini_set('display_errors','1');

$SILENT = isset($_REQUEST['quiet']) ? True : False;

$db_local = Database::pDataConnect();
$db_remote = Database::mDataConnect();

$msgs = "";
$upgrades = array();

$stamp = $db_local->query("SELECT modified FROM lane_config");
if (!$stamp){
	$msgs .= "There's a problem with the local database; can't upgrade";
}
else {
	$dt = array_pop($db_local->fetch_row($stamp));
	$chk = $db_remote->query("SELECT keycode,value FROM lane_config
			WHERE modified >= '$dt'");
	if (!$chk){
		$msgs .= "There's a problem with the remote database";
	}
	else {
		while($row = $db_remote->fetch_row($chk)){
			$upgrades[$row["keycode"]] = unserialize($row['value']);
		}
	}
}

if (!empty($msgs)){
	if (!$SILENT){
		echo "<b>Error</b>: ".$msgs;
	}
	else {
		header("Location: ../login.php");
	}
	exit;
}

if (!empty($upgrades)){
	foreach($upgrades as $key => $val){
		if (is_array($val)){
			$str = "array(";
			foreach($val as $v)
				$str .= "'$v',";
			$str = rtrim($str,",").")";	
			InstallUtilities::confsave($key,$str);
		}
		else if (is_string($val) || is_bool($val)){
			InstallUtilities::confsave($key,"'".$val."'");
		}
		else
			InstallUtilities::confsave($key,$val);
	}
	$db_local->query("UPDATE lane_config SET modified=".$db_local->now());
	$msgs .= "Upgrades to ini.php complete!";
}
else {
	$msgs .= "Settings are up to date!";
}

if (!$SILENT){
	echo $msgs;
}
else {
	header("Location: ../login.php");
}

?>
