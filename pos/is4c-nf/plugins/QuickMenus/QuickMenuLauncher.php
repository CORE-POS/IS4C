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

class QuickMenuLauncher extends Parser {
    
    function check($str){
        if (strstr($str,"QM")){
            $tmp = explode("QM",$str);
            $ct = count($tmp);
            if ($ct <= 2 && is_numeric($tmp[$ct-1]))
                return True;
        }
        return False;
    }

    function parse($str)
    {
        $tmp = explode("QM",$str);
        if (count($tmp) == 2)
            CoreLocal::set("qmInput",$tmp[0]);
        else
            CoreLocal::set("qmInput","");
        CoreLocal::set("qmNumber",$tmp[count($tmp)-1]);
        CoreLocal::set("qmCurrentId",CoreLocal::get("currentid"));
        $ret = $this->default_json();

        $plugin_info = new QuickMenus();
        $ret['main_frame'] = $plugin_info->pluginUrl().'/QMDisplay.php';
        return $ret;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td><i>anything</i>QM<i>number</i></td>
                <td>
                Go to quick menu with the given number.
                Save any provided input.
                </td>
            </tr>
            </table>";
    }
}

