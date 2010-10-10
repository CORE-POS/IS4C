<?php

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

include_once($IS4C_PATH."quickkeys/quickkey.php");

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
