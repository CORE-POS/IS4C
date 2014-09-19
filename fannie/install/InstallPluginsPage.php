<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
//ini_set('display_errors','1');
include('../config.php'); 
include('util.php');
include('db.php');
include_once('../classlib2.0/FannieAPI.php');
$FILEPATH = $FANNIE_ROOT;

/**
    @class InstallPluginsPage
    Class for the Plugins install and config options
*/
class InstallPluginsPage extends InstallPage {

    protected $title = 'Fannie: Plugin Install';
    protected $header = 'Fannie: Plugin Install Options';

    public $description = "
    Class for the Plugins install and config options page.
    ";

    // This replaces the __construct() in the parent.
    public function __construct() {

        // To set authentication.
        FanniePage::__construct();

        $SRC = '../src';
        // Link to a file of CSS by using a function.
        $this->add_css_file("$SRC/style.css");
        $this->add_css_file("$SRC/javascript/jquery-ui.css");
        $this->add_css_file("$SRC/css/install.css");
        $this->add_css_file("$SRC/css/toggle-switch.css");

        // Link to a file of JS by using a function.
        $this->add_script("$SRC/javascript/jquery.js");
        $this->add_script("$SRC/javascript/jquery-ui.js");

    // __construct()
    }

    // If chunks of CSS are going to be added the function has to be
    //  redefined to return them.
    // If this is to override x.css draw_page() needs to load it after the add_css_file
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

    // If chunks of JS are going to be added the function has to be
    //  redefined to return them.
    /**
      Define any javascript needed
      @return a javascript string
    function javascript_content(){
        $js ="";
        return $js;
    //js_content()
    }
    */

    function body_content(){
        //Should this really be done with global?
        global $FANNIE_PLUGIN_LIST, $FANNIE_PLUGIN_SETTINGS;
        ob_start();

    echo showInstallTabs('Plugins');
    ?>

<form action=InstallPluginsPage.php method=post>
<h1 class="install"><?php echo $this->header; ?></h1>
<?php
if (is_writable('../config.php')){
    echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
    echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
}
?>

<h4 class="install">Available plugins</h4>
<?php
if (!isset($FANNIE_PLUGIN_LIST)) $FANNIE_PLUGIN_LIST = array();
if (!is_array($FANNIE_PLUGIN_LIST)) $FANNIE_PLUGIN_LIST = array();
if (!isset($FANNIE_PLUGIN_SETTINGS)) $FANNIE_PLUGIN_SETTINGS = array();
if (!is_array($FANNIE_PLUGIN_SETTINGS)) $FANNIE_PLUGIN_SETTINGS = array();

$mods = FannieAPI::ListModules('FanniePlugin');
sort($mods);

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
        if (!class_exists($plugin_class)) continue;
        if (!in_array($plugin_class,$newset)){
            $obj = new $plugin_class();
            $obj->plugin_disable();
        }
    }
    $FANNIE_PLUGIN_LIST = $_REQUEST['PLUGINLIST'];
}

echo '<table id="install" border=0 cellspacing=0 cellpadding=4>';
foreach($mods as $m){
    $enabled = False;
    $instance = new $m();
    foreach($FANNIE_PLUGIN_LIST as $r){
        if ($r == $m){
            $enabled = True;
            break;
        }
    }
    /* 17Jun13 Under Fannie Admin CSS the spacing is cramped.
               The slider overlaps the text. Want it higher and to the right.
               Not obvious why or how to fix.
               Jiggered the CSS a little here and above but isn't really a fix.
    */
    echo '<tr><td colspan="2" style="height:1px;"><hr /></td></tr>'."\n";
    echo '<tr><td style="width:10em;">&nbsp;</td>
        <td style="width:25em;">'."\n";
    echo '<fieldset class="toggle">'."\n";
    printf('<input name="PLUGINLIST[]" id="plugin_%s" type="checkbox" %s
        value="%s" onchange="$(\'#settings_%s\').toggle();" />
        <label onclick="" for="plugin_%s">%s</label>',
        $m, ($enabled?'checked':''), $m, $m, $m, $m);
    echo "\n".'<span class="toggle-button"></span></fieldset>'."\n";
    // 17Jun13 EL Added <br /> for overlap problem.
    printf('<br /><span class="noteTxt">%s</span>',$instance->plugin_description);
    echo '</td></tr>'."\n";

    if (empty($instance->plugin_settings)){
        echo '<tr><td colspan="2"><i>No settings required</i></td></tr>';   
    } else {
        echo '<tr><td colspan="2" style="margin-bottom: 0px; height:auto;">';
        printf('<div id="settings_%s" %s>',
            $m, (!$enabled ? 'style="display:none;"' : '')
        );
        foreach($instance->plugin_settings as $field => $info){
            $form_id = $m.'_'.$field;
            // ignore submitted values if plugin was not enabled
            if ($enabled && isset($_REQUEST[$form_id])) 
                $FANNIE_PLUGIN_SETTINGS[$field] = $_REQUEST[$form_id];
            if (!isset($FANNIE_PLUGIN_SETTINGS[$field]))
                $FANNIE_PLUGIN_SETTINGS[$field] = isset($info['default'])?$info['default']:'';
            echo '<b>'.(isset($info['label'])?$info['label']:$field).'</b>: ';
            if (isset($info['options'])) {
                echo '<select name="' . $form_id . '">';
                foreach ($info['options'] as $key => $val) {
                    printf('<option %s value="%s">%s</option>',
                        ($FANNIE_PLUGIN_SETTINGS[$field] == $val) ? 'selected' : '',
                        $val, $key);
                }
                echo '</select>';
            } else {
                printf('<input type="text" name="%s" value="%s" />',
                    $form_id,$FANNIE_PLUGIN_SETTINGS[$field]);
            }
            // show the default if plugin isn't enabled, but
            // unset so that it isn't saved in the configuration
            if (!$enabled) {
                unset($FANNIE_PLUGIN_SETTINGS[$field]);
            }
            // 17Jun13 EL Added <br /> for crampedness problem.
            if (isset($info['description'])) 
                echo '<br /><span class="noteTxt">'.$info['description'].'</span>';
            echo '<br />';
            //confset($field,"'".$CORE_LOCAL->get($field)."'");
        }
        if (isset($_REQUEST['psubmit'])) {
            $instance->setting_change();
        }
        echo '</div>';
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

<?php

        return ob_get_clean();

    // body_content
    }

// InstallPluginsPage
}

FannieDispatch::conditionalExec(false);

?>
