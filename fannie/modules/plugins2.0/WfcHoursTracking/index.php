<?php
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}

include(dirname(__FILE__).'/../../../config.php');

if (!function_exists('validateUserQuiet')) {
    require($FANNIE_ROOT.'auth/login.php');
}

$all = validateUserQuiet('view_all_hours');
$name = checkLogin();

if ($all) {
    header("Location: WfcHtMenuPage.php");
} elseif ($name) {
    header("Location: WfcHtViewEmpPage.php?id=".getUID($name));
} else {
    header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$_SERVER['PHP_SELF']}");
}

?>
