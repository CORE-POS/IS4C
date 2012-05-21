<?php
$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

require_once ($CORE_PATH."lib/lib.php");

goodBeep();
twoPairs();
errorBeep();

rePoll();

udpSend('twoPairs');

?>