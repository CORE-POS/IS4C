<?php
$_SESSION["datetimestamp"] = strftime("%m/%d/%y %I:%M %p", time());
$datetimestamp = $_SESSION["datetimestamp"];
$standalone = $_SESSION["standalone"];

if ($_SESSION["training"] == 1 || $_SESSION["CashierNo"] == 9999) {
    $training = 1; 
}
else {
    $training = 0;
}

if ($_SESSION["CCintegrate"] == 1) {
    if ($_SESSION["ccMysql"] == 0) {
        $ccstatus = "<img src='graphics/ccFail.gif' alt='Credit card failed' />";
    }
    elseif ($_SESSION["training"] == 1 || $_SESSION["ccLive"] == 0) {
        $ccstatus = "<img src='graphics/ccTest.gif' alt='Credit card test' />";
    }
    else {
        $ccstatus = "<img src='graphics/ccIn.gif' alt='Credit card in' />";
    }
} 
else {
    $ccstatus = "";
}

echo $datetimestamp . "::" . $standalone . "::" . $training . "::" . $ccstatus;
?>