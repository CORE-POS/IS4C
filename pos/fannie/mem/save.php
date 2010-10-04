<?php
include('../config.php');
include('MemberModule.php');
include('EnabledModules.php');

$memNum = isset($_REQUEST['memNum'])?$_REQUEST['memNum']:False;

$page_title = "Fannie :: Member $memNum";
$header = "Member $memNum";

include($FANNIE_ROOT.'src/header.html');

if ($memNum !== False){
	foreach($memModules as $mm){
		include('modules/'.$mm.'.php');
		$instance = new $mm();
		echo $instance->SaveFormData($memNum);
	}
	echo '<hr />';

	echo '<form action="save.php" method="post">';
	printf('<input type="hidden" name="memNum" value="%d" />',$_REQUEST['memNum']);
	foreach($memModules as $mm){
		$instance = new $mm();
		echo '<div style="float:left;">';
		echo $instance->ShowEditForm($memNum);
		echo '</div>';
	}
	echo '<div style="clear:left;"></div>';
	echo '<hr />';
	echo '<input type="submit" value="Save Changes" />';
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<input type="reset" value="Undo Changes" />';
	echo '</form>';
}

include($FANNIE_ROOT.'src/footer.html');

?>
