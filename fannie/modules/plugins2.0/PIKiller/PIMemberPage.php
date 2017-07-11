<?php 
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'/classlib2.0/FannieAPI.php');
}
if (!class_exists('PIKillerPage')) {
    include('lib/PIKillerPage.php');
}

class PIMemberPage extends PIKillerPage {

    private  $auth_mode = 'None';

    function preprocess(){
        if (FannieAuth::validateUserQuiet('editmembers'))
            $this->auth_mode = 'Full';
        elseif (FannieAuth::validateUserQuiet('editmembers_csc'))
            $this->auth_mode = 'Limited';

        if ($this->auth_mode == 'None'){
            if (isset($_REQUEST['edit'])) unset($_REQUEST['edit']);
            if (isset($_POST['edit'])) unset($_POST['edit']);
            if (isset($_GET['edit'])) unset($_GET['edit']);
        }

        $this->__routes[] = 'get<id><login>';

        return parent::preprocess();
    }

    protected function get_id_login_handler(){
        global $FANNIE_URL;
        $auth = $FANNIE_URL.'auth/ui/loginform.php';
        $redir = $FANNIE_URL.'modules/plugins2.0/PIKiller/PIMemberPage.php?id='.$this->id;
        header("Location: $auth?redirect=$redir");
        
        return False;
    }

    protected function get_id_handler()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $this->card_no = $this->id;

        $this->title = 'Member '.$this->card_no;

        $dbc = FannieDB::get($FANNIE_OP_DB);
        if ($this->id == 0) {
            echo 'Invalid ID';
            return false;
        }

        $this->account = \COREPOS\Fannie\API\member\MemberREST::get($this->id);
        if ($this->account === false) {
            echo 'Invalid ID';
            return false;
        }
        foreach ($this->account['customers'] as $c) {
            if ($c['accountHolder']) {
                $this->primary_customer = $c;
                break;
            }
        }
        $susp = $this->get_model($dbc,'SuspensionsModel',array('cardno'=>$this->card_no));
        if ($susp->load()) $this->__models['suspended'] = $susp;

        $noteP = $dbc->prepare('SELECT note FROM memberNotes WHERE cardno=? ORDER BY stamp DESC');
        $noteR = $dbc->execute($noteP, array($this->card_no));
        $this->__models['note'] = '';
        if ($dbc->num_rows($noteR) > 0){
            $tmp = $dbc->fetch_row($noteR);
            $this->__models['note'] = $tmp['note'];
        }

        $comP = $dbc->prepare("SELECT empNo FROM Commissions WHERE cardNo=? AND type='OWNERSHIP'");
        $this->commissioned = $dbc->getValue($comP, array($this->id));
        $empR = $dbc->query('SELECT emp_no, FirstName FROM employees WHERE EmpActive=1 AND emp_no > 0 ORDER BY FirstName');
        $this->emps = array();
        while ($empW = $dbc->fetchRow($empR)) {
            $this->emps[] = $empW;
        }

        $dbc = FannieDB::get($FANNIE_TRANS_DB);

        $this->__models['equity'] = $this->get_model($dbc, 'EquityLiveBalanceModel',
                    array('memnum'=>$this->card_no));

        $this->__models['ar'] = $this->get_model($dbc, 'ArLiveBalanceModel',
                    array('card_no'=>$this->card_no));

