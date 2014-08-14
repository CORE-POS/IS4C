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

class MemType extends MemberModule {

    function showEditForm($memNum, $country="US"){
        global $FANNIE_URL;

        $dbc = $this->db();
        
        $infoQ = $dbc->prepare_statement("SELECT c.memType,n.memType,n.memDesc,c.discount
                FROM custdata AS c, 
                memtype AS n 
                WHERE c.CardNo=? AND c.personNum=1
                ORDER BY n.memType");
        $infoR = $dbc->exec_statement($infoQ,array($memNum));

        $ret = "<fieldset class='memOneRow'><legend>Membership Type</legend>";
        $ret .= "<table class=\"MemFormTable\" 
            border=\"0\">";

        $ret .= "<tr><th>Type</th>";
        $ret .= '<td><select name="MemType_type">';
        $disc = 0;
        while($infoW = $dbc->fetch_row($infoR)){
            $ret .= sprintf("<option value=%d %s>%s</option>",
                $infoW[1],
                ($infoW[0]==$infoW[1]?'selected':''),
                $infoW[2]);
            $disc = $infoW[3];
        }
        $ret .= "</select></td>";
        
        $ret .= "<th>Discount</th>";
        /*
        $ret .= sprintf('<td><input name="MemType_discount" value="%d"
                size="4" /></td></tr>',$disc);  
        */
        $ret .= sprintf('<td>%d%%</td></tr>',$disc);

        $ret .= "</table></fieldset>";
        return $ret;
    }

    function saveFormData($memNum){
        global $FANNIE_ROOT;
        $dbc = $this->db();
        if (!class_exists("CustdataModel"))
            include($FANNIE_ROOT.'classlib2.0/data/models/CustdataModel.php');

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
