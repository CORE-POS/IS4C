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

class MemType extends \COREPOS\Fannie\API\member\MemberModule {

    public function width()
    {
        return parent::META_WIDTH_HALF;
    }

    function showEditForm($memNum, $country="US")
    {
        $dbc = $this->db();
        
        $infoQ = $dbc->prepare_statement("
            SELECT c.memType AS custdataType,
                n.memType,
                n.memDesc,
                c.discount AS custdataDiscount,
                n.discount AS memTypeDiscount
            FROM custdata AS c
                CROSS JOIN memtype AS n 
            WHERE c.CardNo=? AND c.personNum=1
            ORDER BY n.memType");
        $infoR = $dbc->exec_statement($infoQ,array($memNum));

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
        while($infoW = $dbc->fetch_row($infoR)){
            $ret .= sprintf("<option value=%d %s>%s</option>",
                $infoW['memType'],
                ($infoW['custdataType'] == $infoW['memType'] ? 'selected' : ''),
                $infoW['memDesc']);
            if ($discount_mode == 'custdata.discount') {
                $disc = $infoW['custdataDiscount'];
            } elseif ($infoW['custdataType'] == $infoW['memType']) {
                $disc = $infoW['memTypeDiscount'];
            }
        }
        $ret .= "</select> ";
        
        $ret .= '<span class="label primaryBackground">Discount</span> ';
        $ret .= sprintf('%d%%',$disc);
        $ret .= '</div>';

        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }

    function saveFormData($memNum)
    {
        $dbc = $this->db();

        $mtype = FormLib::get_form_value('MemType_type',0);

        // Default values for custdata fields that depend on Member Type.
        $CUST_FIELDS = array();
        $CUST_FIELDS['memType'] = $mtype;
        $CUST_FIELDS['Type'] = 'REG';
        $CUST_FIELDS['Staff'] = 0;
        $CUST_FIELDS['Discount'] = 0;
        $CUST_FIELDS['SSI'] = 0;

        // Get any special values for this Member Type.
        $mt = $dbc->tableDefinition('memtype');
        $q = $dbc->prepare_statement("SELECT custdataType,discount,staff,ssi from memtype WHERE memtype=?");
        if ($dbc->tableExists('memdefaults') && (!isset($mt['custdataType']) || !isset($mt['discount']) || !isset($mt['staff']) || !isset($mt['ssi']))) {
            $q = $dbc->prepare_statement("SELECT cd_type as custdataType,discount,staff,SSI as ssi
                    FROM memdefaults WHERE memtype=?");
        }
        $r = $dbc->exec_statement($q,array($mtype));
        if ($dbc->num_rows($r) > 0){
            $w = $dbc->fetch_row($r);
            $CUST_FIELDS['Type'] = $w['custdataType'];
            $CUST_FIELDS['Discount'] = $w['discount'];
            $CUST_FIELDS['Staff'] = $w['staff'];
            $CUST_FIELDS['SSI'] = $w['ssi'];
        }

        // Assign Member Type values to each custdata record for the Membership.
        $cust = new CustdataModel($dbc);
        $cust->CardNo($memNum);
        $error = "";
        foreach($cust->find() as $obj){
            $obj->memType($mtype);
            $obj->Type($CUST_FIELDS['Type']);
            $obj->Staff($CUST_FIELDS['Staff']);
            $obj->Discount($CUST_FIELDS['Discount']);
            $obj->SSI($CUST_FIELDS['SSI']);
            $upR = $obj->save();
            if ($upR === False)
                $error .= $mtype;
        }
        
        if ($error)
            return "Error: problem saving Member Type<br />";
        else
            return "";
    }
}

?>
