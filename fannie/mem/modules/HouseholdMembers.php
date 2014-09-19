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

class HouseholdMembers extends MemberModule {

    function showEditForm($memNum, $country="US"){
        global $FANNIE_URL;

        $dbc = $this->db();
        
        $infoQ = $dbc->prepare_statement("SELECT c.FirstName,c.LastName
                FROM custdata AS c 
                WHERE c.CardNo=? AND c.personNum > 1
                ORDER BY c.personNum");
        $infoR = $dbc->exec_statement($infoQ,array($memNum));

        $ret = "<fieldset><legend>Household Members</legend>";
        $ret .= "<table class=\"MemFormTable\" 
            border=\"0\">";
        
        $count = 0; 
        while($infoW = $dbc->fetch_row($infoR)){
            $ret .= sprintf('<tr><th>First Name</th>
                <td><input name="HouseholdMembers_fn[]"
                maxlength="30" value="%s" /></td>
                <th>Last Name</th>
                <td><input name="HouseholdMembers_ln[]"
                maxlength="30" value="%s" /></td></tr>',
                $infoW['FirstName'],$infoW['LastName']);
            $count++;
        }

        while($count < 3){
            $ret .= sprintf('<tr><th>First Name</th>
                <td><input name="HouseholdMembers_fn[]"
                maxlength="30" value="" /></td>
                <th>Last Name</th>
                <td><input name="HouseholdMembers_ln[]"
                maxlength="30" value="" /></td></tr>');
            $count++;
        }

        $ret .= "</table></fieldset>";
        return $ret;
    }

    function saveFormData($memNum){
        global $FANNIE_ROOT;
        $dbc = $this->db();
        if (!class_exists("CustdataModel"))
            include($FANNIE_ROOT.'classlib2.0/data/models/CustdataModel.php');

        $CUST_FIELDS = array('personNum'=>array(),'FirstName'=>array(),'LastName'=>array());

        /**
          Model needs all names, so lookup primary member
        */
        $lookupP = $dbc->prepare_statement("SELECT FirstName,LastName FROM custdata WHERE
                personNum=1 AND CardNo=?");
        $lookupR = $dbc->exec_statement($lookupP, array($memNum));
        if ($dbc->num_rows($lookupR) == 0){
            return "Error: Problem saving household members<br />"; 
        }
        $lookupW = $dbc->fetch_row($lookupR);
        $CUST_FIELDS['personNum'][] = 1;
        $CUST_FIELDS['FirstName'][] = $lookupW['FirstName'];
        $CUST_FIELDS['LastName'][] = $lookupW['LastName'];

        $fns = FormLib::get_form_value('HouseholdMembers_fn',array());
        $lns = FormLib::get_form_value('HouseholdMembers_ln',array());
        $pn = 2;
        for($i=0; $i<count($lns); $i++){
            if (empty($fns[$i]) && empty($lns[$i])) continue;

            $CUST_FIELDS['personNum'][] = $pn;
            $CUST_FIELDS['FirstName'][] = $fns[$i];
            $CUST_FIELDS['LastName'][] = $lns[$i];

            $pn++;
        }

        $test = CustdataModel::update($memNum, $CUST_FIELDS);

        if ($test === False)
            return "Error: Problem saving household members<br />"; 

        return '';
    }
}

?>
