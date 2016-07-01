<?php

$entered = "";
if (isset($_REQUEST["input"])) {
    $entered = strtoupper(trim($_REQUEST["input"]));
}
header('Location: ../ajax/AjaxParser.php?input=' . $entered);

