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

class ContactInfo extends \COREPOS\Fannie\API\member\MemberModule {

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

        $ret = "<div class=\"panel panel-default\">
            <div class=\"panel-heading\">Contact Info</div>
            <div class=\"panel-body\">";

        $ret .= '<div class="form-group form-inline">';
        $ret .= '<span class="label primaryBackground">First Name</span>';
        $ret .= sprintf('<input name="ContactInfo_fn" maxlength="30"
                value="%s" class="form-control" />',$infoW['FirstName']);
        $ret .= ' <span class="label primaryBackground">Last Name</span>';
        $ret .= sprintf('<input name="ContactInfo_ln" maxlength="30"
                value="%s" class="form-control" />',$infoW['LastName']);
        $ret .= sprintf(' <a href="MemPurchasesPage.php?id=%d">View Receipts</a>',
                    $memNum);
        $ret .= '</div>';

        $ret .= '<div class="form-group form-inline">';
        $addrs = strstr($infoW['street'],"\n")?explode("\n",$infoW['street']):array($infoW['street'],'');
        $ret .= '<span class="label primaryBackground">Address</span>';
        $ret .= sprintf('<input name="ContactInfo_addr1" maxlength="125"
                value="%s" class="form-control" />',$addrs[0]);
        $ret .= ' <span class="label primaryBackground">Address (2)</span>';
        $ret .= sprintf('<input name="ContactInfo_addr2" maxlength="125"
                value="%s" class="form-control" />',$addrs[1]);
        $ret .= ' <label><span class="label primaryBackground">Gets Mail</span>';
        $ret .= sprintf('<input type="checkbox" name="ContactInfo_mail"
                %s class="checkbox-inline" /></label>',($infoW['ads_OK']==1?'checked':''));
        $ret .= '</div>';
        
        $ret .= '<div class="form-group form-inline">';
        $ret .= '<span class="label primaryBackground">City</span>';
        $ret .= sprintf('<input name="ContactInfo_city" maxlength="20"
                value="%s" class="form-control" />',$infoW['city']);
        $ret .= ' <span class="label primaryBackground">' . $labels['state'] . '</span>';
        $ret .= sprintf('<input name="ContactInfo_state" maxlength="2"
                value="%s" class="form-control" />',$infoW['state']);
        $ret .= ' <span class="label primaryBackground">' . $labels['zip'] . '</span>';
        $ret .= sprintf('<input name="ContactInfo_zip" maxlength="10"
                value="%s" class="form-control" />',$infoW['zip']);
        $ret .= '</div>';

        $ret .= '<div class="form-group form-inline">';
        $ret .= '<span class="label primaryBackground">Phone</span>';
        $ret .= sprintf('<input name="ContactInfo_ph1" maxlength="30"
                value="%s" class="form-control" />',$infoW['phone']);
        $ret .= ' <span class="label primaryBackground">Alt. Phone</span>';
        $ret .= sprintf('<input name="ContactInfo_ph2" maxlength="30"
                value="%s" class="form-control" />',$infoW['email_2']);
        $ret .= ' <span class="label primaryBackground">E-mail</span>';
        $ret .= sprintf('<input type="email" name="ContactInfo_email" maxlength="75"
                value="%s" class="form-control" />',$infoW['email_1']);
        $ret .= "</div>";

        $ret .= "</div>";
        $ret .= "</div>";

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

        $meminfo = new MeminfoModel($dbc);
        $meminfo->card_no($memNum);
        $meminfo->street($MI_FIELDS['street']);
        $meminfo->city($MI_FIELDS['city']);
        $meminfo->state($MI_FIELDS['state']);
        $meminfo->phone($MI_FIELDS['phone']);
        $meminfo->email_2($MI_FIELDS['email_2']);
        $meminfo->email_1($MI_FIELDS['email_1']);
        $meminfo->ads_OK($MI_FIELDS['ads_OK']);
        $test1 = $meminfo->save();

        $custdata = new CustdataModel($dbc);
        $custdata->CardNo($memNum);
        $custdata->personNum(1);
        $custdata->FirstName(FormLib::get('ContactInfo_fn'));
        $custdata->LastName(FormLib::get('ContactInfo_ln'));
        $test2 = $custdata->save();

        if ($test1 === False || $test2 === False)
            return "Error: problem saving Contact Information<br />";
        else
            return "";
    }

    function hasSearch(){ return True; }

    function showSearchForm($country="US")
    {
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
        return '
            <div class="row form-group form-inline">
                <label>First Name</label>
                <input type="text" name="ContactInfo_fn"
                    id="s_fn" class="form-control" />
                <label>Last Name</label> 
                <input type="text" name="ContactInfo_ln" id="s_ln" 
                    class="form-control" />
            </div>
            <div class="row form-group form-inline">
                <label>Address</label> 
                <input type="text" name="ContactInfo_addr" id="s_addr" 
                    class="form-control" />
            </div>
            <div class="row form-group form-inline">
                <label>City</label> 
                <input type="text" name="ContactInfo_city" id="s_city" 
                    class="form-control" />
                <label>' . $labels['state'] . '</label>
                <input type="text" name="ContactInfo_state" class="form-control" />
                <label>' . $labels['zip'] . '</label>
                <input type="text" name="ContactInfo_zip" class="form-control" />
            </div>
            <div class="row form-group form-inline">
                <label>Email</label>:
                <input type="text" name="ContactInfo_email" id="s_email" 
                    class="form-control" />
            </div>';
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

    function getSearchResults()
    {
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
            echo $where;
            $q = $dbc->prepare_statement("SELECT CardNo,FirstName,LastName FROM
                custdata as c LEFT JOIN meminfo AS m
                ON c.CardNo = m.card_no
                WHERE 1=1 $where ORDER BY m.card_no, c.personNum DESC");
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
