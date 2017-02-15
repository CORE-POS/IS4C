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

class MemType extends \COREPOS\Fannie\API\member\MemberModule {

    public function width()
    {
        return parent::META_WIDTH_HALF;
    }

    function showEditForm($memNum, $country="US")
    {
        $dbc = $this->db();
        $account = self::getAccount();
        
        /**
          Check parameters setting to decide whether
          the discount value from custdata should be
          displayed vs the discount value from memtype
        */
        $modeR = $dbc->query("
            SELECT p.param_value
            FROM parameters AS p
            WHERE param_key='useMemTypeTable'
                AND store_id=0
                AND lane_id=0");
        $discount_mode = 'custdata.discount';
        if ($modeR && $modeW = $dbc->fetch_row($modeR)) {
            if ($modeW['param_value'] == 1) {
                $discount_mode = 'memtype.discount';
            }
        }

        $ret = "<div class=\"panel panel-default\">
            <div class=\"panel-heading\">Membership Type</div>
            <div class=\"panel-body\">";

        $ret .= '<div class="form-group form-inline">';
        $ret .= '<span class="label primaryBackground">Type</span> ';
        $ret .= '<select name="MemType_type" class="form-control">';
        $disc = 0;
        $mDesc = '';
        $types = new MemtypeModel($dbc);
        foreach ($types->find('memtype') as $type) {
            $ret .= sprintf("<option value=%d %s>%s</option>",
                $type->memtype(), 
                ($account['customerTypeID'] == $type->memtype() ? 'selected' : ''),
                $type->memDesc());
            if ($account['customerTypeID'] == $type->memtype()) {
                $mDesc = $type->memDesc();
                $disc = $type->discount();
                if ($discount_mode = 'custdata.discount') {
                    foreach ($account['customers'] as $c) {
                        if ($c['accountHolder']) {
                            $disc = $c['discount'];
                            break;
                        }
                    }
                }
            }
        }
        $ret .= "</select> ";
        $ret .= '<input type="hidden" name="MemType_inactive" value="' . $account['activeStatus'] . '" />';

        if ($discount_mode == 'memtype.discount') {
            $discountTip = " title=\"The discount for the Member's current Type: " .
                "{$mDesc}\"";
        } else {
            $discountTip = " title=\"The Member's current Discount, " .
                "regardless of assigned Type, which is: " .
                "{$mDesc}\"";
        }
        
        $ret .= '<span class="label primaryBackground"' .
                    $discountTip . '>Discount</span> ';
        $ret .= sprintf('%d%%',$disc);
        $ret .= '</div>';

        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }

    public function saveFormData($memNum, $json=array())
    {
        $dbc = $this->db();

        $mtype = FormLib::get_form_value('MemType_type',0);
        $inactive = FormLib::get('MemType_inactive');
        // cannot alter type of inactive members. Must
        // re-activate first
        if ($inactive != '') {
            return '';
        }

        // Default values for custdata fields that depend on Member Type.
        $json['customerTypeID'] = $mtype;

        // Get any special values for this Member Type.
        $mt = $dbc->tableDefinition('memtype');
        $q = $dbc->prepare("SELECT custdataType,discount,staff,ssi from memtype WHERE memtype=?");
        if ($dbc->tableExists('memdefaults') && (!isset($mt['custdataType']) || !isset($mt['discount']) || !isset($mt['staff']) || !isset($mt['ssi']))) {
            $q = $dbc->prepare("SELECT cd_type as custdataType,discount,staff,SSI as ssi
                    FROM memdefaults WHERE memtype=?");
        }
        $r = $dbc->execute($q,array($mtype));
        if ($dbc->num_rows($r) > 0){
            $w = $dbc->fetch_row($r);
            $json['memberStatus'] = $w['custdataType'];
            for ($i=0; $i<count($json['customers']); $i++) {
                $json['customers'][$i]['discount'] = $w['discount'];
                $json['customers'][$i]['staff'] = $w['staff'];
                $json['customers'][$i]['lowIncomeBenefits'] = $w['ssi'];
            }
        }

        return $json;
    }
}

