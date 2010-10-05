<?php

include_once($_SESSION["INCLUDE_PATH"]."/quickkeys/quickkey.php");

$my_keys = array(
	new quickkey("Member","ID"),
	new quickkey("Non\nMember","11ID"),
	new quickkey("Subtotal","TL"),
	new quickkey("FS","FNTL"),
	new quickkey("Tax\nExempt","TETL")
);

?>
