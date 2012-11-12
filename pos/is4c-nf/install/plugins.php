<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::LoadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
include('util.php');
?>
<html>
<head>
<title>IT CORE Lane Installation: Plugins</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
</head>
<body>
	
<a href="index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="extra_config.php">Additional Configuration</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="scanning.php">Scanning Options</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="security.php">Security</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="debug.php">Debug</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Plugins
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="extra_data.php">Sample Data</a>

<h2>IT CORE Lane Installation: Scanning Options</h2>

<?php
check_writeable('../ini.php');
check_writeable('../ini-local.php');
?>
<form action=plugins.php method=post>
<b>Available plugins</b>:<br />
<select name="PLUGINLIST[]" size="10" multiple>
<?php
if (isset($_REQUEST['PLUGINLIST']) || isset($_REQUEST['psubmit'])){
	$oldset = $CORE_LOCAL->get('PluginList');
	if (!is_array($oldset)) $oldset = array();
	$newset = isset($_REQUEST['PLUGINLIST']) ? $_REQUEST['PLUGINLIST'] : array();
	foreach($newset as $plugin_class){
		if (!Plugin::IsEnabled($plugin_class)){
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
	$CORE_LOCAL->set('PluginList',$_REQUEST['PLUGINLIST']);
}
$type_check = $CORE_LOCAL->get('PluginList');
if (!is_array($type_check)) $CORE_LOCAL->set('PluginList',array());

$mods = AutoLoader::ListModules('Plugin');

foreach($mods as $m){
	$selected = "";
	foreach($CORE_LOCAL->get("PluginList") as $r){
		if ($r == $m){
			$selected = "selected";
			break;
		}
	}
	echo "<option $selected>$m</option>";
}

$saveStr = "array(";
foreach($CORE_LOCAL->get("PluginList") as $r){
	$saveStr .= "'".$r."',";
}
$saveStr = rtrim($saveStr,",").")";
confsave('PluginList',$saveStr);
?>
</select><br />
<?php foreach($CORE_LOCAL->get('PluginList') as $plugin_class) {
$obj = new $plugin_class();
if (!empty($obj->plugin_settings)){
	echo '<hr />';
	echo '<h3>Settings for plugin: '.$plugin_class.'</h3>';
}
foreach($obj->plugin_settings as $field => $info){
	$form_id = $plugin_class.'_'.$field;
	if (isset($_REQUEST[$form_id])) $CORE_LOCAL->set($field,$_REQUEST[$form_id]);
	if ($CORE_LOCAL->get($field) === "") 
		$CORE_LOCAL->set($field,isset($info['default'])?$info['default']:'');
	echo '<b>'.(isset($info['label'])?$info['label']:$field).'</b>: ';
	printf('<input type="text" name="%s" value="%s" />',$form_id,$CORE_LOCAL->get($field));
	echo '<br />';
	if (isset($info['description'])) echo $info['description'].'<br />';
	confsave($field,"'".$CORE_LOCAL->get($field)."'");
}

} ?>
<hr />
<input type=submit name=psubmit value="Save Changes" />
</form>
</body>
</html>
