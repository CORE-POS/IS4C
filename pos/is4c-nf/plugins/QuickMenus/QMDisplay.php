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

class QMDisplay extends NoInputPage {

	private $offset;
	private $plugin_url;

	function head_content()
    {
        $base = MiscLib::baseURL();
		?>
        <script type="text/javascript" src="<?php echo $base; ?>js/selectSubmit.js"></script>
        <script type="text/javascript">
        function qmNumberPress()
        {
            var qm_submitted = false;
            $('#ddQKselect').keyup(function(event) {
                if (event.which >= 49 && event.which <= 57) {
                    if (!qm_submitted) {
                        qm_submitted = true;
                        $('#qmform').submit();
                    }
                } else if (event.which >= 97 && event.which <= 105) {
                    if (!qm_submitted) {
                        qm_submitted = true;
                        $('#qmform').submit();
                    }
                }
            });
        }
        </script>
		<?php
	} // END head() FUNCTION

	function preprocess()
    {
		$plugin_info = new QuickMenus();
		$this->plugin_url = $plugin_info->plugin_url().'/';

		$this->offset = isset($_REQUEST['offset'])?$_REQUEST['offset']:0;

		if (count($_POST) > 0){
            if (!FormLib::validateToken()) {
                CoreLocal::set('msgrepeat', 0);
				$this->change_page($this->page_url."gui-modules/pos2.php");

                return false;
            }
			$output = "";
			if ($_REQUEST["clear"] == 0){
				$value = $_REQUEST['ddQKselect'];

				$output = CoreLocal::get("qmInput").$value;
				CoreLocal::set("msgrepeat",1);
				CoreLocal::set("strRemembered",$output);
				CoreLocal::set("currentid",CoreLocal::get("qmCurrentId"));
			}
			if (substr(strtoupper($output),0,2) == "QM"){
				CoreLocal::set("qmNumber",substr($output,2));
				return True;
			} else {
				$this->change_page($this->page_url."gui-modules/pos2.php");
			}
			return False;
		}
		return True;
	} // END preprocess() FUNCTION

	function body_content()
    {
		$this->add_onload_command('$(\'#ddQKselect\').focus()');
        $this->add_onload_command("selectSubmit('#ddQKselect', '#qmform');\n");

		echo "<div class=\"baseHeight\" style=\"border: solid 1px black;\">";
		echo "<form id=\"qmform\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";

        /**
          Where can the menu be found?
        */
		$my_menu = array();
		if (is_array(CoreLocal::get('qmNumber'))){
            /** Calling code provided the menu array via session data */
			$my_menu = CoreLocal::get('qmNumber');
		} else if (file_exists(realpath(dirname(__FILE__)."/quickmenus/".CoreLocal::get("qmNumber").".php"))) {
            /** Old way:
                Menu is defined in a PHP file
            */
			include(realpath(dirname(__FILE__)."/quickmenus/"
				.CoreLocal::get("qmNumber").".php"));
		} else {
            /** New way:
                Get menu options from QuickLookups table
            */
            $db = Database::pDataConnect();
            if ($db->table_exists('QuickLookups')) {
                $model = new QuickLookupsModel($db);
                $model->lookupSet(CoreLocal::get('qmNumber'));
                foreach($model->find(array('sequence', 'label')) as $obj) {
                    $my_menu[$obj->label()] = $obj->action();
                }
            }
        }

		echo '<br /><br />';
		echo '<select name="ddQKselect" id="ddQKselect" style="width:380px;" size="10"
			onblur="$(\'#ddQKselect\').focus();" >';
		$i=1;
		foreach ($my_menu as $label => $value) {
			printf('<option value="%s" %s>%d. %s</option>',$value,
				($i==1?'selected':''),$i,$label);
			$i++;
		}
		echo '</select>';
        $this->add_onload_command("qmNumberPress();\n");

		echo "<input type=\"hidden\" value=\"0\" name=\"clear\" id=\"doClear\" />";	
        echo FormLib::tokenField();
		echo "</form>";
		echo "</div>";
	} // END body_content() FUNCTION

}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
	new QMDisplay();

?>
