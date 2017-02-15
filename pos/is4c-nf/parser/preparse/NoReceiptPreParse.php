<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

namespace COREPOS\pos\parser\preparse;
use COREPOS\pos\parser\PreParser;

class NoReceiptPreParse extends PreParser 
{
    private $remainder;
    
    function check($str){
        if (substr($str,-2) == 'NR'){
            $this->remainder = substr($str,0,strlen($str)-2);
            return True;
        }
        elseif (substr($str,0,2) == "NR"){
            $this->remainder = substr($str,2);
            return True;
        }
        return False;
    }

    function parse($str)
    {
        $this->session->set('receiptToggle', 0);
        return $this->remainder;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td><i>tender command</i>NR<i>item</i></td>
                <td>Apply tender with receipt disabled</td>
            </tr>
            <tr>
                <td>NR<i>tender command</i></td>
                <td>Same as above</td>
            </tr>
            </table>";
    }
}

