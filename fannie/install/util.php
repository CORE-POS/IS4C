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
function confset($key, $value){
	$FILEPATH = realpath(dirname(__FILE__).'/../');
	$lines = array();
	$found = False;
	$fp = fopen($FILEPATH.'/config.php','r');
	while($line = fgets($fp)){
		if (strpos($line,"\$$key ") === 0){
			$lines[] = "\$$key = $value;\n";
			$found = True;
		}
		elseif (strpos($line,"?>") === 0 && $found == False){
			$lines[] = "\$$key = $value;\n";
			$lines[] = "?>\n";
		}
		else
			$lines[] = $line;
	}
	fclose($fp);

	$fp = fopen($FILEPATH.'/config.php','w');
	foreach($lines as $line)
		fwrite($fp,$line);
	fclose($fp);
}

function db_test_connect($host,$type,$db,$user,$pw){
	global $FANNIE_ROOT;
	if (!function_exists("check_db_host"))
		include($FANNIE_ROOT.'src/host_up.php');
	if (!check_db_host($host,$type))
		return False;

	if (!class_exists('SQLManager'))
		include($FANNIE_ROOT.'src/SQLManager.php');
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
		'Lane Config' => 'LaneConfigPages/LaneNecessitiesPage.php',
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
	$key =	'Up to Fannie Config';
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

?>
