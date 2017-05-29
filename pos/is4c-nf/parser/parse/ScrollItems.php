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
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\parser\Parser;

class ScrollItems extends Parser 
{
    function check($str)
    {
        if ($str == "U" || $str == "D")
            return True;
        elseif(($str[0] == "U" || $str[0] == "D")
            && is_numeric(substr($str,1)))
            return True;
        return False;
    }

    function parse($str)
    {
        $lines = DisplayLib::screenLines();

        $ret = $this->default_json();
        if ($str == "U") {
            return $ret->output(DisplayLib::listItems($this->session->get("currenttopid"), $this->nextValid($this->session->get("currentid"),-1)));
        } elseif ($str == "D") {
            return $ret->output(DisplayLib::listItems($this->session->get("currenttopid"), $this->nextValid($this->session->get("currentid"),1)));
        }

        $change = (int)substr($str,1);
        $curID = $this->session->get("currenttopid");
        $newID = $this->session->get("currentid");
        $newID = ($str[0] == "U") ? $newID - $change : $newID + $change;
        if ($newID == $curID || $newID == $curID+$lines) {
            $curID = $newID-5;
        }
        if ($curID < 1) {
            $curID = 1;
        }
        $ret["output"] = DisplayLib::listItems($curID, $newID);

        return $ret;
    }

    /**
      New function: log rows don't appear in screendisplay
      so scrolling by simplying incrementing trans_id
      can land on a "blank" line. It still works if you
      keep scrolling but the cursor disappears from the screen.
      This function finds the next visible line instead.
     
      @param $curID the current id
      @param $inc [int] 1 or -1
    */
    private function nextValid($curID, $inc)
    {
        $dbc = Database::tDataConnect();
        $next = $curID;
        while (true) {
            $prev = $next;
            $next += $inc;
            if ($next <= 0) return $prev;

            $res = $dbc->query("SELECT MAX(trans_id) as max,
                    SUM(CASE WHEN trans_id=$next THEN 1 ELSE 0 END) as present
                    FROM screendisplay");
            if ($dbc->numRows($res) == 0) return 1;
            $row = $dbc->fetchRow($res);
            if ($row['max']=='') return 1;
            if ($row['present'] > 0) return $next;
            if ($row['max'] <= $next) return $row['max'];

            // failsafe; shouldn't happen
            if ($next > 1000) break;
        }

        return $curID;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>U</td>
                <td>Scroll up</td>
            </tr>
            <tr>
                <td>D</td>
                <td>Scroll down</td>
            </tr>
            </table>";
    }
}

