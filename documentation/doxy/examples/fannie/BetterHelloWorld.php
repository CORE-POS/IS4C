<?php

class BetterHelloWorld extends FanniePage {
	
	public $required = False;

	public $description = "A better Hello World module";
	
	protected $title = 'Hello World';
	protected $header = "Fannie :: Hello World";

	function body_content(){
		return "Welcome to Fannie programming!";
	}
}

?>
