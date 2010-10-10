<?php

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

include_once($IS4C_PATH."quickkeys/quickkey.php");

$my_keys = array(
	new quickkey("Cash","CA"),
	new quickkey("Check","CK"),
	new quickkey("Credit\nCard","CC"),
	new quickkey("EBT FS","EF"),
	new quickkey("EBT Cash","EF"),
	new quickkey("Store\nCharge","MI"),
	new quickkey("Gift Card","GD"),
	new quickkey("Coupon","CP"),
	new quickkey("Quarterly\nCoupon","MA"),
	new quickkey("InStore\nCoupon","IC"),
	new quickkey("Gift\nCertificate","TC"),
	new quickkey("Travelers\nCheck","TV")
);

?>