        return true;
    }

    protected function post_id_handler()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $this->card_no = $this->id;
        if ($this->auth_mode == 'None')
            return $this->unknownRequestHandler();

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $note = FormLib::get_form_value('notetext');
        $hash = FormLib::get_form_value('_notetext');
        if (base64_decode($hash) != $note){
            $noteP = $dbc->prepare('INSERT INTO memberNotes
                    (cardno, note, stamp, username) VALUES
                    (?, ?, '.$dbc->now().', ?)');   
            $noteR = $dbc->execute($noteP,array($this->card_no,
                    str_replace("\n",'<br />',$note),
                    $this->current_user));
        }
        
        $json = array(
            'cardNo' => $this->id,
            'customers' => array()
        );
        $account_holder = array('accountHolder' => 1);
        $account_holder['firstName'] = FormLib::get('FirstName');
        $account_holder['lastName'] = FormLib::get('LastName');
        $account_holder['customerID'] = FormLib::get('customerID');
        $json['addressFirstLine'] = FormLib::get('address1');
        $json['addressSecondLine'] = FormLib::get('address2');
        $json['city'] = FormLib::get('city');
        $json['state'] = FormLib::get('state');
        $json['zip'] = FormLib::get('zip');
        $account_holder['phone'] = FormLib::get('phone');
        $account_holder['altPhone'] = FormLib::get('phone2');
        $account_holder['email'] = FormLib::get('email');
        $json['contactAllowed'] = FormLib::get('mailflag', 0);
        $upc = FormLib::get_form_value('upc', false);
        if ($upc !== false) {
            if ($upc != '') {
                $json['idCardUPC'] = BarcodeLib::padUPC($upc);
            } else {
                $json['idCardUPC'] = '';
            }
        }
        if ($this->auth_mode == 'Full') {
            $json['customerTypeID'] = FormLib::get('memType');
            $json['chargeLimit'] = FormLib::get('chargelimit');
            $default = new MemtypeModel($dbc);
            $default->memtype($json['customerTypeID']);
            $default->load();
            if (FormLib::get('suspended') == 0) {
                $json['memberStatus'] = $default->custdataType();
            }
            $account_holder['discount'] = $default->discount();
            $account_holder['staff'] = $default->staff();
            $account_holder['chargeAllowed'] = $json['chargeLimit'] == 0 ? 0 : 1;
            $account_holder['lowIncomeBenefits'] = $default->ssi();

            $start = FormLib::get('start_date', '');
            /**
              Interface hides 1900-01-01 dates from the end-user
              but that's not identical to 0000-00-00. A blank submission
              should preserve that 1900-01-01 date.
            */
            if ($start == '' && FormLib::get('nonBlankStart') != '') {
                $start = FormLib::get('nonBlankStart');
            }
            $json['startDate'] = $start;
            $json['endDate'] = FormLib::get('end_date');
        } else { // get account defaults for additional names if needed
            $account = \COREPOS\Fannie\API\member\MemberREST::get($this->card_no);
            foreach ($account['customers'] as $c) {
                if ($c['accountHolder']) {
                    $account_holder['discount'] = $c['discount'];
                    $account_holder['staff'] = $c['staff'];
                    $account_holder['lowIncomeBenefits'] = $c['lowIncomeBenefits'];
                    $account_holder['chargeAllowed'] = $c['chargeAllowed'];
                }
            }
        }
        $json['customers'][] = $account_holder;

        $names = array('first'=>FormLib::get_form_value('fn'),
                'last'=>FormLib::get_form_value('ln'));
        $fn = FormLib::get_form_value('fn');
        $ln = FormLib::get_form_value('ln');
        $hhID = FormLib::get('hhID');
        for ($i=0;$i<count($fn);$i++) {
            $set = array(
                'first' => isset($fn[$i]) ? $fn[$i] : '',
                'last' => isset($ln[$i]) ? $ln[$i] : '',
                'id' => isset($hhID[$i]) ? $hhID[$i] : '',
            );
            $json['customers'][] = array(
                'customerID' => $hhID[$i],
                'accountHolder' => 0,
                'firstName' => $set['first'],
                'lastName' => $set['last'],
                'discount' => $account_holder['discount'],
                'staff' => $account_holder['staff'],
                'lowIncomeBenefits' => $account_holder['lowIncomeBenefits'],
                'chargeAllowed' => $account_holder['chargeAllowed'],
            );
        }
        $resp = \COREPOS\Fannie\API\member\MemberREST::post($this->card_no, $json);

        $comm = new CommissionsModel($dbc);
        $comm->cardNo($this->id);
        $comm->type('OWNERSHIP');
        $exists = $comm->find();
        if (count($exists) > 0) {
            $comm = $exists[0];
        }
        $comm->empNo(FormLib::get('commissioned'));
        $comm->save();

        $custdata = new CustdataModel($dbc);
        $custdata->CardNo($this->card_no);
        foreach ($custdata->find() as $c) {
            $c->pushToLanes();
        }

        $cards = new MemberCardsModel($dbc);
        $cards->card_no($this->card_no);
        $cards->load();
        $cards->pushToLanes();


        $prep = $dbc->prepare('
            SELECT webServiceUrl FROM Stores WHERE hasOwnItems=1 AND storeID<>?
            ');
        $res = $dbc->execute($prep, array(\FannieConfig::config('STORE_ID')));
        while ($row = $dbc->fetchRow($res)) {
            $client = new \Datto\JsonRpc\Http\Client($row['webServiceUrl']);
            $client->query(time(), 'COREPOS\\Fannie\\API\\webservices\\FannieMemberLaneSync', array('id'=>$this->card_no));
            $client->send();
        }

        header('Location: PIMemberPage.php?id='.$this->card_no);

        return false;
    }

    protected function get_id_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $limitedEdit = $this->auth_mode == 'Full' ? False : True;
        ob_start();
        echo '<form action="PIMemberPage.php" ';
        if (FormLib::get_form_value('edit', False) === False)
            echo 'method="get">';
        else
            echo 'method="post">';
        echo '<input type="hidden" name="id" value="'.$this->card_no.'" />';
        echo "<table>";
        echo "<tr>";
        echo "<td class=\"greenbg yellowtxt\">Owner Num</td>";
        echo "<td class=\"greenbg yellowtxt\">".$this->card_no."</td>";

        $status = $this->account['activeStatus'];
        if ($status == '') {
            $status = $this->account['memberStatus'];
        }
        switch ($status) {
            case 'PC':
                $status = 'ACTIVE';
                break;
            case 'REG':
                $status = 'NONMEM';
                break;
            case 'INACT2':
                $status = 'TERM (PENDING)';
                break;
        }

        if (isset($this->__models['suspended'])){
            echo "<td bgcolor='#cc66cc'>$status</td>";
            echo "<td colspan=1>";
            if ($this->__models['suspended']->reason() != '')
                echo $this->__models['suspended']->reason();
            else {
                $reasons = new ReasoncodesModel($dbc);
                foreach($reasons->find('mask') as $r){
                    if (((int)$r->mask() & (int)$this->__models['suspended']->reasoncode()) != 0){
                        echo $r->textStr().' ';
                    }
                }
            }
            echo '<input type="hidden" name="suspended" value="1" />';
            echo '</td>';
        }
        else {
            echo '<input type="hidden" name="suspended" value="0" />';
            echo "<td>$status</td>";
        }
        echo "<td colspan=2><a href=PISuspensionPage.php?id=".$this->card_no.">History</a>";
        if ($this->auth_mode == 'Full')
            echo '&nbsp;&nbsp;&nbsp;<a href="PISuspensionPage.php?edit=1&id='.$this->card_no.'">Change Status</a>';
        else if ($this->auth_mode == 'Limited' && isset($this->__models['suspended']) && $this->__models['suspended']->reasoncode() == 16){
            echo '&nbsp;&nbsp;&nbsp;<a href="PISuspensionPage.php?fixaddress=1&id='.$this->card_no.'"
                onclick="return confirm(\'Address is correct?\');">Address Corrected</a>';
        }
        echo '</td>';
        echo "<td><a href=\"{$FANNIE_URL}ordering/clearinghouse.php?card_no=".$this->card_no."\">Special Orders</a></td>";
        if (FannieAuth::validateUserQuiet('GiveUsMoney')) {
            echo "<td><a href=\"{$FANNIE_URL}modules/plugins2.0/GiveUsMoneyPlugin/GumMainPage.php?id=".$this->card_no."\">Owner Loans</a></td>";
        }
        echo "</tr>";

        echo "<tr>";
        echo '<input type="hidden" name="customerID" value="' . $this->primary_customer['customerID'] . '" />';
        echo "<td class=\"yellowbg\">First Name: </td>";
        echo '<td>'.$this->text_or_field('FirstName',$this->primary_customer['firstName']).'</td>';
        echo "<td class=\"yellowbg\">Last Name: </td>";
        echo '<td>'.$this->text_or_field('LastName',$this->primary_customer['lastName']).'</td>';
        echo '</tr>';

        echo "<tr>";
        echo "<td class=\"yellowbg\">Address1: </td>";
        echo '<td>'.$this->text_or_field('address1',$this->account['addressFirstLine']).'</td>';
        echo "<td class=\"yellowbg\">Gets mailings: </td>";
        echo '<td>'.$this->text_or_select('mailflag',$this->account['contactAllowed'],
                    array(1,0), array('Yes','No')).'</td>';
        echo "</tr>";

        echo "<tr>";
        echo "<td class=\"yellowbg\">Address2: </td>";
        echo '<td>'.$this->text_or_field('address2',$this->account['addressSecondLine']).'</td>';
        echo "<td class=\"yellowbg\">UPC: </td>";
        echo '<td colspan=\"2\">'.$this->text_or_field('upc',$this->account['idCardUPC']).'</td>';
        echo "</tr>";

        echo "<tr>";
        echo "<td class=\"yellowbg\">City: </td>";
        echo '<td>'.$this->text_or_field('city',$this->account['city']).'</td>';
        echo "<td class=\"yellowbg\">State: </td>";
        echo '<td>'.$this->text_or_field('state',$this->account['state']).'</td>';
        echo "<td class=\"yellowbg\">Zip: </td>";
        echo '<td>'.$this->text_or_field('zip',$this->account['zip']).'</td>';
        echo "</tr>";

        echo "<tr>";
        echo "<td class=\"yellowbg\">Phone Number: </td>";
        echo '<td>'.$this->text_or_field('phone',$this->primary_customer['phone']).'</td>';
        echo "<td class=\"yellowbg\">Start Date: </td>";
        $start = $this->account['startDate'];
        if (strstr($start,' ') !== False) list($start,$junk) = explode(' ',$start,2);
        if ($start == '1900-01-01') {
            echo '<input type="hidden" name="nonBlankStart" value="' . $start . '" />';
        }
        if ($start == '1900-01-01' || $start == '0000-00-00') $start = '';
        echo '<td>'.$this->text_or_field('start_date',$start,array(),$limitedEdit).'</td>';
        echo "<td class=\"yellowbg\">End Date: </td>";
        $end = $this->account['endDate'];
        if (strstr($end,' ') !== False) list($end,$junk) = explode(' ',$end,2);
        if ($end == '1900-01-01' || $end == '0000-00-00') $end = '';
        echo '<td>'.$this->text_or_field('end_date',$end,array(),$limitedEdit).'</td>';
                echo "</tr>";

        echo "<tr>";
        echo "<td class=\"yellowbg\">Alt. Phone: </td>";
        echo '<td>'.$this->text_or_field('phone2',$this->primary_customer['altPhone']).'</td>';
        echo "<td class=\"yellowbg\">E-mail: </td>";
        echo '<td>'.$this->text_or_field('email',$this->primary_customer['email']).'</td>';
        echo "</tr>";

                echo "<tr>";
        echo "<td class=\"yellowbg\">Stock Purchased: </td>";
        echo "<td>".sprintf('%.2f',$this->__models['equity']->payments()).'</td>';
        echo "<td class=\"yellowbg\">Mem Type: </td>";
        $labels = array();
        $opts = array();
        $memtypes = new MemtypeModel($dbc);
        foreach($memtypes->find('memtype') as $mt){
            $labels[] = $mt->memDesc();
            $opts[] = $mt->memtype();
        }
        echo '<td>'.$this->text_or_select('memType',$this->account['customerTypeID'],
                $opts, $labels,array(),$limitedEdit).'</td>';
        echo "<td class=\"yellowbg\">Discount: </td>";
        echo '<td>'.$this->primary_customer['discount'].'</td>';
        echo "</tr>";

        echo "<tr>";
        echo "<td class=\"yellowbg\">Charge Limit: </td>";
        echo '<td>'.$this->text_or_field('chargelimit',$this->account['chargeLimit'],
                array(),$limitedEdit).'</td>';
        echo "<td class=\"yellowbg\">Current Balance: </td>";
        echo '<td>'.sprintf('%.2f',$this->__models['ar']->balance()).'</td>';
        echo "<td class=\"yellowbg\">Referral:</td>";
        $opts = array(0);
        $labels = array('n/a');
        foreach ($this->emps as $e) {
            $opts[] = $e['emp_no'];
            $labels[] = $e['emp_no'] . ' ' . $e['FirstName'];
        }
        echo '<td>' . $this->text_or_select('commissioned', $this->commissioned, $opts, $labels) . '</td>';
        echo "</tr>";

        echo "<tr class=\"yellowbg\"><td colspan=6></td></tr>";

                echo "<tr>";
        echo '<td colspan="2" class="greenbg yellowtxt">Additional household members</td>';
        echo '<td></td>';
        echo '<td class="greenbg yellowtxt">Additional Notes</td>';
        echo "<td><a href=PINoteHistoryPage.php?id=".$this->card_no.">Notes history</a></td>";
                echo "</tr>";

                echo "<tr>";
        echo '<td></td>';
        echo '<td class="yellowbg">First Name</td>';
        echo '<td class="yellowbg">Last Name</td>';
        echo "<td colspan=4 width=\"300px\" valign=\"top\" rowspan=8>";
        echo $this->text_or_area('notetext',$this->__models['note'],
                array('rows'=>7,'cols'=>50), 2);
        echo "</td>";
        echo '</tr>';

        $i=0;
        foreach ($this->account['customers'] as $c) {
            if ($c['accountHolder']) {
                continue;
            }
            echo '<tr>';
            echo '<td class="yellowbg">'.($i+1).'</td>';
            echo '<td>'.$this->text_or_field('fn[]',$c['firstName']).'</td>';
            echo '<td>'.$this->text_or_field('ln[]',$c['lastName']).'</td>';
            echo '<input type="hidden" name="hhID[]" value="' . $c['customerID'] . '" />';
            $i++;
        }
        for ($i; $i<3; $i++) {
            echo '<tr>';
            echo '<td class="yellowbg">'.($i+1).'</td>';
            echo '<td>'.$this->text_or_field('fn[]','').'</td>';
            echo '<td>'.$this->text_or_field('ln[]','').'</td>';
            echo '<input type="hidden" name="hhID[]" value="0" />';

        }
        echo '</tr>';

        echo '<tr>';
        echo '<td colspan="3">';
        if (FormLib::get_form_value('edit',False) === False){
            if ($this->current_user){
                echo '<input type="hidden" name="edit" />';
                echo '<input type="submit" value="Edit Member" />';
            }
            else {
                echo '<input type="hidden" name="login" />';
                echo '<input type="submit" value="Log In" />';
            }
            echo '&nbsp;&nbsp;';
            echo '<a href="PIMemberPage.php?id=' . ($this->card_no - 1) . '">Prev Mem</a>';
            echo '&nbsp;&nbsp;';
            echo '<a href="PIMemberPage.php?id=' . ($this->card_no + 1) . '">Next Mem</a>';
        }
        else
            echo '<input type="submit" value="Save Member" />';
        echo '</td>';

        echo '</tr>';

        echo "</table>";
        if (FormLib::get('edit', false) !== false) {
            $this->addScript('edit.js');
            $this->addOnloadCommand("\$('input').keydown(piJS.nosubmit);\n");
            $this->addOnloadCommand("\$('input[name=FirstName]').focus();\n");
        }
        return ob_get_clean();
    }

    private function text_or_field($name, $value, $attributes=array(), $limited=False){
        if (FormLib::get_form_value('edit',False) === False || $limited)
            return $value;
        
        $tag = '<input type="text" name="'.$name.'" value="'.$value.'"';
        foreach($attributes as $key=>$val)
            $tag .= ' '.$key.'="'.$val.'"';
        $tag .= '/>';
        return $tag;
    }

    private function text_or_select($name, $value, $opts, $labels=array(), $attributes=array(), $limited=False){
        if (FormLib::get_form_value('edit',False) === False || $limited){
            if (!empty($labels)){
                for($i=0;$i<count($opts);$i++){
                    if ($opts[$i] == $value)
                        return (isset($labels[$i])?$labels[$i]:$value);
                }
            }
            else
                return $value;
        }
    
        $tag = '<select name="'.$name.'"';
        foreach($attributes as $key=>$val)
            $tag .= ' '.$key.'="'.$val.'"';
        $tag .= '>';
        for($i=0;$i<count($opts);$i++){
            $tag .= sprintf('<option value="%s" %s>%s</option>',
                $opts[$i],
                ($opts[$i]==$value ? 'selected': ''),
                (isset($labels[$i]) ? $labels[$i] : $opts[$i])
            );
        }
        $tag .= '</select>';
        return $tag;
    }

    /**
      swap values
        0 - make no changes
        1 - value has newlines
        2 - value has br tags
    */
    private function text_or_area($name, $value, $attributes=array(), $swap=0, $limited=False){
        if (FormLib::get_form_value('edit',False) === False || $limited){
            switch($swap){
            case 1:
                return str_replace("\n",'<br />',$value);
                break;
            case 2:
            case 0:
            default:
                return $value;
                break;
            }
        }

        $ret = '<textarea name="'.$name.'"';
        foreach($attributes as $attr => $val){
            $ret .= ' '.$attr.'="'.$val.'"';
        }
        $ret .= '>';
        switch($swap){
        case 2:
            $value = str_replace('<br />',"\n",$value);
            break;
        case 1:
        case 0:
        default:
            $value = $value;
            break;
        }
        $ret .= $value;
        $ret .= '</textarea>';

        $ret .= '<input type="hidden" name="_'.$name.'" value="';
        $ret .= base64_encode($value);
        $ret .= '" />';     

        return $ret;
    }

    public function css_content(){
        return '
            .greenbg { background: #006633; }
            .greentxt { color: #006633; }
            .yellowbg { background: #FFFF33; }
            .yellowtxt { color: #FFFF33; }
        ';
    }
}

FannieDispatch::conditionalExec();

