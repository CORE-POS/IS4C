<?php

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

include_once($IS4C_PATH."quickkeys/quickkey.php");

$my_keys = array(
	new quickkey("Milk\nReturn","9999904"),
	new quickkey("Dahl's\nWhole","1034"),
	new quickkey("Dahl's\n2%","1033"),
	new quickkey("Dahl's\n1%","1032"),
	new quickkey("Dahl's\nSkim","1031")
);

?>
