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

class MemCard extends \COREPOS\Fannie\API\member\MemberModule {

    public function width()
    {
        return parent::META_WIDTH_HALF;
    }

    function HasSearch(){
        $FANNIE_MEMBER_UPC_PREFIX = FannieConfig::config('FANNIE_MEMBER_UPC_PREFIX');
        if (isset($FANNIE_MEMBER_UPC_PREFIX) &&
            $FANNIE_MEMBER_UPC_PREFIX != "") {
            return True;
        } else {
            return False;
        }
    }

    function showSearchForm($country='US') {
        $FANNIE_MEMBER_UPC_PREFIX = FannieConfig::config('FANNIE_MEMBER_UPC_PREFIX');
        $ret = '';
        $ret .= '<div class="row form-group form-inline" ' .
            'title="Type or scan, with or without the prefix ' .
            $FANNIE_MEMBER_UPC_PREFIX . '"' .
            '>' .
            '<label>Membership Card</label>' .
            ' <input type="text" name="MemCard_mc"' .
            'size="13" maxlength="13" ' .
            'id="s_mc" class="form-control" />' .
        '</div>';

        return $ret;
    }

    /* What should replace 'mFirstName'?  mMemberCard
    public function getSearchLoadCommands()
    {
        $FANNIE_URL = FannieConfig::config('URL');
        return array(
            "bindAutoComplete('#s_mc', '" . $FANNIE_URL . "ws/', 'mFirstName');\n",
        );
    }
    */

    function GetSearchResults()
    {
        $FANNIE_MEMBER_UPC_PREFIX = FannieConfig::config('FANNIE_MEMBER_UPC_PREFIX');
        $dbc = $this->db();

        $ret = array();

        $mc = "";
        $mc = FormLib::get_form_value('MemCard_mc');
        if (!preg_match("/^\d+$/",$mc)) {
            return $ret;
        }
        $mcc = "";
        if (strlen($mc) == 13) {
            $mcc = $mc;
        } else if (strlen($mc) == 11) {
            $mcc = sprintf("00%s", $mc);
        } else {
            $mcc = sprintf("%s%05d",$FANNIE_MEMBER_UPC_PREFIX, (int)$mc);
        }

        $json = array(
            'idCardUPC' => $mcc,
        );
        $accounts = \COREPOS\Fannie\API\member\MemberREST::search($json, 0);

        return $accounts;
    }


    // Return a form segment for display or edit the Member Card#
    function showEditForm($memNum, $country="US")
    {
        $FANNIE_URL = FannieConfig::config('URL');
        $FANNIE_MEMBER_UPC_PREFIX = FannieConfig::config('FANNIE_MEMBER_UPC_PREFIX');

        $account = self::getAccount();

        $prefix = isset($FANNIE_MEMBER_UPC_PREFIX) ? $FANNIE_MEMBER_UPC_PREFIX : "";
        $plen = strlen($prefix);

        $ret = "<div class=\"panel panel-default\">
            <div class=\"panel-heading\">Membership Card</div>
            <div class=\"panel-body\">";
        $upc = $account['idCardUPC'];
        if ( $prefix && strpos("$upc", "$prefix") === 0 ) {
            $upc = substr($upc,$plen);
            $upc = ltrim($upc,"0");
        }

        $ret .= '<div class="form-group form-inline">
            <span class="label primaryBackground">Card#</span>
            <input type="text" name="memberCard" class="form-control"
                value="' . $upc . '" />
            </div>';

        $ret .= "</div>";
        $ret .= "</div>";

        return $ret;

    // showEditForm
    }

    // Update, insert or delete the Member Card#.
    // Return "" on success or an error message.
    public function saveFormData($memNum, $json=array())
    {

        global $FANNIE_MEMBER_UPC_PREFIX, $FANNIE_ROOT;
        $dbc = $this->db();
        if (!class_exists("MemberCardsModel"))
            include($FANNIE_ROOT.'classlib2.0/data/models/MemberCardsModel.php');

        $prefix = isset($FANNIE_MEMBER_UPC_PREFIX) ? $FANNIE_MEMBER_UPC_PREFIX : "";
        $plen = strlen($prefix);

        $form_upc = FormLib::get_form_value('memberCard','');
        // Restore prefix and leading 0's to upc.
        if ( $form_upc && strlen($form_upc) < 13 ) {
            $clen = (13 - $plen);
            $form_upc = sprintf("{$prefix}%0{$clen}d", $form_upc);
        }

        $model = new MemberCardsModel($dbc);
        $model->card_no($memNum);
        $model->upc($form_upc);
        $saved = $model->save();
        $model->pushToLanes();

        if (!$saved) {
            return 'Error: problem saving Member Card<br />';
        } else {
            return '';
        }

    // saveFormData
    }

// MemCard
}

