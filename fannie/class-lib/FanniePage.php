<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
  @class FanniePage
  Class for drawing screens
*/
class FanniePage extends FannieModule {

	private $title;
 	private $header;
	private $window_dressing;
	private $onload_commands;

	/**
	  Constructor
	  @param $title Page window title
	  @param $header Page displayed header
	*/
	function FanniePage($title, $header){
		$this->title = $title;
		$this->header = $header;	
		$this->window_dressing = True;
		$this->onload_commands = array();
	}

	/**
	  Toggle using menus
	  @param $menus boolean
	*/
	function has_menus($menus){
		$this->window_dressing = ($menus) ? True : False;
	}

	/**
	  Get the standard header
	  @return An HTML string
	*/
	function get_header(){
		global $FANNIE_ROOT;
		ob_start();
		$page_title = $this->title;
		$header = $this->header;
		include($FANNIE_ROOT.'src/header.html');
		return ob_end_clean();

	}

	/**
	  Get the standard footer
	  @return An HTML string
	*/
	function get_footer(){
		global $FANNIE_ROOT;
		ob_start();
		include($FANNIE_ROOT.'src/footer.html');
		return ob_end_clean();
	}

	/**
	  Handle pre-display tasks such as input processing
	  @return
	   - True if the page should be displayed
	   - False to stop here

	  Common uses include redirecting to a different module
	  and altering body content based on input
	*/
	function preprocess(){
		return True;
	}
	
	/**
	  Define the main displayed content
	  @return An HTML string
	*/
	function body_content(){

	}

	/**
	  Define any javascript needed
	  @return A javascript string
	*/
	function javascript_content(){

	}

	/**
	  Define any CSS needed
	  @return A CSS string
	*/
	function css_content(){

	}

	/**
	  Queue javascript commands to run on page load
	*/
	function add_onload_command($str){
		$this->onload_commands[] = $str;	
	}

	/**
	  Check for input and display the page
	*/
	function run_module(){
		if ($this->preprocess()){
			
			if ($this->window_dressing)
				echo get_header();

			echo body_content();

			if ($this->window_dressing)
				echo get_footer();

			echo '<script type="text/javascript">';
			echo javascript_content();
			echo "\n\$(document).ready(function(){\n";
			foreach($this->onload_commands as $oc)
				echo $oc."\n";
			echo "}\n";
			echo '</script>';

			echo '<style type="text/css">';
			echo css_content();
			echo '</style>';
		}
	}
}

?>
