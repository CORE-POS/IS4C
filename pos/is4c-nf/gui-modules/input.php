<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

if (!class_exists("BasicPage")) include_once($_SESSION["INCLUDE_PATH"]."/gui-class-lib/BasicPage.php");
if (!isset($CORE_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class Input extends BasicPage {

	function body_tag(){
		$val = '';
		if (isset($_GET["in"])) $val = $_GET["in"];
		echo "<body onload=\"betterDate(); document.form.reginput.focus(); document.form.reginput.value='$val';\">";
	}

	function head(){
		global $CORE_LOCAL;
		?>
		<script type="text/javascript">

		function keyDown(e)
		{
			if ( ! e ) {
				e = event;
			}
			var ieKey=e.keyCode;
			switch(ieKey) {
			case 37:
			case 38:
				submitMainForm('U');
				break;
			case 39:
			case 40:
				submitMainForm('D');
				break;
			case 115:
				submitMainForm('LOCK');
				break;
			case 117:
				submitMainForm('RI');
				break;
			case 121:
				submitMainForm('WAKEUP');
				break;
			default:
				break;
			}
		}

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

		function submitMainForm(str){
			/* this can be simplified eventually, but right now
			 * there's a fallback in case the other frame's form
			 * doesn't have an id 
			 */
			if($('#form1 input',window.top.main_frame.document).length != 0){
				$('#form1 input',window.top.main_frame.document).val(str);
				window.top.main_frame.submitWrapper();
			}
			else{
				$('form:first input',window.top.main_frame.document).val(str);
				$('form:first',window.top.main_frame.document).submit();
			}

			$('#formlocal input').val('');
			$('#formlocal input').focus();
		}

		$(document).ready(function() {
			$('#formlocal').submit(function() {
				submitMainForm($('#formlocal input').val());
				return false;
			});

			$(document).keydown(keyDown);
		});
		</script>

		
		<?php
	} // END head() function

	function body_content(){
		global $CORE_LOCAL;
		if ($this->mask_input){
			$inputType = "password";
		} else {
			$inputType = "text";
		}
		/*
		if (isset($_POST["reginput"])) {
			$input = strtoupper(trim($_POST["reginput"]));
		} else {
			$input = "";
		}
		 */
		$time = strftime("%m/%d/%y %I:%M %p", time());

		$CORE_LOCAL->set("repeatable",0);
		?>
		<div id="inputArea">
			<div class="inputform">
				<form name="form" id="formlocal" method="post" autocomplete="off"
					action="<?php echo $_SERVER['PHP_SELF']; ?>">
				<input name="reginput" value="" onBlur="document.forms[0].reginput.focus();"
					type="<?php echo $inputType; ?>" />
				</form>
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

			echo "<span id=timeSpan class=\"time\">".$time."</span>\n";
			?>

			</div>
		</div>
		<?php
		/*
		if ( strlen($input) > 0 || $CORE_LOCAL->get("msgrepeat") == 2) {
			echo "<script type=\"text/javascript\">";
			echo "if (top.main_frame.document.forms[0] &&
				top.main_frame.document.forms[0].input){";
			echo "top.main_frame.document.forms[0].input.value = '".$input."';\n";
			echo "top.main_frame.document.forms[0].submit();\n";
			echo "}";
			echo "</script>";
		}
		 */
	} // END body_content() FUNCTION
}

new Input();
?>
