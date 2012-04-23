<?php

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!class_exists('Parser')) include($CORE_PATH.'parser-class-lib/Parser.php');
if (!function_exists("boxMsg")) include_once($CORE_PATH."lib/drawscreen.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class HW_Parser extends Parser {

	function check($str){

		if (strtoupper($str) == "HW")
			return True;

		return False;
		
	}

	function parse($str){
		global $CORE_LOCAL, $CORE_PATH;

		$return_value = $this->default_json();

		if ($CORE_LOCAL->get("LastID") != "0"){
			$return_value['output'] = boxMsg("transaction in progress");
		}
		else {
			$return_value['main_frame'] = $CORE_PATH.'gui-modules/HelloWorld.php';
		}

		return $return_value;
	}

}

?>
