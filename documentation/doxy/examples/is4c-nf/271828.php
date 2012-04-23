<?php

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!class_exists('quickkey') include($CORE_PATH."quickkeys/quickkey.php");

$my_keys = array(
	new quickkey("Products","QK3"),
	new quickkey("Depart-\nments","QK1"),
	new quickkey("Item\nSearch","PV"),
	new quickkey("Tare","TW"),
	new quickkey("Tax Shift","1TN"),
	new quickkey("FS Shift","FN"),
	new quickkey("Void","VD"),
	new quickkey("Refund","RF")
);

?>
