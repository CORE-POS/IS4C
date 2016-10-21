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

use COREPOS\pos\lib\gui\NoInputCorePage;
use COREPOS\pos\lib\Database;
use \COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class QMDisplay extends NoInputCorePage 
{
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
        $this->plugin_url = $plugin_info->pluginUrl().'/';

        $this->offset = FormLib::get('offset', 0);

        if (count($_POST) > 0){
            $output = "";
            $qstr = '';
            if ($_REQUEST["clear"] == 0) {
                $value = FormLib::get('ddQKselect');

                if ($value !== '') {
                    $output = CoreLocal::get("qmInput").$value;
                    $qstr = '?reginput=' . urlencode($output) . '&repeat=1';
                    CoreLocal::set("currentid",CoreLocal::get("qmCurrentId"));
                }
                if (!FormLib::validateToken() && is_numeric($value)) {
                    CoreLocal::set("msgrepeat",0);
                }
            }
            if (substr(strtoupper($output),0,2) == "QM"){
                CoreLocal::set("qmNumber",substr($output,2));
                return True;
            } else {
                $this->change_page($this->page_url."gui-modules/pos2.php" . $qstr);
            }
            return False;
        }
        return True;
    } // END preprocess() FUNCTION

    private function getMenu()
    {
        $my_menu = array();
        if (is_array(CoreLocal::get('qmNumber'))){
            /** Calling code provided the menu array via session data */
            $my_menu = CoreLocal::get('qmNumber');
        } else {
            /** New way:
                Get menu options from QuickLookups table
            */
            $dbc = Database::pDataConnect();
            if (CoreLocal::get('NoCompat') == 1 || $dbc->table_exists('QuickLookups')) {
                $model = new COREPOS\pos\plugins\QuickMenus\QuickLookupsModel($dbc);
                $model->lookupSet(CoreLocal::get('qmNumber'));
                foreach($model->find(array('sequence', 'label')) as $obj) {
                    $my_menu[$obj->label()] = $obj->action();
                }
            }
            if (count($my_menu) == 0 && file_exists(realpath(dirname(__FILE__)."/quickmenus/".CoreLocal::get("qmNumber").".php"))) {
                /** Old way:
                    Menu is defined in a PHP file
                */
                include(realpath(dirname(__FILE__)."/quickmenus/"
                    .CoreLocal::get("qmNumber").".php"));
            }
        }

        return $my_menu;
    }

    function body_content()
    {
        $this->add_onload_command("selectSubmit('#ddQKselect', '#qmform');\n");
        $this->add_onload_command('$(\'#ddQKselect\').focus()');

        echo "<div class=\"baseHeight\" style=\"border: solid 1px black;\">";
        echo "<form id=\"qmform\" action=\"" . filter_input(INPUT_SERVER, "PHP_SELF") ."\" 
            method=\"post\" onsubmit=\"return false;\">";

        $my_menu = $this->getMenu();

        echo '<br /><br />';
        echo '<select name="ddQKselect" id="ddQKselect" style="width:380px;" size="10"
            onblur="$(\'#ddQKselect\').focus();" >';
        $count=1;
        foreach ($my_menu as $label => $value) {
            printf('<option value="%s" %s>%d. %s</option>',$value,
                ($count==1?'selected':''),$count,$label);
            $count++;
        }
        echo '</select>';
        $this->add_onload_command("qmNumberPress();\n");

        echo "<input type=\"hidden\" value=\"0\" name=\"clear\" id=\"doClear\" />";    
        echo FormLib::tokenField();
        echo "</form>";
        echo "</div>";
    } // END body_content() FUNCTION
}

AutoLoader::dispatch();

