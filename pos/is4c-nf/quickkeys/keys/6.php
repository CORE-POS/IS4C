<?php

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

include_once($CORE_PATH."quickkeys/quickkey.php");

$my_keys = array(
	new quickkey("Lock","LOCK"),
	new quickkey("Sign\nOff","SO"),
	new quickkey("Suspend\nResume","MG"),
	new quickkey("Cancel","CN"),
	new quickkey("Reprint","RP")
);

?>
