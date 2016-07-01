<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

namespace COREPOS\pos\parser\parse;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use \CoreLocal;
use COREPOS\pos\parser\Parser;

class TaxFoodShift extends Parser {

    function check($str)
    {
        if ($str == "TFS" && CoreLocal::get('currentid') > 0){
            return True;
        }
        return False;
    }

    function parse($str)
    {
        $curID = CoreLocal::get("currentid");

        $dbc = Database::tDataConnect();

        $query = "SELECT trans_type,tax,foodstamp FROM localtemptrans WHERE trans_id=$curID";
        $res = $dbc->query($query);
        if ($dbc->num_rows($res) == 0) return True; // shouldn't ever happen
        $item = $dbc->fetch_row($res);

        $query = "SELECT MAX(id) FROM taxrates";
        $res = $dbc->query($query);
        $tax_cap = 0;
        if ($dbc->num_rows($res)>0) {
            $taxID = $dbc->fetch_row($res);
            $max = $taxID[0];
            if (!empty($max)) $tax_cap = $max;
        }
        $dbc->query($query);    

        $next_tax = $item['tax']+1;
        $next_fs = 0;
        if ($next_tax > $max){
            $next_tax = 0;
            $next_fs = 1;
        }

        $query = "UPDATE localtemptrans 
            set tax=$next_tax,foodstamp=$next_fs 
            WHERE trans_id=$curID";
        $dbc->query($query);    
        
        $ret = $this->default_json();
        $ret['output'] = DisplayLib::listItems(CoreLocal::get("currenttopid"),$curID);
        return $ret; // maintain item cursor position
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>TFS</td>
                <td>Roll through tax/foodstamp settings
                on the current item</td>
            </tr>
            </table>";
    }
}

