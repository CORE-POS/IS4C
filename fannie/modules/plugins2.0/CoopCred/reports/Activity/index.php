<?php
/* Perhaps something like.
 */
include(dirname(__FILE__) . '/../../../../../config.php');
$memNum = isset($_REQUEST['memNum'])?(int)$_REQUEST['memNum']:'';
$programID = isset($_REQUEST['programID'])?(int)$_REQUEST['programID']:'';
header('Location: ActivityReport.php?memNum='.$memNum.'&programID='.$programID);
//header('Location: ActivityReport.php');

