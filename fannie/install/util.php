<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

// set a variable in the config file
function confset($key, $value)
{
    $FILEPATH = realpath(dirname(__FILE__).'/../');
    $lines = array();
    $found = false;
    $fp = fopen($FILEPATH.'/config.php','r');
    while($line = fgets($fp)) {
        if (strpos($line,"\$$key ") === 0) {
            $lines[] = "\$$key = $value;\n";
            $found = true;
        } else if (strpos($line,"?>") === 0 && $found == false) {
            $lines[] = "\$$key = $value;\n";
            $lines[] = "?>\n";
            $found = true;
            break; // stop at closing tag
        } else {
            $lines[] = $line;
        }
    }
    fclose($fp);

    // implies no closing tag was found so new settings
    // still needs to be added
    if ($found == false) {
        $lines[] = "\$$key = $value;\n";
    }

    // verify first line is a proper opening php tag
    // if it contains an opening tag with differnet format,
    // just replace that line. Otherwise add one and scan
    // through to make sure there isn't another one later
    // in the file
    if (isset($lines[0]) && $lines[0] != "<?php\n") {
        if (strstr($lines[0], "<?php")) {
            $lines[0] = "<?php\n";
        } else {
            $tmp = array("<?php\n");
            for ($i=0; $i<count($lines); $i++) {
                if (!strstr($lines[$i], "<?php")) {
                    $tmp[] = $lines[$i];
                }
            }
            $lines = $tmp;
        }
    }

    $fp = fopen($FILEPATH.'/config.php','w');
    foreach($lines as $line) {
        fwrite($fp,$line);
    }
    fclose($fp);
}

function check_db_host($host,$dbms)
{
	if (!function_exists("socket_create")) {
		return true; // test not possible
    }

	$port = 0;
	switch (strtoupper($dbms)) {
        case 'MYSQL':
        case 'MYSQLI':
        case 'PDO_MYSQL':
            $port = 3306;
            break;
        case 'MSSQL':
            $port = 1433;
            break;	
        case 'PGSQL':
            $port = 5432;
            break;
	}

	if (strstr($host,":")) {
		list($host,$port) = explode(":",$host,2);
    }

	$test = false;
	$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	socket_set_option($sock, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0)); 
	socket_set_block($sock);
	try {
		$test = @socket_connect($sock,$host,$port);
	} catch(Exception $ex) {}
	socket_close($sock);

	return ($test ? true : false);	
}

function db_test_connect($host,$type,$db,$user,$pw){
    if (!check_db_host($host,$type))
        return False;

    if (!class_exists('SQLManager'))
        include(dirname(__FILE__) . '/../src/SQLManager.php');
    $sql = False;
    try {
        $sql = new SQLManager($host,$type,$db,$user,$pw);
    }
    catch(Exception $ex) {}
    
    if ($sql === False || $sql->connections[$db] === False)
        return False;
    else
        return $sql;
}

function showInstallTabs($current,$path='') {
    $ret = "";

    $ret .= "<ul class='installTabList'>";

    $installTabs = array(
        'Necessities'=>'InstallIndexPage.php',
        'Authentication' => 'InstallAuthenticationPage.php',
        'Members' => 'InstallMembershipPage.php',
        'Products' => 'InstallProductsPage.php',
        'Stores' => 'InstallStoresPage.php',
        'Updates' => 'InstallUpdatesPage.php',
        'Plugins' => 'InstallPluginsPage.php',
        'Menu' => 'InstallMenuPage.php',
        'Theming' => 'InstallThemePage.php',
        'Lane Config' => 'LaneConfigPages/index.php',
        'Sample Data' => 'sample_data/InstallSampleDataPage.php'
        );

    foreach($installTabs as $key => $loc) {
        if ( $key == $current ) {
            $ret .= "<li class='installTab'>$key</li>";
        } else {
            $ret .= "<li class='installTab'><a href='$path$loc'>$key</a></li>";
        }
    }

    $ret .= "</ul>";
    $ret .= "<br style='clear:both;' />";

    return $ret;

// showInstallTabs()
}

