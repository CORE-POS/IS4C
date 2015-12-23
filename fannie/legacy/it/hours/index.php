<?php

include('../../../config.php');

require($FANNIE_ROOT.'auth/login.php');

$all = validateUserQuiet('view_all_hours');
$name = checkLogin();

if ($all)
    header("Location: menu.php");
elseif ($name)
    header("Location: viewEmployee.php?id=".getUID($name));
else
    header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/");

