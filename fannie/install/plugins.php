<?php
include('../config.php');
include('util.php');
include('db.php');
include('../classlib2.0/FanniePlugin.php');
$FILEPATH = $FANNIE_ROOT;
?>
<html>
<head>
<title>Fannie: Plugins</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
<link rel="stylesheet" href="../src/css/toggle-switch.css" type="text/css" />
<script type="text/javascript" src="../src/jquery/jquery.js"></script>
</head>
<body>
<a href="index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="auth.php">Authentication</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="mem.php">Members</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="stores.php">Stores</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="update.php">Updates</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Plugins
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="sample_data/extra_data.php">Sample Data</a>

<div id="wrapper">
<h2>Fannie: Plugins</h2>
<?php
if (is_writable('../config.php')){
	echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
	echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
}
?>
<hr />

<table id="install" border=0 cellspacing=0 cellpadding=4>

<form action=plugins.php method=post>
<b>Available plugins</b>:<br />
<?php
if (!isset($FANNIE_PLUGIN_LIST)) $FANNIE_PLUGIN_LIST = array();
if (!is_array($FANNIE_PLUGIN_LIST)) $FANNIE_PLUGIN_LIST = array();
if (!isset($FANNIE_PLUGIN_SETTINGS)) $FANNIE_PLUGIN_SETTINGS = array();
if (!is_array($FANNIE_PLUGIN_SETTINGS)) $FANNIE_PLUGIN_SETTINGS = array();

//$mods = AutoLoader::ListModules('Plugin');
//sort($mods);
/** no autoloading functionality in Fannie yet
    hardcoded lists will go away eventually */
$mods = array('TimesheetPlugin','CalendarPlugin');
include('../modules/plugins2.0/timesheet/TimesheetPlugin.php');
include('../modules/plugins2.0/calendar/CalendarPlugin.php');

if (isset($_REQUEST['PLUGINLIST']) || isset($_REQUEST['psubmit'])){
	$oldset = $FANNIE_PLUGIN_LIST;
	if (!is_array($oldset)) $oldset = array();
	$newset = isset($_REQUEST['PLUGINLIST']) ? $_REQUEST['PLUGINLIST'] : array();
	foreach($newset as $plugin_class){
		if (!FanniePlugin::IsEnabled($plugin_class)){
			$obj = new $plugin_class();
			$obj->plugin_enable();
		}
	}
	foreach($oldset as $plugin_class){
		if (!in_array($plugin_class,$newset)){
			$obj = new $plugin_class();
			$obj->plugin_disable();
		}
	}
	$FANNIE_PLUGIN_LIST = $_REQUEST['PLUGINLIST'];
}

foreach($mods as $m){
	$enabled = False;
	$instance = new $m();
	foreach($FANNIE_PLUGIN_LIST as $r){
		if ($r == $m){
			$enabled = True;
			break;
		}
	}
	echo '<tr><td colspan="2" style="height:1px;"><hr /></td></tr>';
	echo '<tr><td style="width:10em;"></td>
		<td style="width:25em;">'."\n";
	echo '<fieldset class="toggle">'."\n";
	printf('<input name="PLUGINLIST[]" id="plugin_%s" type="checkbox" %s
		value="%s" /><label onclick="" for="plugin_%s">%s</label>',
		$m, ($enabled?'checked':''),$m, $m, $m);
	echo "\n".'<span class="toggle-button"></span></fieldset>'."\n";
	printf('<span class="noteTxt">%s</span>',$instance->plugin_description);
	echo '</td></tr>'."\n";

	if ($enabled && empty($instance->plugin_settings)){
		echo '<tr><td colspan="2"><i>No settings required</i></td></tr>';	
	}
	else if ($enabled){
		if (isset($_REQUEST['psubmit']))
			$instance->setting_change();
		echo '<tr><td colspan="2" style="margin-bottom: 0px; height:auto;">';
		foreach($instance->plugin_settings as $field => $info){
			$form_id = $m.'_'.$field;
			if (isset($_REQUEST[$form_id])) 
				$FANNIE_PLUGIN_SETTINGS[$field] = $_REQUEST[$form_id];
			if (!isset($FANNIE_PLUGIN_SETTINGS[$field]))
				$FANNIE_PLUGIN_SETTINGS[$field] = isset($info['default'])?$info['default']:'';
			echo '<b>'.(isset($info['label'])?$info['label']:$field).'</b>: ';
			printf('<input type="text" name="%s" value="%s" />',
				$form_id,$FANNIE_PLUGIN_SETTINGS[$field]);
			if (isset($info['description'])) 
				echo '<span class="noteTxt">'.$info['description'].'</span>';
			//confset($field,"'".$CORE_LOCAL->get($field)."'");
		}
		echo '</td></tr>';
	}

}
echo '</table>';

$saveStr = "array(";
foreach($FANNIE_PLUGIN_LIST as $r){
	$saveStr .= "'".$r."',";
}
$saveStr = rtrim($saveStr,",").")";
confset('FANNIE_PLUGIN_LIST',$saveStr);

$saveStr = "array(";
foreach($FANNIE_PLUGIN_SETTINGS as $key => $val){
	$saveStr .= "'".$key."'=>'".$val."',";
}
$saveStr = rtrim($saveStr,",").")";
confset('FANNIE_PLUGIN_SETTINGS',$saveStr);

?>
<hr />
<input type=submit name=psubmit value="Save Changes" />
</form>
</div> <!--	wrapper -->
</body>
</html>
