<?php

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

include_once($IS4C_PATH."quickkeys/quickkey.php");

$my_keys = array(
	new quickkey("Local\nJumbo","1012"),
	new quickkey("Local\nXL","1011"),
	new quickkey("Local\nLarge","1010"),
	new quickkey("Local\nMedium","1009"),
	new quickkey("Local\nSmall","1008"),
	new quickkey("Whole\nFarm\nLarge","1013"),
	new quickkey("Whole\nFarm\nMedium","1016"),
	new quickkey("Larry's\nLarge","1133"),
	new quickkey("Larry's\nMedium","1132")
);

?>
