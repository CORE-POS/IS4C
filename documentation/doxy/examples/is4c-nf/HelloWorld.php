<?php

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class HelloWorld extends BasicPage {

	function body_content(){
		$this->input_header();

		echo '<div class="baseHeight">';
		echo DisplayLib::boxMsg('<b>Hello World!</b><br />
			Enter anything to continue');
		echo '</div>';

		$this->footer();
	}

	function preprocess(){
		if (isset($_REQUEST['reginput'])){
			header("Location: {$this->page_url}gui-modules/pos2.php");
			return False;
		}

		return True;
	}

}

new HelloWorld();

?>
