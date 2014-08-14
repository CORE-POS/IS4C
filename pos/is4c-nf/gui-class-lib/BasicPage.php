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

class BasicPage {

	var $onload_commands;
	/**
	  Relative URL for POS root directory
	  Pages often need this.
	*/
	var $page_url;

	var $body_class='mainBGimage';

	/**
	  Constructor

	  The constructor automatically runs
	  the preprocess and print_page methods
	  (if applicable). Creating a new instance
	  will output the entire page contents
	*/
	function BasicPage(){
		$this->onload_commands = "";
		$this->page_url = MiscLib::base_url();
		if ($this->preprocess()){
			ob_start();
			$this->print_page();
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
		$my_url = $this->page_url;
		?>
		<!DOCTYPE html>
		<html>
		<?php
		echo "<head>";
		echo "<title>COREPOS</title>";
		// 18Aug12 EL Add content/charset.
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\"
		    href=\"{$my_url}/css/pos.css\">";
		// include store css file if it exists
		if (file_exists(dirname(__FILE__).'/../css/store.css')){
			echo "<link rel=\"stylesheet\" type=\"text/css\"
			    href=\"{$my_url}/css/store.css\">";
		}
		echo "<script type=\"text/javascript\"
			src=\"{$my_url}/js/jquery.js\"></script>";
		$this->head_content();
		echo "</head>";
		echo '<body class="'.$this->body_class.'">';
		echo "<div id=\"boundingBox\">";
		$this->body_content();	
		echo "</div>";
		$this->scale_box();
		$this->scanner_scale_polling();
		// body_content populates onload_commands
		if (!empty($this->onload_commands)){
			echo "\n<script type=\"text/javascript\">\n";
			echo "\$(document).ready(function(){\n";
			echo $this->onload_commands;
			echo "});\n";
			echo "</script>\n";
		}
		// 18Aug12 EL Moved after ready-script.
		echo "</body>\n";
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

	protected $mask_input = False;
	function hide_input($bool){
		$this->mask_input = $bool;
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
		global $CORE_LOCAL;
		$my_url = $this->page_url;
		if (empty($action))
			$action = "action=\"".$_SERVER['PHP_SELF']."\"";

		$this->add_onload_command("betterDate();\n\$('#reginput').focus();");
		
		$inputType = "text";
		if ($this->mask_input)
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
			<div class="notices coloredText <?php echo ($CORE_LOCAL->get("training")==1?'training':''); ?>">
			<?php
			if ($CORE_LOCAL->get("training") == 1) {
				echo "<span class=\"text\">"._("training")." </span>"
				     ."<img alt=\"training\" src='{$my_url}graphics/BLUEDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			elseif ($CORE_LOCAL->get("standalone") == 0) {
				echo "<img alt=\"online\" src='{$my_url}graphics/GREENDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			else {
				echo "<span class=\"text\">stand alone</span>"
				     ."<img alt=\"standalone\" src='{$my_url}graphics/REDDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			if ($CORE_LOCAL->get("receiptToggle")==1){
				echo "<img id=\"receipticon\" alt=\"receipt\" src='{$my_url}graphics/receipt.gif'>&nbsp;&nbsp;&nbsp;";
			}
			else {
				echo "<img id=\"receipticon\" alt=\"no receipt\" src='{$my_url}graphics/noreceipt.gif'>&nbsp;&nbsp;&nbsp;";
			}
			if ($CORE_LOCAL->get("CCintegrate") == 1 && $CORE_LOCAL->get("training") == 0) {
			   if ($CORE_LOCAL->get("CachePanEncBlock")=="")
				   echo "<img alt=\"cc mode\" src='{$my_url}graphics/ccIn.gif'>&nbsp;";
			   else
				   echo "<img alt=\"cc available\" src='{$my_url}graphics/ccInLit.gif'>&nbsp;";
			} elseif ($CORE_LOCAL->get("CCintegrate") == 1 && $CORE_LOCAL->get("training") == 1) {
			   if ($CORE_LOCAL->get("CachePanEncBlock")=="")
				   echo "<img alt=\"cc test mode\" src='{$my_url}graphics/ccTest.gif'>&nbsp;";
			   else
				   echo "<img alt=\"cc available (test)\" src='{$my_url}graphics/ccTestLit.gif'>&nbsp;";
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
		$my_url = $this->page_url;
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
			<div class="notices coloredText">
			<?php	
			if ($CORE_LOCAL->get("training") == 1) {
				echo "<span class=\"text\">"._("training")." </span>"
				     ."<img alt=\"training\" src='{$my_url}graphics/BLUEDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			elseif ($CORE_LOCAL->get("standalone") == 0) {
				echo "<img alt=\"online\" src='{$my_url}graphics/GREENDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			else {
				echo "<span class=\"text\">stand alone</span>"
				     ."<img alt=\"standalone\" src='{$my_url}graphics/REDDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			if ($CORE_LOCAL->get("CCintegrate") == 1 && $CORE_LOCAL->get("training") == 0) {
			   if ($CORE_LOCAL->get("CachePanEncBlock")=="")
				   echo "<img alt=\"cc mode\" src='{$my_url}graphics/ccIn.gif'>&nbsp;";
			   else
				   echo "<img alt=\"cc available\" src='{$my_url}graphics/ccInLit.gif'>&nbsp;";
			} elseif ($CORE_LOCAL->get("CCintegrate") == 1 && $CORE_LOCAL->get("training") == 1) {
			   if ($CORE_LOCAL->get("CachePanEncBlock")=="")
				   echo "<img alt=\"cc test mode\" src='{$my_url}graphics/ccTest.gif'>&nbsp;";
			   else
				   echo "<img alt=\"cc available (test)\" src='{$my_url}graphics/ccTestLit.gif'>&nbsp;";
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
			<div id="scaleTop" class="coloredArea"> 
			<?php echo _("weight"); ?>
			</div>
			<div id="scaleBottom">
			<?php echo DisplayLib::scaledisplaymsg(); ?>	
			</div>
			<div id="scaleIconBox">
			<?php echo DisplayLib::drawNotifications(); ?>
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
	function scanner_scale_polling($include_scans=true)
    {
        global $CORE_LOCAL;
		if (!$include_scans) {
            return '';
        }
        $scaleDriver = $CORE_LOCAL->get("scaleDriver");
        if ($scaleDriver == '' || !class_exists($scaleDriver)) {
            return '';
        }
        $scaleObj = new $scaleDriver();
		?>
		<script type="text/javascript"
			src="<?php echo $this->page_url; ?>js/<?php echo $scaleObj->javascriptFile(); ?>">
		</script>
		<?php
		$this->add_onload_command("pollScale('".$this->page_url."');\n");
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
				if (isset($s['line']))
					echo '<li>Line '.$s['line'].', '.$s['file'];
			}
			foreach($stack as $s) echo '</ul>';
		}
		else
			header("Location: ".$url);
	}

    /**
      Callback for javascript scanner-scale polling
      This one sends scan input to a form field on the
      page and other inputs through the normal parser
    */
	function default_parsewrapper_js($input="reginput",$form="formlocal"){
	?>
    <script type="text/javascript" src="<?php echo $this->page_url; ?>js/ajax-parser.js"></script>
	<script type="text/javascript">
	function parseWrapper(str) {
        if (/^\d+$/.test(str)) {
            $('#<?php echo $input; ?>').val(str);
            $('#<?php echo $form; ?>').submit();
        } else {
            runParser(str, '<?php echo $this->page_url; ?>');
        }
	}
	</script>
	<?php
	}

    /**
      Callback for javascript scanner-scale polling
      This one ignores scan input and runs anything
      else through the parser
    */
	function noscan_parsewrapper_js() {
	?>
    <script type="text/javascript" src="<?php echo $this->page_url; ?>js/ajax-parser.js"></script>
	<script type="text/javascript">
	function parseWrapper(str) {
        if (/^\d+$/.test(str)) {
            // do nothing
        } else {
            runParser(str, '<?php echo $this->page_url; ?>');
        }
	}
	</script>
	<?php
	}
}

/**
   @example HelloWorld.php

  AutoLoader.php should be included in any top level
  scripts. If the URL in the browser address bar
  is your script, it's a top level script. No other
  includes are necessary. AutoLoader will include
  other classes as needed. 

  body_content() draws the page. Methods from BasicPage
  provide the standard input box at the top and footer
  at the bottom. DisplayLib::boxMsg() is a utility function that
  puts the 'Hello World' message in a standard message
  box.

  preprocess() handles input. In this case any form
  input causes a redirect to the main display script.

  Note the very last line creating an object. That's
  necessary to actually display anything.

*/

?>
