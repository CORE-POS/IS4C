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

ini_set('display_errors','1');

include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class QMDisplay extends NoInputPage {

	var $offset;

	function head_content(){
		?>
		<script type="text/javascript" >
		var prevKey = -1;
		var prevPrevKey = -1;
		var selectedId = 0;
		function keyCheck(e) {
			var jsKey;
			if(!e)e = window.event;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			// Options:
			// 1: Clear - go back to pos2 w/o selecting anything
			// (Paging wraps)
			if ( (jsKey==108 || jsKey == 76) && 
			(prevKey == 99 || prevKey == 67) ){
				document.getElementById('doClear').value='1';
			}
			else if (jsKey==13){
				$('#qmform').submit();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}

		document.onkeyup = keyCheck;

		</script> 
		<?php
	} // END head() FUNCTION

	var $plugin_url;
	function preprocess(){
		global $CORE_LOCAL;
		$plugin_info = new QuickMenus();
		$this->plugin_url = $plugin_info->plugin_url().'/';

		$this->offset = isset($_REQUEST['offset'])?$_REQUEST['offset']:0;

		if (count($_POST) > 0){
			var_dump($_POST);
			$output = "";
			if ($_REQUEST["clear"] == 0){
				$value = $_REQUEST['ddQKselect'];

				$output = $CORE_LOCAL->get("qmInput").$value;
				$CORE_LOCAL->set("msgrepeat",1);
				$CORE_LOCAL->set("strRemembered",$output);
				$CORE_LOCAL->set("currentid",$CORE_LOCAL->get("qmCurrentId"));
			}
			if (substr(strtoupper($output),0,2) == "QM"){
				$CORE_LOCAL->set("qmNumber",substr($output,2));
				return True;
			}
			else {
				$this->change_page($this->page_url."gui-modules/pos2.php");
			}
			return False;
		}
		return True;
	} // END preprocess() FUNCTION

	function body_content(){
		global $CORE_LOCAL;

		$this->add_onload_command('$(\'#ddQKselect\').focus()');

		echo "<div class=\"baseHeight\" style=\"border: solid 1px black;\">";
		echo "<form id=\"qmform\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";

		include(realpath(dirname(__FILE__)."/quickmenus/"
			.$CORE_LOCAL->get("qmNumber").".php"));

		echo '<br /><br />';
		echo '<select name="ddQKselect" id="ddQKselect" style="width:200px;" size="10"
			onblur="$(\'#ddQKselect\').focus();" >';
		$i=1;
		foreach($my_menu as $label => $value){
			printf('<option value="%s" %s>%d. %s</option>',$value,
				($i==1?'selected':''),$i,$label);
			$i++;
		}
		echo '</select>';

		echo "</div>";
		echo "<input type=\"hidden\" value=\"0\" name=\"clear\" id=\"doClear\" />";	
		echo "</form>";
		echo "</div>";
		$CORE_LOCAL->set("scan","noScan");
	} // END body_content() FUNCTION

}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
	new QMDisplay();

?>
