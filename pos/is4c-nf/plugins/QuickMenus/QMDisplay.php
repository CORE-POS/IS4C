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
		/*
		var prevKey = -1;
		var prevPrevKey = -1;
		var selectedId = 0;
		var form_disabled = 0;
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
			else if (jsKey==13 && form_disabled == 0){
				form_disabled=1;
				$('#qmform').submit();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}

		document.onkeyup = keyCheck;
		*/
		$(document).ready(function(){
			var prevKey = -1;
			var prevPrevKey = -1;
			var selectedId = 0;
			var form_disabled = 0;
			$(document).keyup(function (event){
				if (
					(event.which==108 || event.which==76)
					&&
					(prevKey==99 || prevKey==67)
				){
					// CL or cl
					// pressed clear
					$('#doClear').val('1');
				}
				if (
					(event.which==77 || event.which==109)
					&&
					(prevKey==81 || prevKey==113)
				){
					// QM or qm
					// sticky quick menu button?
					// ignore the next enter key
					form_disabled = -1;
				}
				else if (event.which==13 && form_disabled == 0){
					// enter - submit form
					form_disabled=1;
					$('#qmform').submit();
				}
				else if (event.which==13 && form_disabled == -1){
					// enter - ignore
					// but re-enable form
					form_disabled=0;
				}
				else if (event.which >= 49 && event.which <= 57 && form_disabled == 0){
					// 1-9 key - submit form
					form_disabled=1;
					$('#qmform').submit();
				}
				else if (event.which >= 97 && event.which <= 105 && form_disabled == 0){
					// 1-9 key (numpad) - submit form
					form_disabled=1;
					$('#qmform').submit();
				}
				prevPrevKey = prevKey;
				prevKey = event.which;
			});
		});

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

        /**
          Where can the menu be found?
        */
		$my_menu = array();
		if (is_array($CORE_LOCAL->get('qmNumber'))){
            /** Calling code provided the menu array via session data */
			$my_menu = $CORE_LOCAL->get('qmNumber');
		} else if (file_exists(realpath(dirname(__FILE__)."/quickmenus/".$CORE_LOCAL->get("qmNumber").".php"))) {
            /** Old way:
                Menu is defined in a PHP file
            */
			include(realpath(dirname(__FILE__)."/quickmenus/"
				.$CORE_LOCAL->get("qmNumber").".php"));
		} else {
            /** New way:
                Get menu options from QuickLookups table
            */
            $db = Database::pDataConnect();
            if ($db->table_exists('QuickLookups')) {
                $model = new QuickLookupsModel($db);
                $model->lookupSet($CORE_LOCAL->get('qmNumber'));
                foreach($model->find(array('sequence', 'label')) as $obj) {
                    $my_menu[$obj->label()] = $obj->action();
                }
            }
        }

		echo '<br /><br />';
		echo '<select name="ddQKselect" id="ddQKselect" style="width:380px;" size="10"
			onblur="$(\'#ddQKselect\').focus();" >';
		$i=1;
		foreach($my_menu as $label => $value){
			printf('<option value="%s" %s>%d. %s</option>',$value,
				($i==1?'selected':''),$i,$label);
			$i++;
		}
		echo '</select>';

		echo "<input type=\"hidden\" value=\"0\" name=\"clear\" id=\"doClear\" />";	
		echo "</form>";
		echo "</div>";
	} // END body_content() FUNCTION

}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
	new QMDisplay();

?>
