<?php

include('../../../../config.php');

require($FANNIE_ROOT.'auth/login.php');
include('../db.php');

$name = checkLogin();
$perm = validateUserQuiet('evals');
if ($name === false && $perm === false){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/eval/list.php");
	exit;
}
else if ($perm === false){
	echo "Error";
	exit;
}

header("Location: list.php");

?>
