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

class MemDates extends \COREPOS\Fannie\API\member\MemberModule {

    public function width()
    {
        return parent::META_WIDTH_HALF;
    }

    function showEditForm($memNum, $country="US")
    {
        $account = self::getAccount();
        if (date('Y', strtotime($account['startDate'])) > 1900) {
            $account['startDate'] = date('Y-m-d', strtotime($account['startDate']));
        } else {
            $account['startDate'] = '';
        }
        if (date('Y', strtotime($account['endDate'])) > 1900) {
            $account['endDate'] = date('Y-m-d', strtotime($account['endDate']));
        } else {
            $account['endDate'] = '';
        }

        $ret = "<div class=\"panel panel-default\">
            <div class=\"panel-heading\">Membership Dates</div>
            <div class=\"panel-body\">";

        $ret .= '<div class="form-group form-inline">';
        $ret .= '<span class="label primaryBackground">Start</span>';
        $ret .= sprintf(' <input name="MemDates_start"
                maxlength="10" value="%s" id="MemDates_start"
                class="form-control date-field" /> ',$account['startDate']); 
        $ret .= '<span class="label primaryBackground">End</span>';
        $ret .= sprintf(' <input name="MemDates_end" 
                maxlength="10" value="%s" id="MemDates_end"
                class="form-control date-field" />',$account['endDate']);  
        $ret .= '</div>';

        $ret .= "</div>";
        $ret .= "</div>";

        return $ret;
    }

    function saveFormData($memNum)
    {
        $json = array(
            'cardNo' => $memNum,
            'startDate' => FormLib::get('MemDates_start'),
            'endDate' => FormLib::get('MemDates_end'),
        );
        $resp = \COREPOS\Fannie\API\member\MemberREST::post($memNum, $json);
        
        if ($resp['errors'] > 0) {
            return "Error: problem saving start/end dates<br />";
        } else {
            return "";
        }
    }
}

?>
