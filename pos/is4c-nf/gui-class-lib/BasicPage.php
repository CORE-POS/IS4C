<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

    This file is part of IT CORE.

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

 @class BasicPage
  
   This is the base class for all display scripts

   Display scripts are not required to use this
   base class but it does provide a lot of common
   functionality for building HTML pages with standard
   headers, footers, and styling. 

 */

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

class BasicPage {

	var $onload_commands;

	/**
	  Constructor

	  The constructor automatically runs
	  the preprocess and print_page methods
	  (if applicable). Creating a new instance
	  will output the entire page contents
	*/
	function BasicPage(){
		$this->onload_commands = "";
		if ($this->preprocess()){
			ob_start();
			$this->print_page();
			while (ob_get_level() > 0)
				ob_end_flush();
		}
	}

	/**
	  Add output in the <head> section
	  @return None

	  This function should print anything that
	  belongs inside the HTML head tags
	*/
	function head_content(){

	}

	/**
	  Add output in the <body> section
	  @return None

	  This function should print anything that
	  belongs inside the HTML body tags
	*/
	function body_content(){

	}

	/**
	  Decide whether to display output
	  @return True or False

	  This is the first function called. It is typically
	  used to check $_GET or $_POST variables. If the
	  function returns True, the rest of the page will be
	  printed. If the function returns False, there is no
	  output. Usually this function returns False after 
	  setting a redirect header to change to another page.
	*/
	function preprocess(){
		return True;
	}

	/**
	  Print HTML output
	  @return None

	  Print the page. This version includes the scale
	  weight display as well as the head and body
	  content from those methods. Javascript commands
	  that have been requested via add_onload_command
	  are all run on page load.
	*/
	function print_page(){
		global $CORE_PATH;
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html>
		<?php
		echo "<head>";
		echo "<link rel=\"stylesheet\" type=\"text/css\"
		    href=\"{$CORE_PATH}/pos.css\">";
		echo "<script type=\"text/javascript\"
			src=\"{$CORE_PATH}/js/jquery.js\"></script>";
		$this->head_content();
		echo "</head>";
		echo "<body>";
		echo "<div id=\"boundingBox\">";
		$this->body_content();	
		echo "</div>";
		$this->scale_box();
		$this->scanner_scale_polling();
		echo "</body>";
		if (!empty($this->onload_commands)){
			echo "<script type=\"text/javascript\">\n";
			echo "\$(document).ready(function(){\n";
			echo $this->onload_commands;
			echo "});\n";
			echo "</script>\n";
		}
		echo "</html>";
	}

	/**
	  Add a javascript command to the queue
	  @param $str A javascript command
	  @return None
	
	  All queued commands are run once the page loads
	  Note: JQuery is present
	*/
	function add_onload_command($str){
		$this->onload_commands .= $str."\n";
	}

	/**
	  Display the standard header with input box
	  @param $action What the form does
	  @return None

	  The default action is for a page to POST
	  back to itself. Any specified action will
	  be included in the form tag exactly as is.
	  You can pass "action=..." or "onsubmit=..."
	  (or both) but $action should have one or the
	  other attributes
	*/
	function input_header($action=""){
		global $CORE_LOCAL,$CORE_PATH;
		if (empty($action))
			$action = "action=\"".$_SERVER['PHP_SELF']."\"";

		$this->add_onload_command("betterDate();\n\$('#reginput').focus();");
		
		$inputType = "text";
		if ($CORE_LOCAL->get("inputMasked") != 0)
			$inputType = "password";
		// this needs to be configurable; just fixing
		// a giant PHP warning for the moment
		$time = strftime("%m/%d/%y %I:%M %p", time());

		$CORE_LOCAL->set("repeatable",0);
		?>
		<script type="text/javascript">
		function betterDate() {
			var myNow = new Date();
			var ampm = 'AM';
			var hour = myNow.getHours();
			var minute = myNow.getMinutes();
			if (hour >= 12){
				ampm = 'PM';
				hour = hour - 12;
			}
			if (hour == 0) hour = 12;

			var year = myNow.getYear() % 100;
			var month = myNow.getMonth()+1;
			var day = myNow.getDate();
			if (year < 10) year = '0'+year;
			if (month < 10) month = '0'+month;
			if (day < 10) day ='0'+day;
			if (minute < 10) minute = '0'+minute;

			var timeStr = month+'/'+day+'/'+year+' ';
			timeStr += hour+':'+minute+' '+ampm;
			$('#timeSpan').html(timeStr);
			setTimeout(betterDate,20000);
		}
		</script>
		<div id="inputArea">
			<div class="inputform <?php echo ($CORE_LOCAL->get("training")==1?'training':''); ?>">
				<form name="form" id="formlocal" method="post" autocomplete="off"
					<?php echo $action; ?> >
				<input name="reginput" value="" onblur="$('#reginput').focus();"
					type="<?php echo $inputType; ?>" id="reginput"  />
				</form>
			</div>
			<div class="notices <?php echo ($CORE_LOCAL->get("training")==1?'training':''); ?>">
			<?php
			if ($CORE_LOCAL->get("training") == 1) {
				echo "<span class=\"text\">training </span>"
				     ."<img src='{$CORE_PATH}graphics/BLUEDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			elseif ($CORE_LOCAL->get("standalone") == 0) {
				echo "<img src='{$CORE_PATH}graphics/GREENDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			else {
				echo "<span class=\"text\">stand alone</span>"
				     ."<img src='{$CORE_PATH}graphics/REDDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			if($CORE_LOCAL->get("CCintegrate") == 1 && 
				$CORE_LOCAL->get("ccLive") == 1 && $CORE_LOCAL->get("training") == 0){
			   echo "<img src='{$CORE_PATH}graphics/ccIn.gif'>&nbsp;";
			}elseif($CORE_LOCAL->get("CCintegrate") == 1 && 
				($CORE_LOCAL->get("training") == 1 || $CORE_LOCAL->get("ccLive") == 0)){
			   echo "<img src='{$CORE_PATH}graphics/ccTest.gif'>&nbsp;";
			}

			echo "<span id=\"timeSpan\" class=\"time\">".$time."</span>\n";
			if ($CORE_LOCAL->get("prefix") != ""){
				$this->add_onload_command("\$('#reginput').val('"
					.$CORE_LOCAL->get("prefix")."');\n");
				$CORE_LOCAL->set("prefix","");
			}
			?>

			</div>
		</div>
		<div id="inputAreaEnd"></div>
		<?php
	}

