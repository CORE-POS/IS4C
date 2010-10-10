<?php

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

include_once($IS4C_PATH."quickkeys/quickkey.php");

$my_keys = array(
	new quickkey("Lock","LOCK"),
	new quickkey("Sign\nOff","SO"),
	new quickkey("Suspend\nResume","MG"),
	new quickkey("Cancel","CN"),
	new quickkey("Reprint","RP")
);

?>
