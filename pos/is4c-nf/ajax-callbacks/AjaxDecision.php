<?php

$decision = isset($_REQUEST['input'])?strtoupper(trim($_REQUEST["input"])):'';
header('Location: ../ajax/AjaxDecision.php?input=' . $decision);

