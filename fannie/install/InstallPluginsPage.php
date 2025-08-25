<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
//ini_set('display_errors','1');
include(dirname(__FILE__) . '/../config.php'); 
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}
if (!function_exists('confset')) {
    include(dirname(__FILE__) . '/util.php');
}
if (!function_exists('dropDeprecatedStructure')) {
    include(dirname(__FILE__) . '/db.php');
}

/**
    @class InstallPluginsPage
    Class for the Plugins install and config options
*/
class InstallPluginsPage extends \COREPOS\Fannie\API\InstallPage {

    protected $title = 'Fannie: Plugin Install';
    protected $header = 'Fannie: Plugin Install Options';

    public $description = "
    Class for the Plugins install and config options page.
    ";

    // This replaces the __construct() in the parent.
    public function __construct() {
        // To set authentication.
        parent::__construct();

        $SRC = '../src';
        // Link to a file of CSS by using a function.
        $this->addCssFile("$SRC/css/toggle-switch.css");
    // __construct()
    }

    public function preprocess()
    {
        //Should this really be done with global?
        global $FANNIE_PLUGIN_LIST, $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;

        if (!isset($FANNIE_PLUGIN_LIST)) $FANNIE_PLUGIN_LIST = array();
        if (!is_array($FANNIE_PLUGIN_LIST)) $FANNIE_PLUGIN_LIST = array();
        if (!isset($FANNIE_PLUGIN_SETTINGS)) $FANNIE_PLUGIN_SETTINGS = array();
        if (!is_array($FANNIE_PLUGIN_SETTINGS)) $FANNIE_PLUGIN_SETTINGS = array();

        $mods = FannieAPI::listModules('\COREPOS\Fannie\API\FanniePlugin');
        $sortName = function($name) {
            if (strstr($name, '\\')) {
                $parts = explode('\\', $name);
                $name = $parts[count($parts)-1];
            }
            return $name;
        };
        $modSort = function($a, $b) use ($sortName) {
            $a = $sortName($a);
            $b = $sortName($b);
            if ($a == $b) {
                return 0;
            } else {
                return $a < $b ? -1 : 1;
            }
        };
        usort($mods, $modSort);

        // did user submit form?
        $posted = $_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['psubmit'];

        // update enabled plugin list if form was submitted
        if ($posted && isset($_POST['PLUGINLIST'])){
            $oldset = $FANNIE_PLUGIN_LIST;
            $newset = $_POST['PLUGINLIST'];
            foreach($newset as $plugin_class){
                if (!\COREPOS\Fannie\API\FanniePlugin::IsEnabled($plugin_class)){
                    $obj = new $plugin_class();
                    $obj->pluginEnable();
                }
            }
            foreach($oldset as $plugin_class){
                if (!class_exists($plugin_class)) continue;
                if (!in_array($plugin_class,$newset)){
                    $obj = new $plugin_class();
                    $obj->pluginDisable();
                }
            }
            $FANNIE_PLUGIN_LIST = $newset;
        }

        // initialize settings to be saved; we start by cloning the existing
        // plugin settings array from config file.  this ensures we will not
        // inadvertently "lose" any "unknown" settings when saving.  although
        // later on below we may prune this of disabled plugin settings etc.
        $saveSettings = array('file' => array(), 'db' => array());
        foreach ($FANNIE_PLUGIN_SETTINGS as $key => $value) {
            $saveSettings['file'][$key] = $value;
        }

        // create an instance and check enabled status for each plugin; also
        // load all "current" and "to be saved" settings for each
        $plugins = array();
        $currentSettings = array();
        $changeEvents = array();
        foreach ($mods as $m) {
            $instance = new $m();
            $enabled = array_search($m, $FANNIE_PLUGIN_LIST) !== false;
            $plugins[$m] = array('instance' => $instance,
                                 'enabled' => $enabled);

            if (!empty($instance->plugin_settings)) {

                // all setting values for this plugin
                $pluginSettings = array();

                // read values from form if one was submitted
                if ($posted && $enabled) {
                    foreach ($instance->plugin_settings as $name => $info) {
                        $form_id = $m.'_'.$name;
                        if (isset($_POST[$form_id])) {
                            $pluginSettings[$name] = $_POST[$form_id];
                        }
                    }
                    // assume settings were changed; will trigger event later below
                    $changeEvents[] = $instance;
                }

                // load current settings and merge into running set
                foreach ($instance->getSettings() as $name => $value) {
                    if (!isset($pluginSettings[$name])) {
                        $pluginSettings[$name] = $value;
                    }
                }

                // add in any default values
                foreach ($instance->plugin_settings as $name => $info) {
                    if (!isset($pluginSettings[$name])) {
                        $pluginSettings[$name] = isset($info['default']) ? $info['default'] : '';
                    }
                }

                // finalize "current" plugin settings for display
                foreach ($pluginSettings as $name => $value) {
                    $form_id = $m.'_'.$name;
                    $currentSettings[$form_id] = $value;
                }

                // update "to be saved" settings if form was submitted, but we
                // will only save settings for plugins which are enabled
                if ($posted) {
                    if ($enabled) {
                        foreach ($pluginSettings as $name => $value) {
                            $nsKey = $name;
                            if (strlen($instance->settingsNamespace) > 0) {
                                $nsKey = $instance->settingsNamespace.'.'.$name;
                            }

                            if ($instance->version == 1) {
                                $saveSettings['file'][$nsKey] = $value;

                                // make sure un-qualified settings are removed,
                                // for any v1 plugin which now has a namepsace
                                if ($nsKey != $name) {
                                    if (isset($saveSettings['file'][$name])) {
                                        unset($saveSettings['file'][$name]);
                                    }
                                }

                            } elseif ($instance->version == 2) {
                                $saveSettings['db'][$nsKey] = $value;

                                // make sure version 2 plugin settings are
                                // saved only to db, never to file
                                if (isset($saveSettings['file'][$nsKey])) {
                                    unset($saveSettings['file'][$nsKey]);
                                }
                            }
                        }
                    } else { // plugin is disabled
                        if ($instance->version == 1) {

                            // remove its settings from "to be saved" collection
                            foreach ($instance->plugin_settings as $name => $info) {
                                $nsKey = $name;
                                if (strlen($instance->settingsNamespace) > 0) {
                                    $nsKey = $instance->settingsNamespace.'.'.$name;
                                }
                                if (isset($saveSettings['file'][$nsKey])) {
                                    unset($saveSettings['file'][$nsKey]);
                                }
                            }
                        }
                    }
                }
            }
        }

        // now that all the data is straight, maybe save the settings
        if ($posted) {

            $saveStr = "array(";
            foreach($FANNIE_PLUGIN_LIST as $r){
                $saveStr .= "'".$r."',";
            }
            $saveStr = rtrim($saveStr,",").")";
            confset('FANNIE_PLUGIN_LIST',$saveStr);

            $saveStr = "array(";
            foreach($saveSettings['file'] as $key => $val){
                $saveStr .= "'".$key."'=>'".$val."',";
            }
            $saveStr = rtrim($saveStr,",").")";
            confset('FANNIE_PLUGIN_SETTINGS',$saveStr);

            $dbc = FannieDB::get($FANNIE_OP_DB);
            $dbc->startTransaction();
            $prep = $dbc->prepare("INSERT INTO PluginSettings (name, setting) VALUES (?, ?)");
            $dbc->query('TRUNCATE TABLE PluginSettings');
            foreach ($saveSettings['db'] as $key => $val) {
                $dbc->execute($prep, array($key, $val));
            }
            $dbc->commitTransaction();

            foreach ($changeEvents as $instance) {
                $instance->settingChange();
            }
        }

        // stash these for use within body_content()
        $this->mods = $mods;
        $this->plugins = $plugins;
        $this->currentSettings = $currentSettings;

        return parent::preprocess();
    }

