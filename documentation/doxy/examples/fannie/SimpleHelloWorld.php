<?php

class SimpleHelloWorld extends FannieModule {

	public $required = False;

	public $description = "A very simple hello world module";

	function run_module(){
		echo 'Hello World';
	}
}

?>
