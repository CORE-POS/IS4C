<?php

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

include_once($IS4C_PATH."quickkeys/quickkey.php");

$my_keys = array(
	new quickkey("Body\nCare","DP930"),
	new quickkey("Books","DP2440"),
	new quickkey("Bread","DP1500"),
	new quickkey("Bulk","DP130"),
	new quickkey("Cheese","DP2300"),
	new quickkey("Coffee","DP2570"),
	new quickkey("Cool","DP340"),
	new quickkey("Deli","DP680"),
	new quickkey("Frozen","DP460"),
	new quickkey("General\nMerch","DP2420"),
	new quickkey("Grocery","DP1860"),
	new quickkey("Herbs &\nSpices","DP2530"),
	new quickkey("Maga-\nzines","DP2400"),
	new quickkey("Meat\nFresh","DP2600"),
	new quickkey("Meat\nFrozen","DP2610"),
	new quickkey("Misc\nReceipt","DP7030"),
	new quickkey("Produce","DP2040"),
	new quickkey("Supple-\nments","DP1010"),
);

?>
