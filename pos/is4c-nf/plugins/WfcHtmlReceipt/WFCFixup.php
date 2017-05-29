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

use COREPOS\pos\parser\PreParser;

class WFCFixup extends PreParser {
    var $remainder;
    
    function check($str)
    {
        if ($this->session->get('store') !== 'wfc') {
            return false;
        }
        $as_upc = str_pad($str, 13, '0', STR_PAD_LEFT);
        if (substr($str,-3) == "QK9"){
            $this->remainder = str_replace("QK9","QM9",$str);
            return True;
        } else if (substr($str,-4) == "QK10"){
            $this->remainder = str_replace("QK10","QM10",$str);
            return True;
        } else if (($as_upc == '0000000001112' || $as_upc == '0000000001113') && $this->session->get('msgrepeat') == 0) {
            $this->remainder = 'QM708';
            return true;
        } elseif (preg_match('/(\d+)\*0*1112/', $str, $matches) && $this->session->get('msgrepeat') == 0) {
            $this->remainder = $matches[1] . '*QM708';
            return true;
        } elseif (preg_match('/(\d+)\*0*1113/', $str, $matches) && $this->session->get('msgrepeat') == 0) {
            $this->remainder = $matches[1] . '*QM708';
            return true;
        } elseif ($as_upc == '0049999900047') {
            $this->remainder = '0049999900048';
            return true;
        } elseif ($str == 'FS') {
            $this->remainder = 'WIC';
            return true;
        }
        return False;
    }

    function parse($str){
        return $this->remainder;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td><i>discount</i>DI<i>item</i></td>
                <td>Set a percent discount <i>discount</i>
                for just one item <i>item</i></td>
            </tr>
            <tr>
                <td><i>discount</i>PD<i>item</i></td>
                <td>Same as DI above</td>
            </tr>
            </table>";
    }
}

