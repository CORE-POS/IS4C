<?php

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!class_exists('BasicPage')) include($CORE_PATH.'gui-class-lib/BasicPge.php');
if (!function_exists('boxMsg')) include($CORE_PATH.'lib/drawscreen.php');

class HelloWorld extends BasicPage {

	function body_content(){
		$this->input_header();

		echo '<div class="baseHeight">';
		echo boxMsg('<b>Hello World!</b><br />
			Enter anything to continue');
		echo '</div>';

		$this->footer();
	}

	function preprocess(){
		global $CORE_PATH;

		if (isset($_REQUEST['reginput'])){
			header("Location: {$CORE_PATH}gui-modules/pos2.php");
			return False;
		}

		return True;
	}

}

?>
