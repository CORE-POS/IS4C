<?php
use COREPOS\pos\install\conf\Conf;
use COREPOS\pos\install\conf\FormFactory;
use COREPOS\pos\install\InstallUtilities;
use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\plugins\Plugin;
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
CoreState::loadParams();
$form = new FormFactory(InstallUtilities::dbOrFail(CoreLocal::get('pDatabase')));
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
<script type="text/javascript" src="../js/<?php echo MiscLib::jqueryFile(); ?>"></script>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2><?php echo _('IT CORE Lane Installation: Plugins'); ?></h2>

<div class="alert"><?php Conf::checkWritable('../ini.json', False, 'JSON'); ?></div>
<div class="alert"><?php Conf::checkWritable('../ini.php', False, 'PHP'); ?></div>

<table id="install" border=0 cellspacing=0 cellpadding=4>

<form action=plugins.php method=post>
<b><?php echo _('Available plugins'); ?></b>:<br />
<?php
if (isset($_REQUEST['PLUGINLIST']) || isset($_REQUEST['psubmit'])){
    $oldset = CoreLocal::get('PluginList');
    if (!is_array($oldset)) $oldset = array();
    $newset = isset($_REQUEST['PLUGINLIST']) ? $_REQUEST['PLUGINLIST'] : array();
    foreach($newset as $plugin_class){
        if (!Plugin::isEnabled($plugin_class) && class_exists($plugin_class)){
            $obj = new $plugin_class();
            $obj->pluginEnable();
        }
    }
    foreach($oldset as $plugin_class){
        if (!in_array($plugin_class,$newset) && class_exists($plugin_class)){
            $obj = new $plugin_class();
            $obj->pluginDisable();
        }
    }
    CoreLocal::set('PluginList',$_REQUEST['PLUGINLIST'], true);
}
$type_check = CoreLocal::get('PluginList');
if (!is_array($type_check)) CoreLocal::set('PluginList',array(), true);

$mods = AutoLoader::listModules('COREPOS\\pos\\plugins\\Plugin');
sort($mods);

foreach($mods as $m){
    $enabled = False;
    $instance = new $m();
    foreach(CoreLocal::get("PluginList") as $r){
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
    printf('<span class="noteTxt" style="width:200px;">%s</span>',$instance->plugin_description);
    echo '</td></tr>'."\n";

    if ($enabled && empty($instance->plugin_settings)) {
        echo '<tr><td colspan="2"><i>No settings required</i></td></tr>';    
    } else if ($enabled){
        foreach ($instance->plugin_settings as $field => $info) {
            echo '<tr><td colspan="2" style="margin-bottom: 0px; height:auto;">';
            $default = isset($info['default']) ? $info['default'] : '';
            echo '<b>'.(isset($info['label'])?$info['label']:$field).'</b>: ';
            if (isset($info['options']) && is_array($info['options'])) {
                // plugin select fields are defined backwards. swap keys for values.
                $invert = array();
                foreach ($info['options'] as $label => $value) {
                    $invert[$value] = $label;
                }
                $attributes = array();
                if (is_array($default)) {
                    $attributes['multiple'] = 'multiple';
                    $attributes['size'] = 5;
                }
                echo $form->selectField($field, $invert, $default, Conf::EITHER_SETTING, true, $attributes); 
            } else {
                echo $form->textField($field, $default);
            }
            if (isset($info['description'])) 
                echo '<span class="noteTxt" style="width:200px;">'.$info['description'].'</span>';
            InstallUtilities::paramSave($field,CoreLocal::get($field));
        echo '</td></tr>';
        }
        $instance->settingChange();
    }

}
echo '</table>';

InstallUtilities::paramSave('PluginList',CoreLocal::get('PluginList'));
?>
<hr />
<input type=submit name=psubmit value="<?php echo _('Save Changes'); ?>" />
</form>
</div> <!--    wrapper -->
</body>
</html>
