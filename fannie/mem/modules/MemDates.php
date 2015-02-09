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

class MemDates extends \COREPOS\Fannie\API\member\MemberModule {

    public function width()
    {
        return parent::META_WIDTH_HALF;
    }

    function showEditForm($memNum, $country="US"){
        global $FANNIE_URL;

        $dbc = $this->db();
        
        $infoQ = $dbc->prepare_statement("SELECT start_date,end_date
                FROM memDates
                WHERE card_no=?");
        $infoR = $dbc->exec_statement($infoQ,array($memNum));
        $infoW = $dbc->fetch_row($infoR);

        if (date('Y', strtotime($infoW['start_date'])) > 1900) {
            $infoW['start_date'] = date('Y-m-d', strtotime($infoW['start_date']));
        } else {
            $infoW['start_date'] = '';
        }
        if (date('Y', strtotime($infoW['end_date'])) > 1900) {
            $infoW['end_date'] = date('Y-m-d', strtotime($infoW['end_date']));
        } else {
            $infoW['end_date'] = '';
        }

        $ret = "<div class=\"panel panel-default\">
            <div class=\"panel-heading\">Membership Dates</div>
            <div class=\"panel-body\">";

        $ret .= '<div class="form-group form-inline">';
        $ret .= '<span class="label primaryBackground">Start</span>';
        $ret .= sprintf('<input name="MemDates_start"
                maxlength="10" value="%s" id="MemDates_start"
                class="form-control date-field" />',$infoW['start_date']); 
        $ret .= '<span class="label primaryBackground">End</span>';
        $ret .= sprintf('<input name="MemDates_end" 
                maxlength="10" value="%s" id="MemDates_end"
                class="form-control date-field" />',$infoW['end_date']);  
        $ret .= '</div>';

        $ret .= "</div>";
        $ret .= "</div>";

        return $ret;
    }

    function saveFormData($memNum)
    {
        $dbc = $this->db();
        if (!class_exists("MemDatesModel")) {
            include(dirname(__FILE__) . '/../../classlib2.0/data/models/MemDatesModel.php');
        }
        
        $memdate = new MemDatesModel($dbc);
        $memdate->card_no($memNum);
        $memdate->start_date(FormLib::get('MemDates_start'));
        $memdate->end_date(FormLib::get('MemDates_end'));
        $test = $memdate->save();

        if ($test === false) {
            return "Error: problem saving start/end dates<br />";
        } else {
            return "";
        }
    }
}

?>