	/**
	  Display the standard header without input box
	  @return None
	*/
	function noinput_header(){
		global $CORE_LOCAL;
		$this->add_onload_command("betterDate();\n");
		
		$time = strftime("%m/%d/%y %I:%M %p", time());

		$CORE_LOCAL->set("repeatable",0);
		?>
		<script type="text/javascript">
		function betterDate() {
			var myNow = new Date();
			var ampm = 'AM';
			var hour = myNow.getHours();
			var minute = myNow.getMinutes();
			if (hour >= 12){
				ampm = 'PM';
				hour = hour - 12;
			}
			if (hour == 0) hour = 12;

			var year = myNow.getYear() % 100;
			var month = myNow.getMonth()+1;
			var day = myNow.getDate();
			if (year < 10) year = '0'+year;
			if (month < 10) month = '0'+month;
			if (day < 10) day ='0'+day;
			if (minute < 10) minute = '0'+minute;

			var timeStr = month+'/'+day+'/'+year+' ';
			timeStr += hour+':'+minute+' '+ampm;
			$('#timeSpan').html(timeStr);
			setTimeout(betterDate,20000);
		}
		</script>
		<div id="inputArea">
			<div class="inputform">
			&nbsp;
			</div>
			<div class="notices">
			<?php	
			if ($CORE_LOCAL->get("training") == 1) {
				echo "<span class=\"text\">training </span>"
				     ."<img src='/graphics/BLUEDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			elseif ($CORE_LOCAL->get("standalone") == 0) {
				echo "<img src='/graphics/GREENDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			else {
				echo "<span class=\"text\">stand alone</span>"
				     ."<img src='/graphics/REDDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			if($CORE_LOCAL->get("CCintegrate") == 1 && 
				$CORE_LOCAL->get("ccLive") == 1 && $CORE_LOCAL->get("training") == 0){
			   echo "<img src='/graphics/ccIn.gif'>&nbsp;";
			}elseif($CORE_LOCAL->get("CCintegrate") == 1 && 
				($CORE_LOCAL->get("training") == 1 || $CORE_LOCAL->get("ccLive") == 0)){
			   echo "<img src='/graphics/ccTest.gif'>&nbsp;";
			}

			echo "<span id=\"timeSpan\" class=\"time\">".$time."</span>\n";
			?>

			</div>
		</div>
		<div id="inputAreaEnd"></div>
		<?php
	}

	/**
	  Output the standard scale display box
	  @return None
	*/
	function scale_box(){
		?>
		<div id="scalebox">
			<div id="scaleTop"> 
			weight
			</div>
			<div id="scaleBottom">
			<?php echo DisplayLib::scaledisplaymsg(); ?>	
			</div>
		</div>
		<?php
	}

	/**
	  Read input from scale
	  @return None

	  Outputs the javascript used to poll for scale
	  input and activates it on page load.
	*/
	function scanner_scale_polling($include_scans=True){
		global $CORE_PATH;
		?>
		<script type="text/javascript"
			src="<?php echo $CORE_PATH; ?>js/poll-scale.js">
		</script>
		<?php
		$this->add_onload_command("pollScale('$CORE_PATH');\n");
	}

	/**
	  Print the standard footer
	  @return None
	*/
	function footer(){
		echo '<div id="footer">';
		DisplayLib::printfooter();
		echo '</div>';
	}

	/**
	  Go to a different page
	  @param $url the new page URL

	  Use this function instead of manual redirects
	  to allow debug output.
	*/
	function change_page($url){
		global $CORE_LOCAL;
		if ($CORE_LOCAL->get("Debug_Redirects") == 1){
			$stack = debug_backtrace();
			printf('Follow redirect to <a href="%s">%s</a>',$url,$url);
			echo '<hr />Stack:';
			foreach($stack as $s){
				echo '<ul><li>';
				if(!empty($s['class'])) echo $s['class'].'::';
				echo $s['function'].'()';
				echo '<li>Line '.$s['line'].', '.$s['file'];
			}
			foreach($stack as $s) echo '</ul>';
		}
		else
			header("Location: ".$url);
	}
}

/**
   @example HelloWorld.php

  The first two line snippet is path detection. 
  Every code file should start with these lines.

  The next two lines demonstrate standard include format.
  Check whether the needed class/function already exists
  and use the detected path $CORE_PATH.

  body_content() draws the page. Methods from BasicPage
  provide the standard input box at the top and footer
  at the bottom. boxMsg() is a utility function that
  puts the 'Hello World' message in a standard message
  box.

  preprocess() handles input. In this case any form
  input causes a redirect to the main display script.

  Note the very last line creating an object. That's
  necessary to actually display anything.

*/

?>
