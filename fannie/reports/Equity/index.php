<?php

include(dirname(__FILE__) . '/../../config.php');

$memNum = isset($_REQUEST['memNum'])?(int)$_REQUEST['memNum']:'';
header('Location: EquityReport.php?memNum='.$memNum);

