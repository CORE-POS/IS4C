<?php

$decision = isset($_REQUEST['input'])?strtoupper(trim($_REQUEST["input"])):'';
header('Location: AjaxDecision.php?input=' . $decision);