    // If chunks of CSS are going to be added the function has to be
    //  redefined to return them.
    // If this is to override x.css draw_page() needs to load it after the addCssFile
    /**
      Define any CSS needed
      @return a CSS string
    */
    function css_content(){
        // These reduce the size of the slider to keep it from overlapping the text.
        $css =".toggle label:after { width:70px; }
        .toggle span { width:30px; }";
        return $css;
    //css_content()
    }

    function body_content(){
        ob_start();

    echo showInstallTabs('Plugins');
    ?>

<form action=InstallPluginsPage.php method=post>
<?php
echo $this->writeCheck(dirname(__FILE__) . '/../config.php');
?>

<h4 class="install">Available plugins</h4>
<?php

echo '<table id="install" class="table">';
$count = 0;
foreach($this->mods as $m){
    $instance = $this->plugins[$m]['instance'];
    $enabled = $this->plugins[$m]['enabled'];
    /* 17Jun13 Under Fannie Admin CSS the spacing is cramped.
               The slider overlaps the text. Want it higher and to the right.
               Not obvious why or how to fix.
               Jiggered the CSS a little here and above but isn't really a fix.
    */
    echo '<tr ' . ($count % 2 == 0 ? 'class="info"' : '') . '>
        <td style="width:10em;">&nbsp;</td>
        <td style="width:25em;">'."\n";
    echo '<fieldset class="toggle">'."\n";
    printf('<input name="PLUGINLIST[]" id="plugin_%s" type="checkbox" %s
        value="%s" onchange="$(\'#settings_%s\').toggle();" class="checkbox-inline" />
        <label onclick="" for="plugin_%s">%s</label>',
        $m, ($enabled?'checked':''), $m, $m, $m, $m);
    echo "\n".'<span class="toggle-button"></span></fieldset>'."\n";
    // 17Jun13 EL Added <br /> for overlap problem.
    printf('<br /><span class="noteTxt">%s</span>',$instance->plugin_description);
    echo '</td></tr>'."\n";

    if (empty($instance->plugin_settings)){
        echo '<tr ' . ($count % 2 == 0 ? 'class="info"' : '') . '>
            <td colspan="2"><i>No settings required</i></td></tr>';   
    } else {
        echo '<tr ' . ($count % 2 == 0 ? 'class="info"' : '') . '>
            <td colspan="2" style="margin-bottom: 0px; height:auto;">';
        printf('<div id="settings_%s" %s>',
            $m, (!$enabled ? 'class="collapse"' : '')
        );
        foreach($instance->plugin_settings as $field => $info){
            $form_id = $m.'_'.$field;
            $currentValue = $this->currentSettings[$form_id];
            echo '<b>'.(isset($info['label'])?$info['label']:$field).'</b>: ';
            if (isset($info['options'])) {
                echo '<select name="' . $form_id . '" class="form-control">';
                foreach ($info['options'] as $key => $val) {
                    printf('<option %s value="%s">%s</option>',
                        ($currentValue == $val) ? 'selected' : '',
                        $val, $key);
                }
                echo '</select>';
            } else {
                printf('<input type="text" name="%s" value="%s" class="form-control" />',
                    $form_id,$currentValue);
            }
            // 17Jun13 EL Added <br /> for crampedness problem.
            if (isset($info['description'])) 
                echo '<br /><span class="noteTxt">'.$info['description'].'</span>';
            echo '<br />';
            //confset($field,"'".$CORE_LOCAL->get($field)."'");
        }
        echo '</div>';
        echo '</td></tr>';
    }
    $count++;
}
echo '</table>';

?>
<hr />
        <p>
            <button type="submit" name="psubmit" value="1" class="btn btn-default">Save Configuration</button>
        </p>
</form>

<?php

        return ob_get_clean();

    // body_content
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }

// InstallPluginsPage
}

FannieDispatch::conditionalExec();

