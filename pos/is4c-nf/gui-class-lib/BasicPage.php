<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* BasicPage
 *
 * This is the base class for all display scripts
 * When instantiated, it calls the following functions
 * in this order:
 *
 * preprocess()
 * if preprocess() returns True 
 *   head()
 *   body_tag()
 *   body_content()
 *
 * Any of these functions may be overriden by subclasses
 */

if (!function_exists('scaledisplaymsg')) include($_SERVER['DOCUMENT_ROOT'].'/lib/drawscreen.php');

class BasicPage {

	var $onload_commands;

	function BasicPage(){
		$this->onload_commands = "";
		if ($this->preprocess()){
			ob_start();
			$this->print_page();
			while (ob_get_level() > 0)
				ob_end_flush();
		}
	}

	function head_content(){

	}

	function body_content(){

	}

	function preprocess(){
		return True;
	}

	function print_page(){
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html>
		<?php
		echo "<head>";
		echo "<link rel=\"stylesheet\" type=\"text/css\"
		    href=\"/is4c.css\">";
		echo "<script type=\"text/javascript\"
			src=\"/js/jquery.js\"></script>";
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

	function add_onload_command($str){
		$this->onload_commands .= $str."\n";
	}

	function input_header($action=""){
		global $IS4C_LOCAL;
		if (empty($action))
			$action = "action=\"".$_SERVER['PHP_SELF']."\"";

		$this->add_onload_command("betterDate();\n\$('#reginput').focus();");
		
		$inputType = "text";
		if ($IS4C_LOCAL->get("inputMasked") != 0)
			$inputType = "password";
		$time = strftime("%m/%d/%y %I:%M %p", time());

		$IS4C_LOCAL->set("repeatable",0);
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
			<div class="inputform <?php echo ($IS4C_LOCAL->get("training")==1?'training':''); ?>">
				<form name="form" id="formlocal" method="post" autocomplete="off"
					<?php echo $action; ?> >
				<input name="reginput" value="" onblur="$('#reginput').focus();"
					type="<?php echo $inputType; ?>" id="reginput"  />
				</form>
			</div>
			<div class="notices <?php echo ($IS4C_LOCAL->get("training")==1?'training':''); ?>">
			<?php
			if ($IS4C_LOCAL->get("training") == 1) {
				echo "<span class=\"text\">training </span>"
				     ."<img src='/graphics/BLUEDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			elseif ($IS4C_LOCAL->get("standalone") == 0) {
				echo "<img src='/graphics/GREENDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			else {
				echo "<span class=\"text\">stand alone</span>"
				     ."<img src='/graphics/REDDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			if($IS4C_LOCAL->get("CCintegrate") == 1 && 
				$IS4C_LOCAL->get("ccLive") == 1 && $IS4C_LOCAL->get("training") == 0){
			   echo "<img src='/graphics/ccIn.gif'>&nbsp;";
			}elseif($IS4C_LOCAL->get("CCintegrate") == 1 && 
				($IS4C_LOCAL->get("training") == 1 || $IS4C_LOCAL->get("ccLive") == 0)){
			   echo "<img src='/graphics/ccTest.gif'>&nbsp;";
			}

			echo "<span id=\"timeSpan\" class=\"time\">".$time."</span>\n";
			if ($IS4C_LOCAL->get("prefix") != ""){
				$this->add_onload_command("\$('#reginput').val('"
					.$IS4C_LOCAL->get("prefix")."');\n");
				$IS4C_LOCAL->set("prefix","");
			}
			?>

			</div>
		</div>
		<div id="inputAreaEnd"></div>
		<?php
	}

	function noinput_header(){
		global $IS4C_LOCAL;
		$this->add_onload_command("betterDate();\n");
		
		$time = strftime("%m/%d/%y %I:%M %p", time());

		$IS4C_LOCAL->set("repeatable",0);
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
			if ($IS4C_LOCAL->get("training") == 1) {
				echo "<span class=\"text\">training </span>"
				     ."<img src='/graphics/BLUEDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			elseif ($IS4C_LOCAL->get("standalone") == 0) {
				echo "<img src='/graphics/GREENDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			else {
				echo "<span class=\"text\">stand alone</span>"
				     ."<img src='/graphics/REDDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			if($IS4C_LOCAL->get("CCintegrate") == 1 && 
				$IS4C_LOCAL->get("ccLive") == 1 && $IS4C_LOCAL->get("training") == 0){
			   echo "<img src='/graphics/ccIn.gif'>&nbsp;";
			}elseif($IS4C_LOCAL->get("CCintegrate") == 1 && 
				($IS4C_LOCAL->get("training") == 1 || $IS4C_LOCAL->get("ccLive") == 0)){
			   echo "<img src='/graphics/ccTest.gif'>&nbsp;";
			}

			echo "<span id=\"timeSpan\" class=\"time\">".$time."</span>\n";
			?>

			</div>
		</div>
		<div id="inputAreaEnd"></div>
		<?php
	}

	function scale_box(){
		?>
		<div id="scalebox">
			<div id="scaleTop"> 
			weight
			</div>
			<div id="scaleBottom">
			<?php echo scaledisplaymsg(); ?>	
			</div>
		</div>
		<?php
	}

	function scanner_scale_polling($include_scans=True){
		?>
		<script type="text/javascript"
			src="/js/poll-scale.js">
		</script>
		<?php
		if ($include_scans)
			$this->add_onload_command("pollScale(true);\n");
		else
			$this->add_onload_command("pollScale(false);\n");
	}
}

?>
