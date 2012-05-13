<?php

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

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