function showInstallTabsLane($current,$path='') {
    $ret = "";

    $ret .= "<ul class='installTabList2'>";

    $installTabs = array(
        'Lane Necessities'=>'LaneNecessitiesPage.php',
        'Additional Configuration' => 'LaneAdditionalConfigPage.php',
        'Scanning Options' => 'LaneScanningPage.php',
        'Security' => 'LaneSecurityPage.php',
        'Text Strings' => 'LaneTextStringPage.php'
        );

    /* Original
    $installTabs = array(
        'Lane Necessities'=>'index.php',
        'Additional Configuration' => 'extra_config.php',
        'Scanning Options' => 'scanning.php',
        'Security' => 'security.php',
        'Text Strings' => 'text.php'
        );
    */

    foreach($installTabs as $key => $loc) {
        if ( $key == $current ) {
            $ret .= "<li class='installTab2'>$key</li>";
        } else {
            $ret .= "<li class='installTab2'><a href='$path$loc'>$key</a></li>";
        }
    }

    $ret .= "</ul>";
    $ret .= "<br style='clear:both;' />";

    return $ret;

// showInstallTabsLane()
}

/*
 * Link "up" to home of next higher level of pages.
 * See also: showLinkUp(), which takes arguments.
*/
function showLinkToFannie() {
    $key =  'Up to Fannie Config';
    $loc = 'index.php';
    $path = '../';
    $ret = "<ul class='installTabList'>";
            $ret .= "<li class='installTab'><a href='$path$loc'>$key</a></li>";
    $ret .= "</ul>";
    $ret .= "<br style='clear:both;' />";
    return $ret;
}

/* Link "up" to higher level of install pages.
 * Possibly up the file tree.
*/
function showLinkUp($label='None',$loc='',$path='') {
    if ( substr($path,-2,2) == '..' )
        $path = "{$path}/";
    $ret = "<ul class='installTabList'>";
            $ret .= "<li class='installTab'><a href='$path$loc'>$label</a></li>";
    $ret .= "</ul>";
    $ret .= "<br style='clear:both;' />";
    return $ret;
}

/**
  Get username for PHP process
  @return string username
*/
function whoami(){
    if (function_exists('posix_getpwuid')){
        $chk = posix_getpwuid(posix_getuid());
        return $chk['name'];
    }
    else
        return get_current_user();
}

/**
  Check if file exists and is writable by PHP
  @param $filename the file
  @param $optional boolean file is not required 
  @param $template string template for creating file

  Known $template values: PHP

  Prints output
*/
function check_writeable($filename, $optional=False, $template=False){
    $basename = basename($filename);
    $failure = ($optional) ? 'blue' : 'red';
    $status = ($optional) ? 'Optional' : 'Warning';

    if (!file_exists($filename) && !$optional && is_writable($filename)){
        $fp = fopen($filename,'w');
        if ($template !== False){
            switch($template){
            case 'PHP':
                fwrite($fp,"<?php\n");
                fwrite($fp,"\n");
                break;
            }
        }
        fclose($fp);
    }

    if (!file_exists($filename)){
        echo "<span style=\"color:$failure;\"><b>$status</b>: $basename does not exist</span><br />";
        if (!$optional){
            echo "<b>Advice</b>: <div style=\"font-face:mono;background:#ccc;padding:8px;\">
                touch \"".realpath(dirname($filename))."/".basename($filename)."\"<br />
                chown ".whoami()." \"".realpath(dirname($filename))."/".basename($filename)."\"</div>";
        }
    }
    elseif (is_writable($filename))
        echo "<span style=\"color:green;\">$basename is writeable</span><br />";
    else {
        echo "<span style=\"color:red;\"><b>Warning</b>: $basename is not writeable</span><br />";
        echo "<b>Advice</b>: <div style=\"font-face:mono;background:#ccc;padding:8px;\">
            chown ".whoami()." \"".realpath(dirname($filename))."/".basename($filename)."\"<br />
            chmod 600 \"".realpath(dirname($filename))."/".basename($filename)."\"</div>";
    }
}

