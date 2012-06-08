<?php

class HW_Parser extends Parser {

	function check($str){

		if (strtoupper($str) == "HW")
			return True;

		return False;
		
	}

	function parse($str){
		global $CORE_LOCAL;

		$return_value = $this->default_json();

		if ($CORE_LOCAL->get("LastID") != "0"){
			$return_value['output'] = DisplayLib::boxMsg("transaction in progress");
		}
		else {
			$return_value['main_frame'] = MiscLib::base_url().'gui-modules/HelloWorld.php';
		}

		return $return_value;
	}

}

?>
