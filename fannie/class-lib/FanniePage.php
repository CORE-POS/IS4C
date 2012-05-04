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

	public $required = True;

	public $description = "
	Base class for creating HTML pages.
	";

	protected $title = 'Page window title';
 	protected $header = 'Page displayed header';
	protected $window_dressing = True;
	protected $onload_commands = array();
	protected $scripts = array();

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
		return ob_get_clean();

	}

	/**
	  Get the standard footer
	  @return An HTML string
	*/
	function get_footer(){
		global $FANNIE_ROOT, $FANNIE_AUTH_ENABLED, $FANNIE_URL;
		ob_start();
		include($FANNIE_ROOT.'src/footer.html');
		return ob_get_clean();
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
	  Add a script to the page using <script> tags
	  @param $file_url the script URL
	  @param $type the script type
	*/
	function add_script($file_url,$type="text/javascript"){
		$this->scripts[$file_url] = $type;
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
				echo $this->get_header();

			echo $this->body_content();

			if ($this->window_dressing)
				echo $this->get_footer();

			foreach($this->scripts as $s_url => $s_type){
				printf('<script type="%s" src="%s"></script>',
					$s_type, $s_url);
				echo "\n";
			}
			
			$js_content = $this->javascript_content();
			if (!empty($js_content) || !empty($this->onload_commands)){
				echo '<script type="text/javascript">';
				echo $js_content;
				echo "\n\$(document).ready(function(){\n";
				foreach($this->onload_commands as $oc)
					echo $oc."\n";
				echo "});\n";
				echo '</script>';
			}

			$page_css = $this->css_content();
			if (!empty($page_css)){
				echo '<style type="text/css">';
				echo $page_css;
				echo '</style>';
			}
		}
	}

	/**
	  Get a form tag with this module as the action
	  @param $type form method (get or post)
	  @return An HTML string
	*/
	function form_tag($type='post'){
		global $FANNIE_URL;
		if (basename($_SERVER['PHP_SELF'])==get_class($this).".php"){
			$ret = sprintf('<form method="%s" action="%s">
				<input type="hidden" name="m" value="%s" />',
				$type, $_SERVER['PHP_SELF'],'none'
			);
		}
		else {
			$ret = sprintf('<form method="%s" action="%s">
				<input type="hidden" name="m" value="%s" />',
				$type, $FANNIE_URL.'modules/',
				get_class($this)
			);
		}
		return $ret;
	}

	/**
	  Get the URL for this page
	  @return A URL string
	*/
	function module_url(){
		global $FANNIE_URL;
		if (basename($_SERVER['PHP_SELF'])==get_class($this).".php")
			return $_SERVER['PHP_SELF']."?m=none";
		else
			return $FANNIE_URL.'modules/?m='.get_class($this);
	}

	function provides_functions(){
		return array(
			'get_form_value'
		);
	}
}

/**
  @file
  @brief Functions provided by FanniePage
*/

/**
  Safely fetch a form value
  @param $name the field name
  @param $default default value if the form value doesn't exist
  @return The form value, if available, otherwise the default.
*/
function get_form_value($name, $default=''){
	return (isset($_REQUEST[$name])) ? $_REQUEST[$name] : $default;
}

/**
  @example BetterHelloWorld.php
  Using FanniePage for this module means the header,
  footer, and menu will be included. The body_content()
  method defines what goes in the main content area
  of the page. 
*/

?>
