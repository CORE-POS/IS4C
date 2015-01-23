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

include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class QKDisplay extends NoInputPage 
{
	private $offset;
	private $plugin_url;

	function head_content()
    {
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
			// 2: Select button corresponding to 1-9
			// 3: Page Up - go to previous page of buttons
			// 4: Page Down - go to next page of buttons
			// (Paging wraps)
			if ( (jsKey==108 || jsKey == 76) && 
			(prevKey == 99 || prevKey == 67) ){
				document.getElementById('doClear').value='1';
                document.forms[0].submit();
			}
			else if (jsKey >= 49 && jsKey <= 57){
				setSelected(jsKey-48);
			}
			else if (jsKey >= 97 && jsKey <= 105){
				setSelected(jsKey-96);
			}
			else if (jsKey == 33 || jsKey == 38){
				location = 
					'<?php echo $this->plugin_url; ?>QKDisplay.php?offset=<?php echo ($this->offset - 1)?>';
			}
			else if (jsKey == 34 || jsKey == 40){
				location = 
					'<?php echo $this->plugin_url; ?>QKDisplay.php?offset=<?php echo ($this->offset + 1)?>';
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
            console.log(jsKey);
		}

		document.onkeyup = keyCheck;

		function setSelected(num){
			var row = Math.floor((num-1) / 3);
			var id = 0;
			if (row == 2) id = num - 7;
			else if (row == 1) id = num - 1;
			else if (row == 0) id = num + 5;
			if ($('#qkDiv'+id)){
				$('#qkButton'+id).focus();
				selectedId = id;
                $('.quick_button').removeClass('coloredArea');
                $('#qkDiv'+id+' .quick_button').addClass('coloredArea');
			}
		}
		</script> 
		<?php
	} // END head() FUNCTION

	function preprocess()
    {
		$plugin_info = new QuickKeys();
		$this->plugin_url = $plugin_info->plugin_url().'/';

		$this->offset = isset($_REQUEST['offset'])?$_REQUEST['offset']:0;

		if (count($_POST) > 0){
			$output = "";
			if ($_REQUEST["clear"] == 0){
				// submit process changes line break
				// depending on platform
				// apostrophes pick up slashes
				$choice = str_replace("\r","",$_REQUEST["quickkey_submit"]);
				$choice = stripslashes($choice);

				$value = $_REQUEST[md5($choice)];

				$output = CoreLocal::get("qkInput").$value;
				CoreLocal::set("msgrepeat",1);
				CoreLocal::set("strRemembered",$output);
				CoreLocal::set("currentid",CoreLocal::get("qkCurrentId"));
			}
			if (substr(strtoupper($output),0,2) == "QK"){
				CoreLocal::set("qkNumber",substr($output,2));

				return true;
			} else {
				$this->change_page($this->page_url."gui-modules/pos2.php");
			}
			return False;
		}
		return True;
	} // END preprocess() FUNCTION

	function body_content()
    {
		$this->add_onload_command("setSelected(7);");

		echo "<div class=\"baseHeight\">";
		echo "<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";

        $db = Database::pDataConnect();
        $my_keys = array();
        /**
          First search for entries in the QuickLookups table
        */
        if ($db->table_exists('QuickLookups')) {
            $prep = $db->prepare('
                SELECT label,
                    action
                FROM QuickLookups
                WHERE lookupSet = ?
                ORDER BY sequence');
            $args = array(CoreLocal::get('qkNumber'));
            $res = $db->execute($prep, $args);
            while ($row = $db->fetch_row($res)) {
                $my_keys[] = new quickkey($row['label'], $row['action']);
            }
        }

        /**
          If none are found, then fall back to including numbered files
        */
        if (count($my_keys) == 0) {
            include(realpath(dirname(__FILE__)."/quickkeys/keys/"
                .CoreLocal::get("qkNumber").".php"));
        }

		$num_pages = ceil(count($my_keys)/9.0);
		$page = $this->offset % $num_pages;
		if ($page < 0) $page = $num_pages + $page;

		$count = 0;
        $clearButton = false;
		for ($i=$page*9; $i < count($my_keys); $i++) {
			$key = $my_keys[$i];
			if ($count % 3 == 0) {
				if ($count != 0) {
					if ($num_pages > 1 && $count == 3){
						echo "<div class=\"qkArrowBox\">";
                        echo '<button type=submit class="qkArrow pos-button coloredBorder"
                            onclick="location=\'' . $this->plugin_url . 'QKDisplay.php?offset='. ($page-1) . '\'; return false;">
                            Up</button>';
						echo "</div>";
					}
                    if ($count == 6) {
						echo "<div class=\"qkArrowBox\">";
                        echo '<button type="submit" class="pos-button errorColoredArea"
                            onclick="$(\'#doClear\').val(1);">
                            Cancel <span class="smaller">[clear]</span>
                        </button>';
						echo "</div>";
                        $clearButton = true;
                    }
					echo "</div>";
                }
				echo "<div class=\"qkRow\">";
			}
			echo "<div class=\"qkBox\"><div id=\"qkDiv$count\">";
			echo $key->display("qkButton$count");
			echo "</div></div>";
			$count++;
			if ($count > 8) break;
		}
        if (!$clearButton) {
            echo "<div class=\"qkBox\"><div>";
            echo '<button type="submit" class="quick_button pos-button errorColoredArea"
                onclick="$(\'#doClear\').val(1);">
                Cancel <span class="smaller">[clear]</span>
            </button>';
            echo "</div></div>";
        }
		if ($num_pages > 1) {
			echo "<div class=\"qkArrowBox\">";
			echo '<button type=submit class="qkArrow pos-button coloredBorder"
				onclick="location=\'' . $this->plugin_url . 'QKDisplay.php?offset='. ($page+1) . '\'; return false;">
                Down</button>';
			echo "</div>";

		}
		echo "</div>";
		echo "<input type=\"hidden\" value=\"0\" name=\"clear\" id=\"doClear\" />";	
		echo "</form>";
		echo "</div>";
	} // END body_content() FUNCTION

}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
	new QKDisplay();

?>
