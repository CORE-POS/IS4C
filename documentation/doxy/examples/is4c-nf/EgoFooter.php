<?php

class EgoFooter extends FooterBox {

	var $header_css = "background: #990000; color: #ffffff;";

	var $display_css = "font-weight: bold; font-size: 200%;";

	function header_content(){
		return "notice";
	}

	function display_content(){
		return "IT Rules!";
	}
}

?>