/**
  Render configuration variable as an <input> tag
  Process any form submissions
  Write configuration variable to config.php

  @param $name [string] name of the variable
  @param $current_value [mixed] the actual config variable
  @param $default_value [mixed, default empty string] default value for the setting
  @param $quoted [boolean, default true] write value to config.php with single quotes
  @param $attributes [array, default empty] array of <input> tag attribute names and values

  @return [string] html input field
*/
function installTextField($name, &$current_value, $default_value='', $quoted=true, $attributes=array())
{
    if (FormLib::get($name, false) !== false) {
        $current_value = FormLib::get($name);
    } else if ($current_value === null) {
        $current_value = $default_value;
    }

    // sanitize values:
    if (!$quoted) {
        // unquoted must be a number or boolean
        if (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value) !== false) {
            $current_value = (int)$current_value;
        }
    } else if ($quoted) {
        // quoted must not contain single quotes
        $current_value = str_replace("'", '', $current_value);
        // must not start with backslash
        while (strlen($current_value) > 0 && substr($current_value, 0, 1) == "\\") {
            $current_value = substr($current_value, 1);
        }
        // must not end with backslash
        while (strlen($current_value) > 0 && substr($current_value, -1) == "\\") {
            $current_value = substr($current_value, 0, strlen($current_value)-1);
        }
    }

    confset($name, ($quoted ? "'" . $current_value . "'" : $current_value));

    $quote_char = strstr($current_value, '"') ? '\'' : '"';
    $ret = sprintf('<input name="%s" value=%s%s%s',
        $name, $quote_char, $current_value, $quote_char);
    if (!isset($attributes['type'])) {
        $attributes['type'] = 'text';
    }
    foreach ($attributes as $name => $value) {
        if ($name == 'name' || $name == 'value') {
            continue;
        }
        $ret .= ' ' . $name . '="' . $value . '"';
    }
    $ret .= " />\n";

    return $ret;
}

/**
  Render configuration variable as an <select> tag
  Process any form submissions
  Write configuration variable to config.php
  
  @param $name [string] name of the variable
  @param $current_value [mixed] the actual config variable
  @param $options [array] list of options
    This can be a keyed array in which case the keys
    are what is written to config.php and the values
    are what is shown in the user interface, or it
    can simply be an array of valid values.
  @param $default_value [mixed, default empty string] default value for the setting
  @param $quoted [boolean, default true] write value to config.php with single quotes

  @return [string] html select field
*/
function installSelectField($name, &$current_value, $options, $default_value='', $quoted=true)
{
    if (FormLib::get($name, false) !== false) {
        $current_value = FormLib::get($name);
    }

    // sanitize values:
    if (!$quoted) {
        // unquoted must be a number or boolean
        if (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value) !== 'false') {
            $current_value = (int)$current_value;
        }
    } else if ($quoted) {
        // quoted must not contain single quotes
        $current_value = str_replace("'", '', $current_value);
        // must not start with backslash
        while (strlen($current_value) > 0 && substr($current_value, 0, 1) == "\\") {
            $current_value = substr($current_value, 1);
        }
        // must not end with backslash
        while (strlen($current_value) > 0 && substr($current_value, -1) == "\\") {
            $current_value = substr($current_value, 0, strlen($current_value)-1);
        }
    }

    confset($name, ($quoted ? "'" . $current_value . "'" : $current_value));

    $ret = '<select name="' . $name . '">' . "\n";
    // array has non-numeric keys
    // if the array has meaningful keys, use the key value
    // combination to build <option>s with labels
    $has_keys = ($options === array_values($options)) ? false : true;
    foreach ($options as $key => $value) {
        $selected = '';
        if ($has_keys && $current_value == $key) {
            $selected = 'selected';
        } elseif (!$has_keys && $current_value == $value) {
            $selected = 'selected';
        }
        $optval = $has_keys ? $key : $value;

        $ret .= sprintf('<option value="%s" %s>%s</option>',
            $optval, $selected, $value);
        $ret .= "\n";
    }
    $ret .= '</select>' . "\n";

    return $ret;
}

?>
