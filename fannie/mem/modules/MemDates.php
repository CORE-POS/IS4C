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

        $ret = "<div class=\"container-fluid\"><h4>Membership Dates</h4>";

        $ret .= '<div class="row form-group form-inline">';
        $ret .= '<span class="label primaryBackground">Start</span>';
        $ret .= sprintf('<input name="MemDates_start"
                maxlength="10" value="%s" id="MemDates_start"
                class="form-control" />',$infoW['start_date']); 
        $ret .= '<span class="label primaryBackground">End</span>';
        $ret .= sprintf('<input name="MemDates_end" 
                maxlength="10" value="%s" id="MemDates_end"
                class="form-control" />',$infoW['end_date']);  
        $ret .= '</div>';

        $ret .= "</div>";

        return $ret;
    }

    public function getEditLoadCommands()
    {
        return array(
            "\$('#MemDates_start').datepicker();\n",
            "\$('#MemDates_end').datepicker();\n",
        );
    }

    function saveFormData($memNum){
        global $FANNIE_ROOT;
        $dbc = $this->db();
        if (!class_exists("MemDatesModel"))
            include($FANNIE_ROOT.'classlib2.0/data/models/MemDatesModel.php');
        
        $test = MemDatesModel::update($memNum,
                FormLib::get_form_value('MemDates_start'),
                FormLib::get_form_value('MemDates_end')
        );

        if ($test === False)
            return "Error: problem saving start/end dates<br />";
        else
            return "";
    }
}

?>
