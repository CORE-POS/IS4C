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
<link rel="stylesheet" href="../css/toggle-switch.css" type="text/css" />
<script type="text/javascript" src="../js/jquery.js"></script>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2>IT CORE Lane Installation: Plugins</h2>

<div class="alert"><?php check_writeable('../ini.php'); ?></div>
<div class="alert"><?php check_writeable('../ini-local.php'); ?></div>

<table id="install" border=0 cellspacing=0 cellpadding=4>

<form action=plugins.php method=post>
<b>Available plugins</b>:<br />
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
	$CORE_LOCAL->set('PluginList',$_REQUEST['PLUGINLIST'], True);
}
$type_check = $CORE_LOCAL->get('PluginList');
if (!is_array($type_check)) $CORE_LOCAL->set('PluginList',array(), True);

$mods = AutoLoader::ListModules('Plugin');
sort($mods);

foreach($mods as $m){
	$enabled = False;
	$instance = new $m();
	foreach($CORE_LOCAL->get("PluginList") as $r){
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
		foreach($instance->plugin_settings as $field => $info){
			echo '<tr><td colspan="2" style="margin-bottom: 0px; height:auto;">';
			$form_id = $m.'_'.$field;
			if (isset($_REQUEST[$form_id])) 
				$CORE_LOCAL->set($field,$_REQUEST[$form_id],True);
			if ($CORE_LOCAL->get($field) === "") 
				$CORE_LOCAL->set($field,isset($info['default'])?$info['default']:'',True);
			echo '<b>'.(isset($info['label'])?$info['label']:$field).'</b>: ';
			if (isset($info['options']) && is_array($info['options'])){
				printf('<select name="%s">',$form_id);
				foreach($info['options'] as $label => $value){
					printf('<option %s value="%s">%s</option>',
						($CORE_LOCAL->get($field)==$value?'selected':''),
						$value, $label);
				}
				echo '</select>';
			}
			else {
				printf('<input type="text" name="%s" value="%s" />',
					$form_id,$CORE_LOCAL->get($field));
			}
			if (isset($info['description'])) 
				echo '<span class="noteTxt">'.$info['description'].'</span>';
			confsave($field,"'".$CORE_LOCAL->get($field)."'");
		echo '</td></tr>';
		}
	}

}
echo '</table>';

$saveStr = "array(";
foreach($CORE_LOCAL->get("PluginList") as $r){
	$saveStr .= "'".$r."',";
}
$saveStr = rtrim($saveStr,",").")";
confsave('PluginList',$saveStr);
?>
<hr />
<input type=submit name=psubmit value="Save Changes" />
</form>
</div> <!--	wrapper -->
</body>
</html>
