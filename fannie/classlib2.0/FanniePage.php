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

if (!function_exists('validateUserQuiet'))
	include($FANNIE_ROOT.'auth/login.php');

/**
  @class FanniePage
  Class for drawing screens
*/
class FanniePage {

	public $required = True;

	public $description = "
	Base class for creating HTML pages.
	";

	/** force users to login immediately */
	protected $must_authenticate = False;
	/** name of the logged in user (or False is no one is logged in) */
	protected $current_user = False;
	/** list of either auth_class(es) or array(auth_class, start, end) tuple(s) */
	protected $auth_classes = array();

	protected $title = 'Page window title';
 	protected $header = 'Page displayed header';
	protected $window_dressing = True;
	protected $onload_commands = array();
	protected $scripts = array();
	protected $css_files = array();

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

	function add_css_file($file_url){
		$this->css_files[] = $file_url;
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
	  Send user to login page
	*/
	function login_redirect(){
		global $FANNIE_URL;
		$redirect = $_SERVER['REQUEST_URI'];
		$url = $FANNIE_URL.'auth/ui/loginform.php';
		header('Location: '.$url.'?redirect='.$redirect);
	}

	/**
	  Check if the user is logged in
	*/
	function check_auth(){
		foreach($this->auth_classes as $class){
			$try = False;
			if (is_array($class) && count($class) == 3)
				$try = validateUserQuiet($class[0],$class[1],$class[2]);
			else
				$try = validateUserQuiet($class);
			if ($try){
				$this->current_user = $try;
				return True;
			}
		}
		$try = checkLogin();
		if ($try && empty($this->auth_classes)){
			$this->current_user = $try;
			return True;
		}
		return False;
	}

	/**
	  Check for input and display the page
	*/
	function draw_page(){

		if (!$this->check_auth() && $this->must_authenticate){
			$this->login_redirect();
		}
		elseif ($this->preprocess()){
			
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
			foreach($this->css_files as $css_url){
				printf('<link rel="stylesheet" type="text/css" href="%s">',
					$css_url);
				echo "\n";
			}
		}
	}
}

?>
