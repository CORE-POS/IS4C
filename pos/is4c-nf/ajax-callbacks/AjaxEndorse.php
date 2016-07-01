<?php

$endorseType = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
$amount = isset($_REQUEST['amount']) ? $_REQUEST['amount'] : '';
header('Location: ../ajax/AjaxEndorse.php?type=' . $type . '&amount=' . $amount);

