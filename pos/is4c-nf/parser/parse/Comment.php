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

namespace COREPOS\pos\parser\parse;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\parser\Parser;

class Comment extends Parser {
    function check($str){
        if (substr($str,0,2) == "CM")
            return True;
        return False;
    }

    function parse($str){
        $ret = $this->default_json();
        if (strlen($str) > 2){
            $comment = substr($str,2);
            TransRecord::addcomment($comment);
            $ret['output'] = DisplayLib::lastpage();
        }
        else {
            $ret['main_frame'] = MiscLib::base_url().'gui-modules/bigComment.php';
        }
        return $ret;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>CM<i>text</i></td>
                <td>Add <i>text</i> to the transaction
                as a comment line</td>
            </tr>
            </table>";
    }
}

