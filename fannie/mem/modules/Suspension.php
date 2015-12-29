<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

class Suspension extends \COREPOS\Fannie\API\member\MemberModule {

    public function width()
    {
        return parent::META_WIDTH_THIRD;
    }

    function showEditForm($memNum,$country="US"){
        global $FANNIE_URL;

        $dbc = $this->db();
        
        $infoQ = $dbc->prepare("SELECT CASE WHEN s.type = 'I' THEN 'Inactive' ELSE 'Terminated' END as status,
                s.suspDate,
                CASE WHEN s.reasoncode = 0 THEN s.reason ELSE r.textStr END as reason
                FROM suspensions AS s LEFT JOIN reasoncodes AS r
                ON s.reasoncode & r.mask <> 0
                WHERE s.cardno=?");
        $infoR = $dbc->execute($infoQ,array($memNum));

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

        $ret = "<div class=\"panel panel-default\">
            <div class=\"panel-heading\">Active Status</div>
            <div class=\"panel-body\">";

        $ret .= '<div class="form-group">
            <span class="label primaryBackground">Current Status</span>';
        $ret .= ' <strong>' . $status . '</strong>';
        $ret .= '</div>';

        if (!empty($reason)) {
            $ret .= '<div class="form-group">
                <span class="label primaryBackground">Reason</span>';
            $ret .= ' <strong>' . $reason . '</strong>';
            $ret .= '</div>';
        }
        
        $ret .= '<div class="form-group">';
        $ret .= "<a href=\"{$FANNIE_URL}reports/SuspensionHistory/index.php?memNum=$memNum\">History</a>";
        $ret .= ' | ';
        $ret .= "<a href=\"{$FANNIE_URL}mem/MemStatusEditor.php?memID=$memNum\">Change Status</a>";
        $ret .= '</div>';

        $ret .= "</div>";
        $ret .= "</div>";

        return $ret;
    }
}

