<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	*  4Jan2013 Eric Lee Add unmark option to allow an update to be run again.

*/

include('../config.php');
include('updates/Update.php');

echo '<html><head>';
echo '<script src="'.$FANNIE_URL.'src/jquery/js/jquery.js" type="text/javascript"></script>';
echo '</head><body>';
?>
<a href="index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="auth.php">Authentication</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="mem.php">Members</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="stores.php">Stores</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Updates
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="plugins.php">Plugins</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="sample_data/extra_data.php">Sample Data</a>
<?php

$action = isset($_REQUEST['action']) ? $_REQUEST['action']: 'list';
$updateID = isset($_REQUEST['u']) ? $_REQUEST['u'] : '';
switch($action){
	case 'view':
	case 'mark':
	case 'unmark':
		if (empty($updateID)){
			echo 'No update specified!';
			echo '<a href="update.php">Back</a>';
			exit;
		}
		$file_name = "updates/$updateID.php";
		$class_name = "update_$updateID";
		if (!file_exists($file_name)){
			echo 'Update not found!';
			echo '<a href="update.php">Back</a>';
			exit;
		}
		include($file_name);
		if (!class_exists($class_name)){
			echo 'Update is malformed!';
			echo '<a href="update.php">Back</a>';
			exit;
		}
		$obj = new $class_name();
		echo $obj->HtmlInfo();
		if ($action=='mark')
			$obj->SetStatus(True);
		if ($action=='unmark')
			$obj->SetStatus(False);
		if (!$obj->CheckStatus()){
			printf('<a href="update.php?action=apply&u=%s">Apply Update</a><br />',$updateID);
			printf('<a href="update.php?action=mark&u=%s">Mark Update Complete</a><br />',$updateID);
		} else {
			printf('<a href="update.php?action=unmark&u=%s" title="This does not un-do the Update. Not all Updates can be re-run.">Un-mark Update (so it can be run again)</a><br />',$updateID);
		}
		echo '<a href="update.php">Back to List of Updates</a>';
		echo "<hr />";
		echo "<b>Query details</b>:<br />";
		echo $obj->HtmlQueries();
			
		break;
	case 'apply':
		if (empty($updateID)){
			echo 'No update specified!';
			echo '<a href="update.php">Back</a>';
			exit;
		}
		$file_name = "updates/$updateID.php";
		$class_name = "update_$updateID";
		if (!file_exists($file_name)){
			echo 'Update not found!';
			echo '<a href="update.php">Back</a>';
			exit;
		}
		include($file_name);
		if (!class_exists($class_name)){
			echo 'Update is malformed!';
			echo '<a href="update.php">Back</a>';
			exit;
		}
		$obj = new $class_name();
		echo $obj->ApplyUpdates();
		echo '<hr />';
		echo "If the queries all succeeded, this update is automatically marked complete.
If not, you can make corrections in your database and refresh this page to try again or just make
alterations directly";
		echo '<br /><br />';	
		if ( !$obj->CheckStatus() ) {
			printf('<a href="update.php?action=mark&u=%s">Manually Mark Update $updateID Complete</a><br />',$updateID);
		} else {
			echo "Update $updateID has been Marked Complete.<br />";
			printf('<a href="update.php?action=unmark&u=%s" title="This does not un-do the Update. Not all Updates can be re-run.">Un-mark Update (so it can be run again)</a><br />',$updateID);
		}
		echo '<a href="update.php">Back to List of Updates</a>';
		break;
	case 'list':
		// find update files
		$dh = opendir('updates');
		$updates = array();
		while ( ($file=readdir($dh)) !== False ){
			if ($file[0] == ".") continue;
			if ($file == "Update.php") continue;
			if (!is_file('updates/'.$file)) continue;
			if (substr($file,-4) != ".php") continue;
			$updates[] = $file;
		}
		sort($updates);

		// check for new vs. finished and put in separate arrays.
		$new = array();
		$done = array();
		foreach($updates as $u){
			$key = substr($u,0,strlen($u)-4);
			include('updates/'.$u);
			if (!class_exists('update_'.$key)) continue;
			$class = "update_$key";
			$obj = new $class();
			if ($obj->CheckStatus())
				$done[] = $key;
			else
				$new[] = $key;
		}

		// display
		echo '<h3>Available Updates</h3>';
		echo '<ul>';
		foreach($new as $key){
			printf('<li><a href="update.php?action=view&u=%s">%s</a></li>',
				$key,$key);
		}
		echo '</ul>';
		echo '<h3>Applied Updates</h3>';
		echo '<ul>';
		foreach($done as $key){
			printf('<li><a href="update.php?action=view&u=%s">%s</a></li>',
				$key,$key);
		}
		echo '</ul>';
		break;
	default:
		echo 'Action unknown!';
		echo '<a href="update.php">Back</a>';
		break;
}

?>
