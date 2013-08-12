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

class undo_input extends BasicPage {

	function body_tag(){
		echo "<body onload=\"document.form.reginput.focus()\">";
	}

	function head(){
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
				{
					window.top.main_frame.document.form1.input.value = 'U';
					window.top.main_frame.document.form1.submit();
				};
				break;
			case 38:
				{
					window.top.main_frame.document.form1.input.value = 'U';
					window.top.main_frame.document.form1.submit();
				};
				break;
			case 39:
				{
					window.top.main_frame.document.form1.input.value = 'D';
					window.top.main_frame.document.form1.submit();
				};
				break;
			case 40:
				{
					window.top.main_frame.document.form1.input.value = 'D';
					window.top.main_frame.document.form1.submit();
				};
				break;
			default:
				break;
			}
		}

		document.onkeydown = keyDown;

		// filter all form input
		// only acceptable inputs are:
		// 	clear or cancel to cancel the transaction
		// 	enter to accept the transaction
		// all other inputs are erased
		function inputCheck(){
			var input = document.getElementById('reginput').value.toUpperCase();
			if (input == "CL" || input == "CN"){
				window.top.main_frame.document.form1.input.value = 'CN';
				window.top.main_frame.document.form1.submit();
			}
			else if (input == ""){
				window.top.main_frame.document.form1.input.value = '0CA';
				window.top.main_frame.document.form1.submit();
				window.location = '/gui-modules/input.php';
			}
			else {
				document.getElementById('reginput').value = "";
			}
		}
		</script>
		<?php
	} // END head() function

	function body_content(){
		global $CORE_LOCAL;
		// this *should* be true anyway, but it's here so canceling won't
		// require authentication (and leave this filtered input frame)
		$CORE_LOCAL->set("runningTotal",0);

		echo "<div id=\"inputArea\">";
		echo "<div class=\"inputform\">";
		echo "<form name='form' method='post' autocomplete='off' onsubmit=\"inputCheck();return false;\">";
		if ($this->mask_input){
			$inputType = "password";
		} else {
			$inputType = "text";
		}
		echo "<input name='reginput' id=reginput type=".$inputType." value='' onBlur='document.forms[0].reginput.focus();'>";
		echo "</form></div>";
		echo "<div class=\"notices\">";
		echo "<b>[Enter] to accept, [Clear] to reject</b>&nbsp;&nbsp;&nbsp;&nbsp;";
		if (isset($_POST["reginput"])) {
			$input = strtoupper(trim($_POST["reginput"]));
		} else {
			$input = "";
		}

		$time = strftime("%m/%d/%y %I:%M %p", time());

		$CORE_LOCAL->set("repeatable",0);

		if ($CORE_LOCAL->get("training") == 1) {
			echo "<span class=\"text\">training </span>"
			     ."<img src='/graphics/BLUEDOT.GIF'>&nbsp;&nbsp:&nbsp;";
		}
		elseif ($CORE_LOCAL->get("standalone") == 0) {
			echo "<img src='/graphics/GREENDOT.GIF'>&nbsp;&nbsp;&nbsp;";
		}
		else {
			echo "<span class=\"text\">stand alone</span>"
			     ."<img src='/graphics/REDDOT.GIF'>&nbsp;&nbsp;&nbsp;";
		}

		echo "<span class=\"time\">".$time."</span>\n";

		if ( strlen($input) > 0 || $CORE_LOCAL->get("msgrepeat") == 2) {
			echo "<script type=\"text/javascript\">";
			echo "top.main_frame.document.forms[0].input.value = '".$input."';\n";
			echo "top.main_frame.document.forms[0].submit();\n";
			echo "</script>";
		}
	} // END body_content() FUNCTION
}

new undo_input();
?>
