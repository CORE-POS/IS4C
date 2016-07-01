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
use COREPOS\pos\lib\FormLib;
include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class QKDisplay extends NoInputCorePage 
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
            } else if (jsKey >= 49 && jsKey <= 57){
                setSelected(jsKey-48);
            } else if (jsKey >= 97 && jsKey <= 105){
                setSelected(jsKey-96);
            } else if (jsKey == 33 || jsKey == 38){
                location = 
                    '<?php echo $this->plugin_url; ?>QKDisplay.php?offset=<?php echo ($this->offset - 1)?>';
            } else if (jsKey == 34 || jsKey == 40){
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
        $this->plugin_url = $plugin_info->pluginUrl().'/';

        $this->offset = FormLib::get('offset', 0);

        if (FormLib::get('quickkey_submit', false) !== false) {
            $output = "";
            $qstr = '';
            if (FormLib::get("clear") == 0) {
                // submit process changes line break
                // depending on platform
                // apostrophes pick up slashes
                $choice = str_replace("\r","",FormLib::get("quickkey_submit"));
                $choice = stripslashes($choice);

                $value = FormLib::get(md5($choice));

                $output = CoreLocal::get("qkInput").$value;
                $qstr = '?reginput=' . urlencode($output) . '&repeat=1';
                CoreLocal::set("currentid",CoreLocal::get("qkCurrentId"));
            }
            if (substr(strtoupper($output),0,2) == "QK"){
                CoreLocal::set("qkNumber",substr($output,2));

                return true;
            } else {
                $this->change_page($this->page_url."gui-modules/pos2.php" . $qstr);
            }
            return false;
        }
        return true;
    } // END preprocess() FUNCTION

    function body_content()
    {
        $this->add_onload_command("setSelected(7);");

        echo "<div class=\"baseHeight\">";
        echo "<form action=\"" . filter_input(INPUT_SERVER,"PHP_SELF") ."\" method=\"post\">";

        $launcher = new QuickKeysLauncher();
        $my_keys = $launcher->getKeys(CoreLocal::get('qkNumber'));

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
                        $this->pageButton('Up', $page-1);
                    }
                    if ($count == 6) {
                        $this->clearButton('qkArrowBox');
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
            $this->clearButton('qkBox');
            echo "</div>";
        }
        if ($num_pages > 1) {
            $this->pageButton('Down', $page+1);
        }
        echo "</div>";
        echo "<input type=\"hidden\" value=\"0\" name=\"clear\" id=\"doClear\" />";    
        echo "</form>";
        echo "</div>";
    } // END body_content() FUNCTION

    private function clearButton($class)
    {
        echo "<div class=\"{$class}\"><div>";
        echo '<button type="submit" class="quick_button pos-button errorColoredArea"
            onclick="$(\'#doClear\').val(1);">
            Cancel <span class="smaller">[clear]</span>
        </button></div>';
    }

    private function pageButton($title, $offset)
    {
        echo "<div class=\"qkArrowBox\">";
        echo '<button type=submit class="qkArrow pos-button coloredBorder"
            onclick="location=\'' . $this->plugin_url . 'QKDisplay.php?offset='. ($offset) . '\'; return false;">
            ' . $title . '</button>';
        echo "</div>";
    }

}

AutoLoader::dispatch();

