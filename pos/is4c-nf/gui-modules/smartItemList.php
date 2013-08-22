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
if (!class_exists("MainFramePage")) include_once($_SESSION["INCLUDE_PATH"]."/gui-class-lib/MainFramePage.php");
if (!function_exists("tDataConnect")) include($_SESSION["INCLUDE_PATH"]."/lib/connect.php");
if (!function_exists("setMember")) include($_SESSION["INCLUDE_PATH"]."/lib/prehkeys.php");
if (!function_exists("changeBothPages")) include($_SESSION["INCLUDE_PATH"]."/gui-base.php");
if (!isset($CORE_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class SmartItemList extends MainFramePage {

	var $temp_result;
	var $entered;
	var $db;

	function preprocess(){
		global $CORE_LOCAL;
		if (isset($_POST['selectlist'])){
			$val = $_POST['selectlist'];
			if ($val != 'CL'){
				$CORE_LOCAL->set('strRemembered',$val);
				$CORE_LOCAL->set('msgrepeat',1);
			}
			changeBothPages('/gui-modules/input.php','/gui-modules/pos2.php');
			return False;
		}
		return True;
	} // END preprocess() FUNCTION

	function body_tag() {
		echo "<body onLoad='preloadopts(); document.selectform.selectlist.focus()'>";
	}

	function head(){
		global $CORE_LOCAL;
		// Javascript is only needed if there are results
		?>
		<script type="text/javascript" >
		var prevKey = -1;
		var prevPrevKey = -1;
		var entered = '';
		function processkeypress(e) {
			var jsKey;
			if(!e)e = window.event;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			if (jsKey==13) {
				if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
				(prevKey == 108 || prevKey == 76) ){ //CL<enter>
					try {
						document.selectform.selectlist[0].value = 'CL';
					}
					catch(ex){
						var o = document.createElement('option');
						o.text = 'CL';
						o.value = 'CL';
						safeAdd(document.selectform.selectlist, o);	
					}
					document.selectform.selectlist.selectedIndex = 0;
				}
				document.selectform.submit();
			}
			else {
				if (jsKey == 33 || jsKey == 34 || jsKey == 38 || jsKey == 40){
					// up, down, page up, page down
					return true;
				}

				var s = document.getElementById('selectlist');
				for(var i=s.options.length-1; i>=0; i--)
					s.remove(i);

				if (jsKey == 8){
					for (var i=0; i<allopts.length; i++){
						safeAdd(s, allopts[i]);
					}
					entered = '';
					s.selectedIndex = 0;
					return false;
				}

				entered += String.fromCharCode(jsKey).toUpperCase();
				var index = -1;
				var j = 0;
				for (var i=0; i<allopts.length; i++){
					if (allopts[i].text.toUpperCase().indexOf(entered) == 0){
						safeAdd(s,allopts[i]);
						index = 0;
					}
				}
				if (index != -1){
					s.selectedIndex = index;
				}
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
			return true;
		}

		var isIE = /*@cc_on!@*/false;
		if (isIE)
			document.onkeydown = processkeypress;
		else
			document.onkeypress = processkeypress;

		var allopts = new Array();
		function preloadopts(){
			var s = document.getElementById('selectlist');
			for(var i=0; i<s.options.length; i++){
				allopts[i] = s.options[i];
			}	
		}

		function safeAdd(selector, opt){
			try {
				selector.add(opt,null);
			}
			catch (ex){
				selector.add(opt);
			}
		}
		</script> 
		<?php
	} // END head() FUNCTION

	function body_content(){
		global $CORE_LOCAL;

		$db = pDataConnect();
		$itemsQ = "select upc,description from products order by description";
		$result = $db->query($itemsQ);

		echo "<div class=\"baseHeight\">"
			."<div class=\"listbox\">"
			."<form name='selectform' method='post' action='".$_SERVER["PHP_SELF"]."'>"
			."<select id='selectlist' name='selectlist' size='15' "
			."onBlur='document.selectform.selectlist.focus()'>";

		$selectFlag = 0;
		for ($i = 0; $i < $db->num_rows($result); $i++) {
			$row = $db->fetch_array($result);
			if( $i == 0 && $selectFlag == 0) {
				$selected = "selected";
			} else {
				$selected = "";
			}
			echo "<option value='".$row["upc"]."' ".$selected.">"
				.$row["description"]."</option>";
		}
		echo "</select></form></div>"
			."<div class=\"centerOffset listboxText coloredText\">"
			."use arrow keys to navigate<p>[esc] to cancel</div>"
			."<div class=\"clear\"></div>";
		echo "</div>";
	} // END body_content() FUNCTION
}

new SmartItemList();

?>
