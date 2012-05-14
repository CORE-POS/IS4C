<?php

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

$my_keys = array(
	new quickkey("All\nEggs","QK7"),
	new quickkey("All\nMilk","QK8"),
	new quickkey("Deli\nCoffee","21190040099"),
	new quickkey("Baked\nGood","1017"),
	new quickkey("Single\nCookie","6000"),
	new quickkey("Dozen\nCookies","6001"),
	new quickkey("Blue Sky\nOrganic","9930"),
	new quickkey("Blue Sky\nSpritzer","9931"),
	new quickkey("Blue Sky\nRegular","9932"),
	new quickkey("Bottle","8366"),
	new quickkey("Coffee\nBag","5006"),
	new quickkey("Bag\nRefund","5005"),
	new quickkey("Growler\nReturn","1092"),
	new quickkey("Cards","8661"),
	new quickkey("Totes","1003")
);

?>
