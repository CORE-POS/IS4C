<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\parser\Parser;

class QuickKeyLauncher extends Parser 
{

    private $mode = 'page';

    function check($str)
    {
        $tmp = false;
        if (strstr($str,"QK")) {
            $tmp = explode("QK",$str);
        } elseif (strstr($str,"QO")) {
            $tmp = explode("QO",$str);
            $this->mode = 'overlay';
        }

        $len = count($tmp);
        if ($tmp && $len <= 2 && is_numeric($tmp[$len-1])) {
            return true;
        } else {
            return false;
        }
    }

    function parse($str)
    {
        $ret = $this->default_json();
        if ($this->mode == 'page') {
            $tmp = explode("QK",$str);
            if (count($tmp) == 2) {
                CoreLocal::set("qkInput",$tmp[0]);
            } else {
                CoreLocal::set("qkInput","");
            }
            CoreLocal::set("qkNumber",$tmp[count($tmp)-1]);
            CoreLocal::set("qkCurrentId",CoreLocal::get("currentid"));

            $plugin_info = new QuickKeys();
            $ret['main_frame'] = $plugin_info->pluginUrl().'/QKDisplay.php';
        } else {
            $tmp = explode('QO', $str);
            $num = $tmp[1]; 
            $ret['output'] = $this->overlayKeys($num, $tmp[0]);
        }

        return $ret;
    }

    public function getKeys($number)
    {
        $dbc = Database::pDataConnect();
        $my_keys = array();
        if (CoreLocal::get('NoCompat') == 1 || $dbc->table_exists('QuickLookups')) {
            $prep = $dbc->prepare('
                SELECT label,
                    action
                FROM QuickLookups
                WHERE lookupSet = ?
                ORDER BY sequence');
            $res = $dbc->execute($prep, array($number));
            while ($row = $dbc->fetch_row($res)) {
                $my_keys[] = new quickkey($row['label'], $row['action']);
            }
        }
        if (count($my_keys) == 0) {
            include(dirname(__FILE__) . '/quickkeys/noauto/' . $number . '.php');
        }

        return $my_keys;
    }

    private function overlayKeys($number, $preInput)
    {
        $my_keys = $this->getKeys($number);
        if (count($my_keys) == 0) {
            return DisplayLib::boxMsg('Menu not found', '', false, DisplayLib::standardClearButton());
        }

        $clearButton = false;
        $ret = '';
        for ($i=0; $i<count($my_keys); $i++) {
            if ($i % 3 == 0) {
                if ($i != 0) {
                   $ret .= ' </div>';
                }
                $ret .= '<div class="qkRow">';
            }
            $ret .= sprintf('
                <div class="qkBox">
                    <div id="qkDiv%d">
                        <button type="button" class="quick_button pos-button coloredBorder"
                            onclick="$(\'#reginput\').val($(\'#reginput\').val()+\'%s\');pos2.submitWrapper();">
                        %s
                        </button>
                    </div>
                </div>',
                $i, $preInput . $my_keys[$i]->output_text, $my_keys[$i]->title);
        }
        if (!$clearButton) {
            $ret .= '<div class="qkBox">
                <div>
                    <button type="button" class="quick_button pos-button errorColoredArea"
                        onclick="$(\'#reginput\').val(\'CL\');pos2.submitWrapper();">
                        Clear <span class="smaller">[clear]</span>
                    </button>
                </div>
                </div>';
        }
        $ret .= '</div>';

        return $ret;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td><i>anything</i>QK<i>number</i></td>
                <td>
                Go to quick key with the given number.
                Save any provided input.
                </td>
            </tr>
            </table>";
    }
}

