<?php

$memNum = isset($_REQUEST['memNum'])?(int)$_REQUEST['memNum']:'';
header('Location: SuspensionHistoryReport.php?memNum='.$memNum);

