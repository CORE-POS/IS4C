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

class ContactInfo extends MemberModule {

    function showEditForm($memNum, $country="US"){
        $dbc = $this->db();
        
        $infoQ = $dbc->prepare_statement("SELECT CardNo,FirstName,LastName,
                street,city,state,zip,phone,email_1,
                email_2,ads_OK FROM custdata AS c
                LEFT JOIN meminfo AS m ON c.CardNo = m.card_no
                WHERE c.personNum=1 AND CardNo=?");
        $infoR = $dbc->exec_statement($infoQ,array($memNum));
        $infoW = $dbc->fetch_row($infoR);

        $labels = array();
        switch ($country) {
            case "US":
                $labels['state'] = "State";
                $labels['zip'] = "Zip";
                break;
            case "CA":
                $labels['state'] = "Province";
                $labels['zip'] = "Postal Code";
                break;
        }

        $ret = "<fieldset><legend>Contact Info</legend>";
        $ret .= "<table class=\"MemFormTable\" 
            border=\"0\">";

        $ret .= "<tr><th>First Name</th>";
        $ret .= sprintf('<td colspan="2"><input name="ContactInfo_fn" maxlength="30"
                value="%s" /></td>',$infoW['FirstName']);
        $ret .= "<th>Last Name</th>";
        $ret .= sprintf('<td colspan="2"><input name="ContactInfo_ln" maxlength="30"
                value="%s" /></td>',$infoW['LastName']);
        $ret .= sprintf('<td colspan="3"><a href="MemPurchasesPage.php?id=%d">View Receipts</a></td></tr>',
                    $memNum);

        $addrs = strstr($infoW['street'],"\n")?explode("\n",$infoW['street']):array($infoW['street'],'');
        $ret .= "<tr><th>Address</th>";
        $ret .= sprintf('<td colspan="2"><input name="ContactInfo_addr1" maxlength="125"
                value="%s" /></td>',$addrs[0]);
        $ret .= "<th>Gets Mail</th>";
        $ret .= sprintf('<td colspan="2"><input type="checkbox" name="ContactInfo_mail"
                %s /></td></tr>',($infoW['ads_OK']==1?'checked':''));
        
        $ret .= "<tr><th>Address (2)</th>";
        $ret .= sprintf('<td colspan="2"><input name="ContactInfo_addr2" maxlength="125"
                value="%s" /></td>',$addrs[1]);

        $ret .= "<th>City</th>";
        $ret .= sprintf('<td><input name="ContactInfo_city" maxlength="20"
                value="%s" size="15" /></td>',$infoW['city']);
        $ret .= "<th>{$labels['state']}</th>";
        $ret .= sprintf('<td><input name="ContactInfo_state" maxlength="2"
                value="%s" size="2" /></td>',$infoW['state']);
        $ret .= "<th>{$labels['zip']}</th>";
        $ret .= sprintf('<td><input name="ContactInfo_zip" maxlength="10"
                value="%s" size="5" /></td></tr>',$infoW['zip']);

        $ret .= "<tr><th>Phone</th>";
        $ret .= sprintf('<td><input name="ContactInfo_ph1" maxlength="30"
                value="%s" size="12" /></td>',$infoW['phone']);
        $ret .= "<th>Alt. Phone</th>";
        $ret .= sprintf('<td><input name="ContactInfo_ph2" maxlength="30"
                value="%s" size="12" /></td>',$infoW['email_2']);
        $ret .= "<th>E-mail</th>";
        $ret .= sprintf('<td colspan="4"><input name="ContactInfo_email" maxlength="75"
                value="%s" /></td>',$infoW['email_1']);

        $ret .= "</table></fieldset>";
        return $ret;
    }

    function saveFormData($memNum){
        global $FANNIE_ROOT;
        $dbc = $this->db();
        if (!class_exists("MeminfoModel"))
            include($FANNIE_ROOT.'classlib2.0/data/models/MeminfoModel.php');
        if (!class_exists("CustdataModel"))
            include($FANNIE_ROOT.'classlib2.0/data/models/CustdataModel.php');

        $MI_FIELDS = array(
            'street' => FormLib::get_form_value('ContactInfo_addr1',''),
            'city' => FormLib::get_form_value('ContactInfo_city',''),
            'state' => FormLib::get_form_value('ContactInfo_state',''),
            'zip' => FormLib::get_form_value('ContactInfo_zip',''),
            'phone' => FormLib::get_form_value('ContactInfo_ph1',''),
            'email_2' => FormLib::get_form_value('ContactInfo_ph2',''),
            'email_1' => FormLib::get_form_value('ContactInfo_email',''),
            'ads_OK' => (FormLib::get_form_value('ContactInfo_mail')!=='' ? 1 : 0)
        );
        /* Canadian Postal Code, and City and Province
         * Phone style: ###-###-####
        */
        if ( preg_match("/^[A-Z]\d[A-Z]/i", $MI_FIELDS['zip']) ) {
            $MI_FIELDS['zip'] = strtoupper($MI_FIELDS['zip']);
            if ( strlen($MI_FIELDS['zip']) == 6 ) {
                $MI_FIELDS['zip'] = substr($MI_FIELDS['zip'],0,3).' '. substr($MI_FIELDS['zip'],3,3);
            }
            // Postal code M* supply City and Province
            if ( preg_match("/^M/", $MI_FIELDS['zip']) &&
                    $MI_FIELDS['city'] == '' && $MI_FIELDS['state'] == '') {
                $MI_FIELDS['city'] = 'Toronto';
                $MI_FIELDS['state'] = 'ON';
            }
            // Phone# style: ###-###-####
            if ( preg_match("/^[MKLP]/", $MI_FIELDS['zip']) ) {
                if ( preg_match("/^[-() .0-9]+$/",$MI_FIELDS['phone']) ) {
                    $phone = preg_replace("/[^0-9]/", '' ,$MI_FIELDS['phone']);
                    if ( preg_match("/^\d{10}$/",$phone) )
                        $MI_FIELDS['phone'] = preg_replace("/(\d{3})(\d{3})(\d{4})/",'${1}-${2}-${3}',$phone);
                }
                if ( preg_match("/^[-() .0-9]+$/",$MI_FIELDS['email_2']) ) {
                    $phone = preg_replace("/[^0-9]/", '' ,$MI_FIELDS['email_2']);
                    if ( preg_match("/^\d{10}$/",$phone) )
                        $MI_FIELDS['email_2'] = preg_replace("/(\d{3})(\d{3})(\d{4})/",'${1}-${2}-${3}',$phone);
                }
            }
        }
        if (FormLib::get_form_value('ContactInfo_addr2','') !== '')
            $MI_FIELDS['street'] .= "\n".FormLib::get_form_value('ContactInfo_addr2');
        $test1 = MeminfoModel::update($memNum, $MI_FIELDS);

        $CUST_FIELDS = array(
            'personNum' => array(1),
            'FirstName' => array(FormLib::get_form_value('ContactInfo_fn')),
            'LastName' => array(FormLib::get_form_value('ContactInfo_ln'))
        );
        $test2 = CustdataModel::update($memNum, $CUST_FIELDS);

        if ($test1 === False || $test2 === False)
            return "Error: problem saving Contact Information<br />";
        else
            return "";
    }

    function hasSearch(){ return True; }

    function showSearchForm($country="US"){
        $labels = array();
        switch ($country) {
            case "US":
                $labels['state'] = "State";
                $labels['zip'] = "Zip";
                break;
            case "CA":
                $labels['state'] = "Province";
                $labels['zip'] = "Postal Code";
                break;
        }
        return "<p><b>First Name</b>: <input type='text' name='ContactInfo_fn'
                size='10' id='s_fn' /> &nbsp;&nbsp;&nbsp; <b>Last Name</b>: 
                <input type='text' name='ContactInfo_ln' size='10' id='s_ln' />
                <br /><br />
                <b>Address</b>: 
                <input type='text' name='ContactInfo_addr' id='s_addr' size='15' />
                <br /><br />
                <b>City</b>: 
                <input type='text' name='ContactInfo_city' id='s_city' size='8' />
                <b>{$labels['state']}</b>:
                <input type='text' name='ContactInfo_state' size='2' />
                <b>{$labels['zip']}</b>:
                <input type='text' name='ContactInfo_zip' size='5' />
                <br /><br />
                <b>Email</b>: 
                <input type='text' name='ContactInfo_email' id='s_email' size='15' />
                </p>";
    }

    public function getSearchLoadCommands()
    {
        global $FANNIE_URL;
        return array(
            "bindAutoComplete('#s_fn', '" . $FANNIE_URL . "ws/', 'mFirstName');\n",
            "bindAutoComplete('#s_ln', '" . $FANNIE_URL . "ws/', 'mLastName');\n",
            "bindAutoComplete('#s_addr', '" . $FANNIE_URL . "ws/', 'mAddress');\n",
            "bindAutoComplete('#s_city', '" . $FANNIE_URL . "ws/', 'mCity');\n",
            "bindAutoComplete('#s_email', '" . $FANNIE_URL . "ws/', 'mEmail');\n",
        );
    }

    function getSearchResults(){
        $dbc = $this->db();

        $fn = FormLib::get_form_value('ContactInfo_fn');
        $ln = FormLib::get_form_value('ContactInfo_ln');
        $addr = FormLib::get_form_value('ContactInfo_addr');
        $city = FormLib::get_form_value('ContactInfo_city');
        $state = FormLib::get_form_value('ContactInfo_state');
        $zip = FormLib::get_form_value('ContactInfo_zip');
        $email = FormLib::get_form_value('ContactInfo_email');

        $where = "";
        $args = array();
        if (!empty($fn)){
            $where .= " AND FirstName LIKE ?";
            $args[] = '%'.$fn.'%';
        }
        if (!empty($ln)){
            $where .= " AND LastName LIKE ?";
            $args[] = '%'.$ln.'%';
        }
        if (!empty($addr)){
            $where .= " AND street LIKE ?";
            $args[] = '%'.$addr.'%';
        }
        if (!empty($city)){
            $where .= " AND city LIKE ?";
            $args[] = '%'.$city.'%';
        }
        if (!empty($state)){
            $where .= " AND state LIKE ?";
            $args[] = '%'.$state.'%';
        }
        if (!empty($zip)){
            $where .= " AND zip LIKE ?";
            $args[] = '%'.$zip.'%';
        }
        if (!empty($email)){
            $where .= " AND email_1 LIKE ?";
            $args[] = '%'.$email.'%';
        }

        $ret = array();
        if (!empty($where)){
            $q = $dbc->prepare_statement("SELECT CardNo,FirstName,LastName FROM
                custdata as c LEFT JOIN meminfo AS m
                ON c.CardNo = m.card_no
                WHERE 1=1 $where ORDER BY m.card_no");
            $r = $dbc->exec_statement($q,$args);
            if ($dbc->num_rows($r) > 0){
                while($w = $dbc->fetch_row($r)){
                    $ret[$w[0]] = $w[1]." ".$w[2];
                }
            }
        }
        return $ret;
    }
}

?>
