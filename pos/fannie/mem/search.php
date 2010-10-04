<?php
include('../config.php');
include('MemberModule.php');
include('EnabledModules.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$page_title = "Fannie :: Find Member";
$header = "Find Member";

$searchButton = isset($_REQUEST['doSearch'])?True:False;

if (!$searchButton){
	include($FANNIE_ROOT.'src/header.html');

	echo '<form action="search.php" method="post">';
	echo '<p><b>Member Number</>: <input type="text" name="memNum" id="mn" size="5" /></p>';
	foreach($memModules as $mm){
		include('modules/'.$mm.'.php');
		$instance = new $mm();
		if ($instance->HasSearch()){
			echo $instance->ShowSearchForm();
		}
	}
	echo '<hr />';
	echo '<input type="submit" value="Search" name="doSearch" />';
	echo '</form>';
	echo '<script type="text/javascript">';
	echo "$(document).ready(function(){
		\$('input#mn').focus();
	});";
	echo '</script>';

	include($FANNIE_ROOT.'src/footer.html');
}
else {
	$results = array();
	if (isset($_REQUEST['memNum'])){
		$q = sprintf("SELECT cardno FROM custdata WHERE cardno=%d",$_REQUEST['memNum']);
		$r = $dbc->query($q);
		if ($dbc->num_rows($r) > 0){
			header("Location: edit.php?memNum=".$_REQUEST['memNum']);
			exit;
		}
	}
	foreach($memModules as $mm){
		include('modules/'.$mm.'.php');
		$instance = new $mm();
		if ($instance->HasSearch()){
			$tmp = $instance->GetSearchResults();
			if (empty($results)) $results = $tmp;
		}
	}

	include($FANNIE_ROOT.'src/header.html');

	if (empty($results)){
		echo "<i>Error</i>: No matching member found";
	}
	else {
		echo "<ul>";
		foreach($results as $cn => $name){
			echo "<li><a href=\"edit.php?memNum=$cn\">$cn $name</a></li>";
		}
		echo "</ul>";
	}

	include($FANNIE_ROOT.'src/footer.html');
}

?>
