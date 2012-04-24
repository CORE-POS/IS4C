<?php

class FunctionLibrary extends FannieModule {
	
	public $required = False;
	
	public $description = "A function library";

	function provided_functions(){
		return array(
			'fl_blink_function',
			'fl_pow_function'
		);
	}
}

function fl_blink_function($str){
	return '<blink>'.$str.'</blink>';
}

function fl_pow_function($base, $exp){
	if ($exp == 0) return 1;
	elseif($exp == 1) return $base;
	else return $base*fl_pow_function($base,$exp-1);
}

?>
