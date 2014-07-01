<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

/* 17Aug12 flathat Add titles to un-coded "History" and "Change Status" links.
*/

class Suspension extends MemberModule {

    function showEditForm($memNum,$country="US"){
        global $FANNIE_URL;

        $dbc = $this->db();
        
        $infoQ = $dbc->prepare_statement("SELECT CASE WHEN s.type = 'I' THEN 'Inactive' ELSE 'Terminated' END as status,
                s.suspDate,
                CASE WHEN s.reasoncode = 0 THEN s.reason ELSE r.textStr END as reason
                FROM suspensions AS s LEFT JOIN reasoncodes AS r
                ON s.reasoncode & r.mask <> 0
                WHERE s.cardno=?");
        $infoR = $dbc->exec_statement($infoQ,array($memNum));

        $status = "Active";
        $date = "";
        $reason = "";
        if ($dbc->num_rows($infoR) > 0){
            while($infoW = $dbc->fetch_row($infoR)){
                $status = $infoW['status'];
                $date = $infoW['suspDate'];
                $reason .= $infoW['reason'].", ";
            }       
            $reason = rtrim($reason,", ");
        }

        $ret = "<fieldset><legend>Active Status</legend>";
        $ret .= "<table class=\"MemFormTable\" 
            border=\"0\">";

        $ret .= "<tr><th>Current Status</th>";
        $ret .= "<td>$status</td>";
        if (!empty($reason)){
            $ret .= "<th>Reason</th>";
            $ret .= "<td>$reason</td></tr>";
        }
        $ret .= "<tr><td><a href=\"{$FANNIE_URL}reports/SuspensionHistory/index.php?memNum=$memNum\">History</a></td>";
        $ret .= "<td><a href=\"{$FANNIE_URL}mem/MemStatusEditor.php?memID=$memNum\">Change Status</a></td></tr>";

        $ret .= "</table></fieldset>";
        return $ret;
    }
}

?>
