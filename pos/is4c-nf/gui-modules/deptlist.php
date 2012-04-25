<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!class_exists("NoInputPage")) include_once($CORE_PATH."gui-class-lib/NoInputPage.php");
if (!function_exists("pDataConnect")) include($CORE_PATH."lib/connect.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class deptlist extends NoInputPage {

	/**
	  Input processing function
	*/
	function preprocess(){
		global $CORE_LOCAL,$CORE_PATH;

		// a selection was made
		if (isset($_REQUEST['search'])){
			$entered = strtoupper($_REQUEST['search']);

			if ($entered == "" || $entered == "CL"){
				// should be empty string
				// javascript causes this input if the
				// user presses CL{enter}
				// Redirect to main screen
				$CORE_LOCAL->set("departmentAmount","0");	
				header("Location: {$CORE_PATH}gui-modules/pos2.php");
				return False;
			}

			if (is_numeric($entered)){ 
				// built department input string and set it
				// to be the next POS entry
				// Redirect to main screen
				$input = $CORE_LOCAL->get("departmentAmount")."DP".$entered."0";
				$CORE_LOCAL->set("msgrepeat",1);
				$CORE_LOCAL->set("strRemembered",$input);
				header("Location: {$CORE_PATH}gui-modules/pos2.php");
				return False;
			}
		}
		return True;
	} // END preprocess() FUNCTION

	/**
	  Pretty standard javascript for
	  catching CL typed in a select box
	*/
	function head_content(){
		global $CORE_LOCAL;
		?>
		<script type="text/javascript" >
		var prevKey = -1;
		var prevPrevKey = -1;
		function processkeypress(e) {
			var jsKey;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			if (jsKey==13) {
				if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
				(prevKey == 108 || prevKey == 76) ){ //CL<enter>
					$('#search option:selected').val('');
				}
				$('#selectform').submit();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}
		</script> 
		<?php
	} // END head() FUNCTION

	/**
	  Build a <select> form that submits
	  back to this script
	*/
	function body_content(){
		global $CORE_LOCAL;
		$db = pDataConnect();
		$q = "SELECT dept_no,dept_name FROM departments ORDER BY dept_name";
		$r = $db->query($q);

		echo "<div class=\"baseHeight\">"
			."<div class=\"listbox\">"
			."<form name=\"selectform\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}\""
			." id=\"selectform\">"
			."<select name=\"search\" id=\"search\" "
			."size=\"15\" onblur=\"\$('#search').focus();\">";

		$selected = "selected";
		while($row = $db->fetch_row($r)){
			echo "<option value='".$row["dept_no"]."' ".$selected.">";
			echo $row['dept_name'];
			echo '</option>';
			$selected = "";
		}
		echo "</select>"
			."</form>"
			."</div>"
			."<div class=\"listboxText centerOffset\">"
			."[Clear] to Cancel</div>"
			."<div class=\"clear\"></div>";
		echo "</div>";

		if (is_object($db))
			$db->close();
		$CORE_LOCAL->set("scan","noScan");
		$CORE_LOCAL->set("beep","noBeep");

		$this->add_onload_command("\$('#search').keypress(processkeypress);\n");
		$this->add_onload_command("\$('#search').focus();\n");
	} // END body_content() FUNCTION

}

new deptlist();

?>
