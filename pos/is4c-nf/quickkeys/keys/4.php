<?php

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

$my_keys = array(
	new quickkey("Member","ID"),
	new quickkey("Non\nMember","11ID"),
	new quickkey("Subtotal","TL"),
	new quickkey("FS","FNTL"),
	new quickkey("Tax\nExempt","TETL")
);

?>
