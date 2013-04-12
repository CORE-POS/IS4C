<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
ini_set('display_errors','1');
?>
<?php 
include('../config.php'); 
include('util.php');
?>
<html>
<head><title>Fannie Menu Builder</title>
<link rel="stylesheet" href="../src/css/install.css" type="text/css" />
<script type="text/javascript" src="../src/jquery/jquery.js"></script>
</head>
<body>
<?php
echo showInstallTabs("Menu");
?>
<form action=menu.php method=post>
<h1>Fannie: Menu Builder</h1>
<?php
// path detection
$FILEPATH = rtrim($_SERVER['SCRIPT_FILENAME'],'menu.php');
$FILEPATH = rtrim($FILEPATH,'/');
$FILEPATH = rtrim($FILEPATH,'install');
$FANNIE_ROOT = $FILEPATH;

if (is_writable($FILEPATH.'config.php')){
	echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
	echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
	echo "<br />Full path is: ".$FILEPATH.'config.php'."<br />";
	if (function_exists('posix_getpwuid')){
		$chk = posix_getpwuid(posix_getuid());
		echo "PHP is running as: ".$chk['name']."<br />";
	}
	else
		echo "PHP is (probably) running as: ".get_current_user()."<br />";
}
?>
<hr  />
Use this tool to customize Fannie's left hand menu. Usage:
<ul>
<li>To add a new menu entry, type it in the appropriate 'New' box.
<li>To delete an entry, clear its 'Text' box. Bear in mind sub-entries
will also be deleted.
<li>URLs are relative to Fannie <i>unless</i> they begin with / or
a protocol (http://, https://, etc).
</ul>
<b>Configurable Menu Enabled</b>
<select name=FANNIE_DYNAMIC_MENU>
<?php
if (!isset($FANNIE_DYNAMIC_MENU)) $FANNIE_DYNAMIC_MENU = False;
if (isset($_REQUEST['FANNIE_DYNAMIC_MENU'])) $FANNIE_DYNAMIC_MENU = $_REQUEST['FANNIE_DYNAMIC_MENU'];
if ($FANNIE_DYNAMIC_MENU === True || $FANNIE_DYNAMIC_MENU == 'Yes'){
	confset('FANNIE_DYNAMIC_MENU','True');
	echo "<option selected>Yes</option><option>No</option>";
}
else{
	confset('FANNIE_DYNAMIC_MENU','False');
	echo "<option>Yes</option><option selected>No</option>";
}
echo "</select><br />";
?>
<br />
<b>Fannie Menu Builder</b>
<?php
if (!isset($FANNIE_MENU) || !is_array($FANNIE_MENU)) $FANNIE_MENU=array();
if (isset($_REQUEST['label1'])){
	$READ_BACK = array();
	$READ_BACK = fm_read($READ_BACK,'1');
	$FANNIE_MENU = $READ_BACK;
}
if (empty($FANNIE_MENU)){
	include($FANNIE_ROOT.'src/defaultmenu.php');
}

fm_draw($FANNIE_MENU);
$saveStr = fm_to_string($FANNIE_MENU);
confset('FANNIE_MENU',$saveStr);
?>
<hr />
<input type=submit value="Refresh" />
</form>
</body>
</html>
<?php
/**
  Read POST variables recursively into the proper
  array format
*/
function fm_read($arr,$parent='1'){
	if (!isset($_REQUEST['label'.$parent]) || !is_array($_REQUEST['label'.$parent]))
		return $arr;
	for($i=0;$i<count($_REQUEST['label'.$parent]);$i++){
		if (empty($_REQUEST['label'.$parent][$i]))
			continue;
		$new_entry = array();
		$new_entry['label'] = $_REQUEST['label'.$parent][$i];
		$new_entry['url'] = $_REQUEST['url'.$parent][$i];
		if (isset($_REQUEST['subheading'.$parent]) && isset($_REQUEST['subheading'.$parent][$i]))
			$new_entry['subheading'] = $_REQUEST['subheading'.$parent][$i];
		$new_entry['submenu'] = array();
		$new_entry = fm_read($new_entry,$parent.'_'.($i+1));
		if ($parent == '1'){ 
			$arr[] = $new_entry;
		}
		else {
			$arr['submenu'][] = $new_entry;
		}
	}
	return $arr;
}

/**
  Convert menu array to a string that can be
  written to config.php.
*/
function fm_to_string($arr){
	$ret = 'array(';
	for($i=0;$i<count($arr);$i++){
		if (!isset($arr[$i]['label']) || empty($arr[$i]['label']))
			continue;
		$ret .= 'array(';
		$ret .= "'label'=>'".str_replace("'","",$arr[$i]['label'])."',";
		$ret .= "'url'=>'".(isset($arr[$i]['url'])?$arr[$i]['url']:'')."',";
		if (isset($arr[$i]['subheading']))
			$ret .= "'subheading'=>'".str_replace("'","",$arr[$i]['subheading'])."',";
		if (isset($arr[$i]['submenu']) && is_array($arr[$i]['submenu']))
			$ret .= "'submenu'=>".fm_to_string($arr[$i]['submenu']);
		$ret = rtrim($ret,',');
		$ret .= '),';
	}
	$ret = rtrim($ret,',');
	$ret .= ')';
	return $ret;
}

/**
  Draw menu recursively
*/
function fm_draw($arr,$parent='1'){
	echo '<ul>';
	$i=1;
	foreach($arr as $item){
		printf('<li>Text:<input type="text" name="label%s[]" value="%s" /> ',
			$parent,$item['label']);
		if ($parent == '1'){
			printf('Sub:<input type="text" name="subheading%s[]" value="%s" /> ',
				$parent,(isset($item['subheading'])?$item['subheading']:''));
		}
		printf('URL:<input type="text" name="url%s[]" value="%s" /> ',
			$parent,$item['url']);
		echo "\n";
		if (!isset($item['submenu']) || !is_array($item['submenu'])){
			$item['submenu'] = array();
		}
		if(empty($item['submenu'])){
			printf('<a href="" 
				onclick="$(\'#submenu%s_%s\').show();return false;"
				>Add submenu</a>',$parent,$i);
				
		}
		echo '</li><li id="submenu'.$parent.'_'.$i.'"';
		if (empty($item['submenu'])) echo 'style="display:none;"';
		echo '>';
		fm_draw($item['submenu'], $parent.'_'.$i);
		echo '</li>';
		echo "\n";
		$i++;
	}	
	printf('<li>New:<input type="text" name="label%s[]" value="" /> ',$parent);
	printf('URL:<input type="text" name="url%s[]" value="" /></li>',$parent);
	echo '<br />';
	echo '</ul>'."\n";
}
?>
