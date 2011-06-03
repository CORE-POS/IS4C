<?php

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

include_once($CORE_PATH."quickkeys/quickkey.php");

$my_keys = array(
	new quickkey("AR\nPayment","DP990"),
	new quickkey("Equity A","DP9920"),
	new quickkey("Equity B","DP9910"),
	new quickkey("Balance","BQ")
);

?>
