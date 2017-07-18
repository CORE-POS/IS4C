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

class Comment extends Parser 
{
    public function check($str)
    {
        return (substr($str, 0, 2) === 'CM' || substr($str, 0, 3) === 'HRI');
    }

    public function parse($str)
    {
        $ret = $this->default_json();
        if ($str === 'CM') {
            $ret['main_frame'] = MiscLib::baseURL().'gui-modules/bigComment.php';
        } elseif ($str === 'HRI') {
            $ret['main_frame'] = MiscLib::baseURL().'gui-modules/requestInfo.php?class=COREPOS-pos-lib-adminlogin-HumanReadableIdRequest';
        } elseif (substr($str, 0, 3) === 'HRI') {
            $hri = substr($str, 3);
            TransRecord::addRecord(array(
                'description' => $hri,
                'trans_type' => 'C',
                'trans_subtype' => 'CM',
                'trans_status' => 'D',
                'charflag' => 'HR',
            ));
            $ret['output'] = DisplayLib::lastpage();
        } else {
            $comment = substr($str,2);
            TransRecord::addcomment($comment);
            $ret['output'] = DisplayLib::lastpage();
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

