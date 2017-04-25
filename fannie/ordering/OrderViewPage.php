<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

if (!class_exists('SoPoBridge')) {
    include(__DIR__ . '/SoPoBridge.php');
}
if (!class_exists('OrderNotifications')) {
    include(__DIR__ . '/OrderNotifications.php');
}

class OrderViewPage extends FannieRESTfulPage
{
    protected $header = 'View Order';
    protected $title = 'View Order';
    protected $must_authenticate = true;
    public $description = '[View Special Order] lists and/or edits an active special order';
    public $page_set = 'Special Orders';

    public function preprocess()
    {
        if (php_sapi_name() !== 'cli' && !headers_sent() && session_id() == '') {
            session_start();
        }

        $this->__routes[] = 'get<orderID>';
        $this->__routes[] = 'get<orderID><items>';
        $this->__routes[] = 'get<orderID><customer>';
        $this->__routes[] = 'post<orderID><memNum><upc><cases>';
        $this->__routes[] = 'post<orderID><transID><dept>';
        $this->__routes[] = 'post<orderID><transID><qty>';
        $this->__routes[] = 'post<orderID><transID><toggleStaff>';
        $this->__routes[] = 'post<orderID><transID><toggleMemType>';
        $this->__routes[] = 'post<orderID><togglePrint>';
        $this->__routes[] = 'post<orderID><noteDept><noteText><ph1><ph2><email>';
        $this->__routes[] = 'delete<orderID><transID>';
        $this->addRoute('post<orderID><description><srp><actual><qty><dept><unitPrice><vendor><transID><changed>');
        $this->addRoute('post<addPO><orderID><transID><storeID>');

        return parent::preprocess();
    }

    protected function post_addPO_orderID_transID_storeID_handler()
    {
        $bridge = new SoPoBridge($this->connection, $this->config);
        $poID = $bridge->addItemToPurchaseOrder($this->orderID, $this->transID, $this->storeID);
        if ($poID) {
            echo json_encode(array('error'=>false, 'poID'=>$poID));
        } else {
            echo json_encode(array('error'=>true));
        }

        return false;
    }

    protected function post_orderID_transID_dept_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $upP = $dbc->prepare('
            UPDATE PendingSpecialOrder
            SET department=?
            WHERE order_id=?
                AND trans_id=?'); 
        $upR = $dbc->execute($upP, array($this->dept, $this->orderID, $this->transID));

        $desc = FormLib::get('newdesc');
        if (!empty($desc)) {
            $brand = FormLib::get('newbrand');
            if (!empty($brand)) {
                $desc = $brand . ' ' . $desc;
            }
            $upP = $dbc->prepare('
                UPDATE PendingSpecialOrder
                SET description=?
                WHERE order_id=?
                    AND trans_id=?'); 
            $upR = $dbc->execute($upP, array($desc, $this->orderID, $this->transID));
        }

        $qtyType = FormLib::get('newQtyType', 'Cases');
        if (strtolower($qtyType) !== 'cases') {
            $upP = $dbc->prepare('
                UPDATE PendingSpecialOrder
                SET quantity=ItemQtty
                WHERE order_id=?
                    AND trans_id=?'); 
            $upR = $dbc->execute($upP, array($this->orderID, $this->transID));

            $upP = $dbc->prepare('
                UPDATE PendingSpecialOrder
                SET ItemQtty=1
                WHERE order_id=?
                    AND trans_id=?'); 
            $upR = $dbc->execute($upP, array($this->orderID, $this->transID));
        }

        return $this->get_orderID_items_handler();
    }

    protected function post_orderID_transID_qty_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $upP = $dbc->prepare('
            UPDATE PendingSpecialOrder
            SET quantity=?
            WHERE order_id=?
                AND trans_id=?'); 
        $upR = $dbc->execute($upP, array($this->qty, $this->orderID, $this->transID));
        $this->reprice($this->orderID, $this->transID);

        return $this->get_orderID_items_handler();
    }

    protected function post_orderID_description_srp_actual_qty_dept_unitPrice_vendor_transID_changed_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $basicP = $dbc->prepare('
            UPDATE PendingSpecialOrder
            SET description=?,
                department=?,
                mixMatch=?,
                total=?,
                unitPrice=?,
                quantity=?
            WHERE order_id=?
                AND trans_id=?
        ');
        $basicR = $dbc->execute($basicP, array(
            $this->description,
            $this->dept,
            $this->vendor,
            $this->actual,
            $this->unitPrice,
            $this->qty,
            $this->orderID,
            $this->transID,
        ));

        if ($this->changed == 'srp' || $this->changed == 'qty' || $this->changed == 'unitPrice') {
            $info = $this->reprice($this->orderID, $this->transID, ($this->changed == 'srp' ? $this->srp : false));
        } else {
            $info = array('regPrice' => $this->srp, 'total' => $this->actual);
        }

        $fetchP = $dbc->prepare("SELECT ROUND(100*((regPrice-total)/regPrice),0)
            FROM PendingSpecialOrder WHERE trans_id=? AND order_id=?");
        $info['discount'] = $dbc->getValue($fetchP, array($this->transID, $this->orderID));
        echo json_encode($info);

        return false;
    }

    protected function post_orderID_togglePrint_handler()
    {
        $user = $this->current_user;
        $cachepath = sys_get_temp_dir()."/ordercache/";
        $prints = unserialize(file_get_contents("{$cachepath}{$user}.prints"));
        if (isset($prints[$this->orderID])) {
            unset($prints[$this->orderID]);
        } else {
            $prints[$this->orderID] = array();
        }
        $fptr = fopen("{$cachepath}{$user}.prints",'w');
        fwrite($fptr,serialize($prints));
        fclose($fptr);

        return false;
    }

    protected function post_orderID_transID_toggleStaff_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));

        $upP = $dbc->prepare('
            UPDATE PendingSpecialOrder 
            SET staff = (staff+1)%2
            WHERE order_id=? 
                AND trans_id=?');
        $dbc->execute($upP, array($this->orderID, $this->transID));

        $json = array();
        $email = new OrderNotifications($dbc);
        $json['sentEmail'] = $email->itemArrivedEmail($this->orderID, $this->transID);
        echo json_encode($json);

        return false;
    }

    protected function post_orderID_transID_toggleMemType_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));

        $upP = $dbc->prepare('
            UPDATE PendingSpecialOrder 
            SET memType = (memType+1)%2
            WHERE order_id=? 
                AND trans_id=?');
        $dbc->execute($upP, array($this->orderID, $this->transID));

        return false;
    }

    protected function delete_orderID_transID_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $delP = $dbc->prepare('
            DELETE FROM PendingSpecialOrder
            WHERE order_id=?
                AND trans_id=?');
        $delR = $dbc->execute($delP, array($this->orderID, $this->transID));

        return $this->get_orderID_items_handler();
    }

    protected function post_orderID_memNum_upc_cases_handler()
    {
        if (is_numeric($this->cases)) {
            $this->cases = (int)$this->cases;
        } else {
            $this->cases = 1;
        }
        $result = $this->addUPC($this->orderID, $this->memNum, $this->upc, $this->cases);
        if (!is_numeric($this->upc)) {
            echo $this->getDeptForm($this->orderID, $result[1], $result[2]);
        } elseif ($result[0] === false) {
            return $this->get_orderID_items_handler();
        } else {
            echo $this->getQtyForm($this->orderID, $result[0], $result[1], $result[2]);
        }

        return false;
    }

    protected function post_orderID_noteDept_noteText_ph1_ph2_email_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));

        $soModel = new SpecialOrdersModel($dbc);
        $soModel->specialOrderID($this->orderID);
        $soModel->noteSuperID($this->noteDept);
        $soModel->notes($this->noteText);
        $soModel->phone($this->ph1);
        $soModel->altPhone($this->ph2);
        $soModel->email($this->email);
        $soModel->sendEmails(FormLib::get('contactBy'));

        if (FormLib::get('fn', false) !== false) {
            $soModel->firstName(FormLib::get('fn'));
        }
        if (FormLib::get('ln', false) !== false) {
            $soModel->lastName(FormLib::get('ln'));
        }
        if (FormLib::get('street', false) !== false) {
            $soModel->street(FormLib::get('street'));
        }
        if (FormLib::get('city', false) !== false) {
            $soModel->city(FormLib::get('city'));
        }
        if (FormLib::get('state', false) !== false) {
            $soModel->state(FormLib::get('state'));
        }
        if (FormLib::get('zip', false) !== false) {
            $soModel->zip(FormLib::get('zip'));
        }
        $json = array();
        $json['saved'] = $soModel->save() ? true : false;
        echo json_encode($json);

        return false;
    }

    protected function get_orderID_customer_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();
        $orderID = $this->orderID;
        try {
            $memNum = $this->form->memNum;
        } catch (Exception $ex) {
            $memNum = '0';
        }
        $canEdit = FannieAuth::validateUserQuiet('ordering_edit');

        if (empty($orderID)) {
            $orderID = $this->createEmptyOrder();
        }

        $names = array();
        $personNum = 1;
        $status_row = array(
            'Type' => 'REG',
            'status' => ''
        );

        $dbc->selectDB($this->config->get('TRANS_DB'));
        $orderModel = new SpecialOrdersModel($dbc);
        $orderModel->specialOrderID($orderID);
        $orderModel->load();
        $dbc->selectDB($this->config->get('OP_DB'));

        // detect member UPC entry
        if ($memNum > 9999999) {
            $cards = new MemberCardsModel($dbc);
            $cards->upc(BarcodeLib::padUPC($memNum));
            $memNum = '';
            foreach ($cards->find() as $c) {
                $memNum = $c->card_no();
                break;
            }
        }

        // look up member id if applicable
        if ($memNum === "0") {
            $findMem = $dbc->prepare("SELECT card_no,voided FROM {$TRANS}PendingSpecialOrder WHERE order_id=?");
            $memR = $dbc->execute($findMem, array($orderID));
            if ($dbc->numRows($memR) > 0) {
                $memW = $dbc->fetchRow($memR);
                $memNum = $memW['card_no'];
                $personNum = $memW['voided'];
            }
        } elseif ($memNum == "") {
            $prep = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET card_no=?,voided=0
                WHERE order_id=?");
            $dbc->execute($prep,array(0,$orderID));
        } else {
            $prep = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET card_no=?
                WHERE order_id=?");
            $dbc->execute($prep,array($memNum,$orderID));

            // clear contact fields if member number changed
            // so that defaults are reloaded from meminfo
            $dbc->selectDB($this->config->get('TRANS_DB'));
            $orderModel->street('');
            $orderModel->phone('');
            $orderModel->save();
            $orderModel->specialOrderID($orderID);
            $orderModel->load();
            $dbc->selectDB($this->config->get('OP_DB'));

            // look up personnum, correct if it hasn't been set
            $pendQ = $dbc->prepare("SELECT voided FROM {$TRANS}PendingSpecialOrder
                WHERE order_id=?");
            $personNum = $dbc->getValue($pendQ,array($orderID));
            if ($personNum == 0) {
                $personNum = 1;
                $upP = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET voided=?
                    WHERE order_id=?");
                $upR = $dbc->execute($upP,array($personNum,$orderID));
            }
        }

        if ($memNum != 0) {
            $custdata = new CustdataModel($dbc);
            $custdata->CardNo($memNum);
            foreach ($custdata->find('personNum') as $c) {
                $names[$c->personNum()] = array($c->FirstName(), $c->LastName());
            }

            // load member contact info into order
            // on first go so it can be edited separately
            $current_street = $orderModel->street();
            $current_phone = $orderModel->phone();
            if (empty($current_street) && empty($current_phone)) {
                $contactQ = $dbc->prepare("SELECT street,city,state,zip,phone,email_1,email_2
                        FROM meminfo WHERE card_no=?");
                $contactR = $dbc->execute($contactQ, array($memNum));
                if ($dbc->num_rows($contactR) > 0) {
                    $contact_row = $dbc->fetch_row($contactR);

                    $dbc->selectDB($this->config->get('TRANS_DB'));
                    $orderModel->street($contact_row['street']);
                    $orderModel->city($contact_row['city']);
                    $orderModel->state($contact_row['state']);
                    $orderModel->zip($contact_row['zip']);
                    $orderModel->phone($contact_row['phone']);
                    $orderModel->altPhone($contact_row['email_2']);
                    $orderModel->email($contact_row['email_1']);
                    $orderModel->save();
                    $orderModel->specialOrderID($orderID);
                    $orderModel->load();
                
                    $dbc->selectDB($this->config->get('OP_DB'));
                }
            }

            $custdata->personNum($personNum);
            if ($custdata->load()) {
                $status_row['Type'] = $custdata->Type();
                if ($status_row['Type'] == 'INACT') {
                    $status_row['status'] = 'Inactive';
                } elseif ($status_row['Type'] == 'INACT2') {
                    $status_row['status'] = 'Inactive';
                } elseif ($status_row['Type'] == 'TERM') {
                    $status_row['status'] = 'Terminated';
                }
            }
        } 

        $prep = $dbc->prepare("SELECT entry_date FROM {$TRANS}SpecialOrderHistory 
                WHERE order_id=? AND entry_type='CONFIRMED'");
        $confirm_date = $dbc->getValue($prep, array($orderID));

        $callback = 2;
        $user = 'Unknown';
        $orderDate = "";
        $prep = $dbc->prepare("SELECT datetime,numflag,mixMatch FROM 
                {$TRANS}PendingSpecialOrder WHERE order_id=? AND trans_id=0");
        $res = $dbc->execute($prep, array($orderID));
        if ($dbc->num_rows($res) > 0) {
            list($orderDate,$callback,$user) = $dbc->fetch_row($res);
        }

        $status = array(
            0 => "New, No Call",
            3 => "New, Call",
            1 => "Called/waiting",
            2 => "Pending",
            4 => "Placed",
            5 => "Arrived"
        );
        $order_status = $orderModel->statusFlag();

        $ret = "";
        $ret .= sprintf('<input type="hidden" id="orderID" value="%d" />',$orderID);
        $ret .= '<div class="row form-inline"><div class="col-sm-4 text-left">';
        $ret .= sprintf('<b>Owner Number</b>: <input type="text" size="6"
                id="memNum" value="%s" class="form-control price-field input-sm" 
                />',($memNum==0?'':$memNum));
        $ret .= '<br />';
        $ret .= '<b>Owner</b>: '.($status_row['Type']=='PC'?'Yes':'No');
        $ret .= sprintf('<input type="hidden" id="isMember" value="%s" />',
                $status_row['Type']);
        $ret .= '<br />';
        if (!empty($status_row['status'])) {
            $ret .= '<b>Account status</b>: '.$status_row['status'];
            $ret .= '<br />';
        }
        $ret .= '</div><div class="col-sm-4 text-center">';

        if ($canEdit) {
            $ret .= '<b>Status</b>: ';
            $ret .= '<select id="orderStatus" class="form-control input-sm">';
            foreach($status as $k => $v) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                            ($k == $order_status ? 'selected' : ''),
                            $k, $v);
            }
            $ret .= '</select><p />';
        }
        if ($this->config->get('STORE_MODE') === 'HQ') {
            $ret .= '<b>Store</b>: ';
            $ret .= '<select id="orderStore" class="form-control input-sm">';
            $ret .= '<option value="0">Choose...</option>';
            $stores = new StoresModel($dbc);
            $ret .= $stores->toOptions($orderModel->storeID());
            $ret .= '</select>';
        } else {
            $ret .= '<input type="hidden" id="orderStore" value="1" />';
        }
        $ret .= '</div><div class="col-sm-4 text-right">';

        $ret .= "<a href=\"\" class=\"btn btn-default btn-sm done-btn\">Done</a>";
        $username = FannieAuth::checkLogin();
        $prints = array();
        $cachepath = sys_get_temp_dir()."/ordercache/";
        if (file_exists("{$cachepath}{$username}.prints")) {
            $prints = unserialize(file_get_contents("{$cachepath}{$username}.prints"));
        } else {
            $fptr = fopen("{$cachepath}{$username}.prints",'w');
            fwrite($fptr,serialize($prints));
            fclose($fptr);
        }
        $ret .= sprintf('<br />Queue tags <input type="checkbox" %s class="print-cb" />',
                (isset($prints[$orderID])?'checked':''),
                $username,$orderID
            );
        $ret .= sprintf('<br /><a href="SpecialOrderTags.php?oids[]=%d" target="_tags%d">Print Now</a>',
                $orderID,$orderID);
        $ret .= '</div></div>';

        $extra = "";    
        $extra .= '<div class="row"><div class="col-sm-6 text-left">';
        $extra .= "<b>Taken by</b>: ".$user."<br />";
        $extra .= "<b>On</b>: ".date("M j, Y g:ia",strtotime($orderDate))."<br />";
        $extra .= '</div><div class="col-sm-6 text-right form-inline">';
        $extra .= '<b>Call to Confirm</b>: ';
        $extra .= '<select id="ctcselect" class="form-control input-sm">'; 
        $extra .= '<option value="2"></option>';
        if ($callback == 1) {
            $extra .= '<option value="1" selected>Yes</option>';    
            $extra .= '<option value="0">No</option>';  
        } else if ($callback == 0) {
            $extra .= '<option value="1">Yes</option>'; 
            $extra .= '<option value="0" selected>No</option>'; 
        } else {
            $extra .= '<option value="1">Yes</option>'; 
            $extra .= '<option value="0">No</option>';  
        }
        $extra .= '</select><br />';    
        $extra .= '<span id="confDateSpan">'.(!empty($confirm_date)?'Confirmed '.$confirm_date:'Not confirmed')."</span> ";
        $extra .= '<input type="checkbox" id="confirm-date" ';
        if (!empty($confirm_date)) $extra .= "checked";
        $extra .= ' /><br />';

        $extra .= "<a href=\"\" class=\"btn btn-default btn-sm done-btn\">Done</a>";
        $extra .= '</div></div>';

        $ret .= '<table class="table table-bordered">';

        // names
        if (empty($names)) {
            $ret .= sprintf('<tr><th>First Name</th><td>
                    <input type="text" id="t_firstName" name="fn"
                    class="form-control input-sm contact-field"
                    value="%s" 
                    /></td>',$orderModel->firstName());
            $ret .= sprintf('<th>Last Name</th><td><input 
                    type="text" id="t_lastName" value="%s" name="ln"
                    class="form-control input-sm contact-field"
                    /></td>',
                    $orderModel->lastName());
        } else {
            $ret .= '<tr><th>Name</th><td colspan="2"><select id="s_personNum"
                class="form-control input-sm">';
            foreach($names as $p=>$n) {
                $ret .= sprintf('<option value="%d" %s>%s %s</option>',
                    $p,($p==$personNum?'selected':''),
                    $n[0],$n[1]);
            }
            $ret .= '</select></td>';
            $ret .= '<td><a href="NewSpecialOrdersPage.php?card_no=' . $memNum . '">All Orders for this Account</a></td>';
        }
        $ret .= '<td colspan="4" class="form-inline">Notes For Department:
            <select id="nDept" class="form-control input-sm contact-field" 
                name="noteDept">
            <option value="0">Choose...</option>';
        $superQ = $dbc->prepare("select superID,super_name from MasterSuperDepts
            where superID > 0
            group by superID,super_name
            order by super_name");
        $superR = $dbc->execute($superQ);
        while($superW = $dbc->fetch_row($superR)) {
            $ret .= sprintf('<option value="%d" %s>%s</option>',
                $superW['superID'],
                ($superW['superID']==$orderModel->noteSuperID()?'selected':''),
                $superW['super_name']);
        }
        $ret .= "</select></td></tr>";

        $contactOpts = array(
            0 => 'Call',
            1 => 'Email',
            2 => 'Text (AT&T)',
            3 => 'Text (Sprint)',
            4 => 'Text (T-Mobile)',
            5 => 'Text (Verizon)',
            6 => 'Text (Google Fi)',
        );
        $contactHtml = '';
        foreach ($contactOpts as $id=>$val) {
            $contactHtml .= sprintf('<option %s value="%d">%s</option>',
                ($orderModel->sendEmails() == $id ? 'selected' : ''),
                $id, $val);
        }

        $ret .= sprintf('
            <tr>
                <th>Phone</th>
                <td>
                    <input type="text" id="t_ph1" name="ph1"
                        class="form-control input-sm contact-field"
                        value="%s" />
                </td>
                <th>Alt. Phone</th>
                <td>
                    <input type="text" id="t_ph2" value="%s" name="ph2"
                        class="form-control input-sm contact-field" />
                </td>
                <td rowspan="2" colspan="4">
                    <textarea id="nText" rows="5" cols="25" 
                        class="form-control input-sm contact-field" name="noteText"
                        >%s</textarea>
                </td>
            </tr>
            <tr>
                <th>E-mail</th>
                <td>
                    <input type="text" id="t_email" value="%s" 
                        class="form-control input-sm contact-field"
                        name="email" />
                </td>
                <th>Prefer</th>
                <td class="form-inline">
                    <select name="contactBy" class="form-control input-sm contact-field">
                        %s
                    </select>
                    <button class="btn btn-default btn-sm btn-test-send">Test Send</button>
                </td>
            </tr>
            <tr>
                <th>Address</th>
                <td><input type="text" class="form-control input-sm contact-field"
                    name="street" value="%s" /></td>
                <th>City</th>
                <td><input type="text" class="form-control input-sm contact-field"
                    name="city" value="%s" /></td>
                <td class="form-inline">
                    <strong>State</strong>
                    <input type="text" class="form-control input-sm contact-field"
                    name="state" value="%s" />
                    <strong>Zip</strong>
                    <input type="text" class="form-control input-sm contact-field"
                    name="zip" value="%s" />
                </td>
            </tr>',
            $orderModel->phone(), 
            $orderModel->altPhone(),
            $orderModel->notes(),
            $orderModel->email(),
            $contactHtml,
            $orderModel->street(),
            $orderModel->city(),
            $orderModel->state(),
            $orderModel->zip()
        );

        $ret .= '</table>';

        echo json_encode(array('customer'=>$ret, 'footer'=>$extra));

        return false;
    }

    private function addUPC($orderID, $memNum, $upc, $num_cases=1)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();

        $ins_array = $this->genericRow($orderID);
        $ins_array['upc'] = "$upc";
        $ins_array['card_no'] = "$memNum";
        $ins_array['trans_type'] = "I";
        $ins_array['ItemQtty'] = $num_cases;

        if (!class_exists('OrderItemLib')) {
            include(dirname(__FILE__) . '/OrderItemLib.php');
        }

        $mempricing = OrderItemLib::memPricing($memNum);

        $item = OrderItemLib::getItem($upc);
        $qtyReq = OrderItemLib::manualQuantityRequired($item);
        $item['department'] = OrderItemLib::mapDepartment($item['department']);
        if ($qtyReq !== false) {
            $item['caseSize'] = $qtyReq;
        }
        $unitPrice = OrderItemLib::getUnitPrice($item, $mempricing);
        $casePrice = OrderItemLib::getCasePrice($item, $mempricing);
        if ($unitPrice == $item['normal_price'] && !OrderItemLib::useSalePrice($item, $mempricing)) {
            $item['discounttype'] = 0;
        }

        $ins_array['upc'] = $item['upc'];
        $ins_array['quantity'] = $item['caseSize'];
        $ins_array['mixMatch'] = $item['vendorName'];
        $ins_array['description'] = substr($item['description'], 0, 32) . ' SO';
        $ins_array['department'] = $item['department'];
        $ins_array['discountable'] = $item['discountable'];
        $ins_array['discounttype'] = $item['discounttype'];
        $ins_array['cost'] = $item['cost'];
        $ins_array['unitPrice'] = $unitPrice;
        $ins_array['total'] = $casePrice * $num_cases;
        $ins_array['regPrice'] = $item['normal_price'] * $item['caseSize'] * $num_cases;

        $tidP = $dbc->prepare("SELECT MAX(trans_id),MAX(voided),MAX(numflag) 
                FROM {$TRANS}PendingSpecialOrder WHERE order_id=?");
        $tidR = $dbc->execute($tidP,array($orderID));
        $tidW = $dbc->fetch_row($tidR);
        $ins_array['trans_id'] = $tidW[0]+1;
        $ins_array['voided'] = $tidW[1];
        $ins_array['numflag'] = $tidW[2];

        $dbc->smartInsert("{$TRANS}PendingSpecialOrder",$ins_array);

        return array($qtyReq,$ins_array['trans_id'],$ins_array['description']);
    }

    private function createEmptyOrder()
    {
        global $FANNIE_OP_DB,$TRANS,$FANNIE_SERVER_DBMS, $FANNIE_TRANS_DB;
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();
        $user = FannieAuth::checkLogin();
        $orderID = 1;
        $values = ($this->config->get('SERVER_DBMS') != "MSSQL" ? "VALUES()" : "DEFAULT VALUES");
        $dbc->query('INSERT ' . $TRANS . 'SpecialOrders ' . $values);
        $orderID = $dbc->insertID();

        $ins_array = $this->genericRow($orderID);
        $ins_array['numflag'] = 2;
        $ins_array['mixMatch'] = $user;
        $dbc->smartInsert("{$TRANS}PendingSpecialOrder",$ins_array);

        $note_vals = array(
            'order_id'=>$orderID,
            'notes'=>"",
            'superID'=>0
        );

        $status_vals = array(
            'order_id'=>$orderID,
            'status_flag'=>3,
            'sub_status'=>time()
        );

        $dbc->selectDB($this->config->get('TRANS_DB'));
        $s_order = new SpecialOrdersModel($dbc);
        $s_order->specialOrderID($orderID);
        $s_order->statusFlag($status_vals['status_flag']);
        $s_order->subStatus($status_vals['sub_status']);
        $s_order->notes(trim($note_vals['notes'],"'"));
        $s_order->noteSuperID($note_vals['superID']);
        $s_order->save();
        $dbc->selectDB($this->config->get('TRANS_DB')); // switch back to previous

        $this->createContactRow($orderID);

        return $orderID;
    }

    private function genericRow($orderID)
    {
        return array(
        'order_id'=>$orderID,
        'datetime'=>date('Y-m-d H:i:s'),
        'emp_no'=>1001,
        'register_no'=>30,
        'trans_no'=>$orderID,
        'upc'=>'0',
        'description'=>"SPECIAL ORDER",
        'trans_type'=>"C",
        'trans_subtype'=>"",
        'trans_status'=>"",
        'department'=>0,
        'quantity'=>0,
        'scale'=>0,
        'cost'=>0,
        'unitPrice'=>0,
        'total'=>0,
        'regPrice'=>0,
        'tax'=>0,
        'foodstamp'=>0,
        'discount'=>0,
        'memDiscount'=>0,
        'discountable'=>1,
        'discounttype'=>0,
        'voided'=>0,
        'percentDiscount'=>0,
        'ItemQtty'=>0,
        'volDiscType'=>0,
        'volume'=>0,
        'VolSpecial'=>0,
        'mixMatch'=>0,
        'matched'=>0,
        'memType'=>0,
        'staff'=>0,
        'numflag'=>0,
        'charflag'=>"",   
        'card_no'=>0,
        'trans_id'=>0
        );
    }

    private function createContactRow($orderID)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();

        $so_order = new SpecialOrdersModel($dbc);
        $so_order->specialOrderID($orderID);
        $so_order->firstName('');
        $so_order->lastName('');
        $so_order->street('');
        $so_order->city('');
        $so_order->state('');
        $so_order->zip('');
        $so_order->phone('');
        $so_order->altPhone('');
        $so_order->email('');
        $so_order->save();

        $dbc->selectDB($this->config->get('OP_DB'));
    }

    protected function get_orderID_items_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        if (FannieAuth::validateUserQuiet('ordering_edit')) {
            $items = $this->editableItemList($this->orderID);
        } else {
            $items = $this->itemList($this->orderID);
        }
        
        echo <<<HTML
<form onkeydown="return event.keyCode != 13;">
<div class="form-inline">
    <div class="input-group">
        <span class="input-group-addon">UPC</span> 
        <input type="text" id="newupc" class="form-control input-sm" maxlength="35" />
    </div>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <div class="input-group">
        <span class="input-group-addon">Cases</span> 
        <input id="newcases" maxlength="2" value="1" size="3" class="form-control input-sm" />
    </div>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button type="submit" class="btn btn-default btn-sm">Add Item</button>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button type="button" class="btn btn-default btn-sm btn-search">Search</button>
</div>
</form>
<p />
{$items}
<p />
<b><a href="" onclick="\$('#manualclosebuttons').toggle();return false;">Manually close order</a></b>
<span id="manualclosebuttons" class="collapse"> as:
    <a href="" class="btn btn-default close-order-btn" data-close="7">Completed</a>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="" class="btn btn-default close-order-btn" data-close="8">Canceled</a>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="" class="btn btn-default close-order-btn" data-close="9">Inquiry</a>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br />
    <div class="alert alert-danger">
        Closing an order means slips for these items will no longer scan at the registers
    </div>
</span>
HTML;

        return false;
    }

    private function editableItemList($orderID)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();

        $deptP = $dbc->prepare("SELECT dept_no,dept_name FROM departments order by dept_no");
        $deptR = $dbc->execute($deptP);
        $depts = array(0=>'Unassigned');
        while($deptW = $dbc->fetch_row($deptR)) {
            $depts[$deptW['dept_no']] = $deptW['dept_name'];
        }

        $ret = '<table class="table table-bordered table-striped">';
        $ret .= '<tr><th>UPC</th><th>SKU</th><th>Description</th><th>Cases</th><th>SRP</th><th>Actual</th><th>Qty</th><th>Dept</th><th>&nbsp;</th></tr>';
        $prep = $dbc->prepare("SELECT o.upc,o.description,total,quantity,department,
            v.sku,ItemQtty,regPrice,o.discounttype,o.charflag,o.mixMatch,
            o.trans_id,o.unitPrice,o.memType,o.staff
            FROM {$TRANS}PendingSpecialOrder as o
                LEFT JOIN vendors AS n ON LEFT(n.vendorName, LENGTH(o.mixMatch)) = o.mixMatch
                LEFT JOIN vendorItems as v on o.upc=v.upc AND n.vendorID=v.vendorID
            WHERE order_id=? AND trans_type='I' 
            ORDER BY trans_id DESC");
        $res = $dbc->execute($prep, array($orderID));
        $num_rows = $dbc->num_rows($res);
        $prev_id = 0;
        $bridge = new SoPoBridge($dbc, $this->config);
        $store = $dbc->prepare("SELECT storeID FROM {$TRANS}SpecialOrders WHERE specialOrderID=?");
        $storeID = $dbc->getValue($store, array($orderID));
        $vendorsR = $dbc->query("SELECT vendorName FROM vendors WHERE inactive=0 ORDER BY vendorName");
        $vendors = array();
        while ($row = $dbc->fetchRow($vendorsR)) {
            $vendors[] = $row['vendorName'];
        }
        while ($row = $dbc->fetch_row($res)) {
            if ($row['trans_id'] == $prev_id) continue;
            $ret .= sprintf('
                    <tbody>
                    <tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td><input class="form-control input-sm item-field" name="description"
                        value="%s" /></td>
                    <td>%d</td>
                    <td><input size="5" class="form-control input-sm price-field item-field" id="srp%d" 
                        name="srp" value="%.2f" /></td>
                    <td><input size="5" class="form-control input-sm price-field item-field" id="act%d" 
                        value="%.2f" name="actual" /></td>
                    <td><input size="4" class="form-control input-sm price-field item-field" 
                        value="%.2f" name="qty" /></td>
                    <td><select class="form-control input-sm editDept item-field" 
                        name="dept">',
                    $row['upc'],
                    (!empty($row['sku'])?$row['sku']:'&nbsp;'),
                    $row['description'],
                    $row['ItemQtty'],
                    $row['trans_id'],$row['regPrice'],
                    $row['trans_id'],$row['total'],
                    $row['quantity']
                );
            foreach($depts as $id=>$name) {
                $ret .= sprintf('<option value="%d" %s>%d %s</option>',
                    $id,
                    ($id==$row['department']?'selected':''),
                    $id,$name);
            }
            $ret .= sprintf('</select></td>
                    <td><a href="" data-order="%d" data-trans="%d" 
                        class="btn btn-danger btn-xs btn-delete">%s</a></td>
                    </tr>',
                    $orderID,$row['trans_id'],
                    \COREPOS\Fannie\API\lib\FannieUI::deleteIcon()
            );
            $ret .= '<tr>';
            $ret .= sprintf('<td colspan="2" align="right" class="form-inline">Unit Price: 
                <input type="text" size="4" value="%.2f" id="unitp%d" name="unitPrice"
                class="form-control input-sm price-field item-field" /></td>',
                $row['unitPrice'],$row['trans_id']);

            /**
              If the current supplier entry matches a known vendor,
              display supplier options as a <select> from known vendors.
              Since entries may have been truncated in the database,
              matching the first 13 characters is sufficent.

              If the current entry does not match any known vendor,
              revert to showing supplier as a text box.
            */
            $supplierInput = '<select name="vendor" class="form-control input-sm item-field input-vendor">';
            $supplierInput .= '<option value=""></option>';
            $found = false;
            foreach ($vendors as $v) {
                if ($v == $row['mixMatch'] || substr($v, 0, 13) == $row['mixMatch']) {
                    $supplierInput .= "<option selected>{$v}</option>";
                    $found = true;
                } else {
                    $supplierInput .= "<option>{$v}</option>";
                }
            }
            if (!$found && $row['mixMatch'] != '') {
                $supplierInput = sprintf('<input type="text" value="%s" size="12"
                    class="form-control input-sm item-field input-vendor" name="vendor" maxlength="13" />',
                    $row['mixMatch']);
            } else {
                $supplierInput .= '</select>';
            }
            $ret .= sprintf('<td class="form-inline">Supplier: <input type="text" value="%s" size="12" 
                    class="form-control input-sm item-field input-vendor" name="vendor"
                    maxlength="26" 
                    /></td>',$row['mixMatch']);
            //$ret .= sprintf('<td class="form-inline">Supplier: %s</td>', $supplierInput);

            $ret .= '<td>Discount</td>';
            if ($row['discounttype'] == 1 || $row['discounttype'] == 2) {
                $ret .= '<td class="disc-percent" id="discPercent'.$row['trans_id'].'">Sale</td>';
            } else if ($row['regPrice'] != $row['total']) {
                $ret .= sprintf('<td class="disc-percent" id="discPercent%d">%d%%</td>',$row['upc'],
                    round(100*(($row['regPrice']-$row['total'])/$row['regPrice'])));
            } else {
                $ret .= '<td class="disc-percent" id="discPercent'.$row['upc'].'">0%</td>';
            }
            $ret .= sprintf('<td colspan="2">Printed: %s</td>',
                    ($row['charflag']=='P'?'Yes':'No'));
            $ret .= '<td colspan="2">';
            if ($storeID && $bridge->canPurchaseOrder($orderID, $row['trans_id'])) {
                $ordered = $bridge->findPurchaseOrder($orderID, $row['trans_id'], $storeID);
                if ($ordered) {
                    $ret .= '<a href="../purchasing/ViewPurchaseOrders.php?id=' . $ordered . '">In PO</a> | ';
                } else {
                    $ret .= sprintf('<span><a class="btn btn-default btn-xs add-po-btn" 
                        data-order="%d" data-trans="%d" data-store="%d">Add to PO</a></span> | ',
                        $orderID, $row['trans_id'], $storeID);
                }
            }
            if ($num_rows > 1) {
                $ret .= sprintf('<a href="" class="btn btn-default btn-xs"
                    onclick="orderView.doSplit(%d,%d);return false;">Split Item to New Order</a><br />
                    O <input type="checkbox" class="itemChkO" %s data-order="%d" data-trans="%d" />&nbsp;&nbsp;&nbsp;&nbsp;
                    A <input type="checkbox" class="itemChkA" %s data-order="%d" data-trans="%d" />',
                    $orderID,$row['trans_id'],
                    ($row['memType']>0?'checked':''),$orderID,$row['trans_id'],
                    ($row['staff']>0?'checked':''),$orderID,$row['trans_id']);
            }
            $ret .= '</td></tr>';
            $ret .= '<tr><td class="small" colspan="9"><span style="font-size:1;">&nbsp;</span>';
            $ret .= '<input type="hidden" name="transID" class="item-field" value="' . $row['trans_id'] . '" /></td></tr>';
            $ret .= '</tbody>';
            $prev_id=$row['trans_id'];
        }
        $ret .= '</table>';

        return $ret;
    }

    private function itemList($orderID,$table="PendingSpecialOrder")
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();

        $deptP = $dbc->prepare("SELECT dept_no,dept_name FROM departments order by dept_no");
        $deptR = $dbc->execute($deptP);
        $depts = array(0=>'Unassigned');
        while($deptW = $dbc->fetchRow($deptR)) {
            $depts[$deptW['dept_no']] = $deptW['dept_name'];
        }

        $prep = $dbc->prepare("SELECT o.upc,o.description,total,quantity,department,
            v.sku,ItemQtty,regPrice,o.discounttype,o.charflag,o.mixMatch,
            o.trans_id,o.unitPrice,o.memType,o.staff,o.discountable
            FROM {$TRANS}PendingSpecialOrder as o
                LEFT JOIN vendors AS n ON LEFT(n.vendorName, LENGTH(o.mixMatch)) = o.mixMatch
                LEFT JOIN vendorItems as v on o.upc=v.upc AND n.vendorID=v.vendorID
            WHERE order_id=? AND trans_type='I' 
            ORDER BY trans_id DESC");
        $res = $dbc->execute($prep, array($orderID));
        $num_rows = $dbc->num_rows($res);

        $ret = '<table class="table table-bordered table-striped">';
        $ret .= '<tr><th>UPC</th><th>Description</th><th>Cases</th><th>Pricing</th><th>&nbsp;</th></tr>';
            //<th>Est. Price</th>
            //<th>Qty</th><th>Est. Savings</th><th>&nbsp;</th></tr>';
        $prep = $dbc->prepare("SELECT o.upc,o.description,total,quantity,
            department,regPrice,ItemQtty,discounttype,trans_id FROM {$TRANS}$table as o
            WHERE order_id=? AND trans_type='I'");
        $res = $dbc->execute($prep, array($orderID));
        while($w = $dbc->fetch_row($res)) {
            $pricing = "Regular";
            if ($w['discounttype'] == 1) {
                $pricing = "Sale";
            } elseif($w['regPrice'] != $w['total']) {
                if ($w['discounttype']==2) {
                    $pricing = "Sale";
                } else {
                    $pricing = "% Discount";
                }
            } elseif ($w['discountable'] == 0) {
                $pricing = _('Basics');
            }
            $ret .= sprintf('<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%d</td>
                    <td>%s</td>
                    <td><a href="" data-order="%d" data-trans="%d"
                        class="btn btn-danger btn-xs btn-delete">%s</a></td>
                    </tr>',
                    $w['upc'],
                    $w['description'],
                    $w['ItemQtty'],
                    $pricing,
                    $orderID,$w['trans_id'],
                    \COREPOS\Fannie\API\lib\FannieUI::deleteIcon()
                );
        }
        $ret .= '</table>';

        return $ret;
    }



    private function getQtyForm($orderID,$default,$transID,$description)
    {
        return <<<HTML
<i>This item ({$description}) requires a quantity</i><br />
<form data-order="{$orderID}" data-trans="{$transID}">
    <div class="form-inline">
        <label>Qty</label>: <input type="text" id="newqty" 
            class="form-control input-sm" value="{$default}" maxlength="3" size="4" />
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <button type="submit" class="btn btn-default">Enter Qty</button>
    </div>
</form>
HTML;
    }

    private function getDeptForm($orderID,$transID,$description)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();
        $prep = $dbc->prepare("select super_name,
            CASE WHEN MIN(map_to) IS NULL THEN MIN(m.dept_ID) ELSE MIN(map_to) END
            from MasterSuperDepts
            as m left join {$TRANS}SpecialOrderDeptMap as s
            on m.dept_ID=s.dept_ID
            where m.superID > 0
            group by super_name ORDER BY super_name");
        $res = $dbc->execute($prep);
        $opts = '';
        while ($row = $dbc->fetchRow($res)) {
            $opts .= sprintf('<option value="%d">%s</option>',$row[1],$row[0]);
        }
        $current = $dbc->prepare("
            SELECT description, ItemQtty
            FROM {$TRANS}PendingSpecialOrder
            WHERE order_id=?
                AND trans_id=?");
        $info = $dbc->getRow($current, array($orderID, $transID));

        return <<<HTML
<i>This item ({$description}) requires additional information</i><br />
<form data-order="{$orderID}" data-trans="{$transID}">
    <div class="form-inline more-item-info">
        <div class="form-group">
            <label>Brand</label>
            <input type="text" name="newbrand" id="newbrand" class="form-control input-sm" />
        </div>
        <div class="form-group">
            <label>Description</label>
            <input type="text" name="newdesc" value="{$info['description']}" class="form-control input-sm" />
        </div>
        <div class="form-group">
            <label>Qty {$info['ItemQtty']} is</label>
            <select name="newQtyType" class="form-control input-sm">
                <option>Cases</option>
                <option>Eaches</option>
            </select>
        </div>
        <div class="form-group">
            <label>Dept.</label>
            <select id="newdept" name="dept" class="form-control input-sm">
                 <option value="">Choose...</option>
                {$opts}
            </select>
        </div>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <button type="submit" class="btn btn-default">Add Item</button>
    </div>
</form>
HTML;
    }

    private function reprice($oid,$tid,$reg=false)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();

        $query = $dbc->prepare("SELECT o.unitPrice,o.itemQtty,o.quantity,o.discounttype,
            c.type,c.memType,o.regPrice,o.total,o.discountable,o.cost,o.card_no
            FROM {$TRANS}PendingSpecialOrder AS o LEFT JOIN custdata AS c ON
            o.card_no=c.CardNo AND c.personNum=1
            WHERE order_id=? AND trans_id=?");
        $response = $dbc->execute($query, array($oid,$tid));
        $row = $dbc->fetch_row($response);

        $regPrice = $row['itemQtty']*$row['quantity']*$row['unitPrice'];
        if ($reg !== false) {
            $regPrice = $reg;
        }
        $total = $regPrice;
        if (!class_exists('OrderItemLib')) {
            include(dirname(__FILE__) . '/OrderItemLib.php');
        }
        if ($row['discountable'] != 0 && $row['discounttype'] == 0) {
            $mempricing = OrderItemLib::memPricing($row['card_no']);
            // create fake item to re-apply rules for marking up/down
            $item = array('normal_price' => $regPrice, 
                'cost'=>$row['cost']*$row['itemQtty']*$row['quantity']);
            $total = OrderItemLib::markUpOrDown($item, $mempricing);
        }

        if ($row['unitPrice'] == 0 || $row['quantity'] == 0) {
            $regPrice = $row['regPrice'];
            $total = $row['total'];
        }

        $query = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET
                total=?,regPrice=?
                WHERE order_id=? AND trans_id=?");
        $dbc->execute($query, array($total,$regPrice,$oid,$tid));

        return array(
            'regPrice'=>sprintf("%.2f",$regPrice),
            'total'=>sprintf("%.2f",$total)
        );
    }

    protected function get_handler()
    {
        $orderID = $this->createEmptyOrder();

        return filter_input(INPUT_SERVER, 'PHP_SELF') . '?orderID=' . $orderID;
    }

    // this shouldn't occur unless something goes wonky creating the new order
    protected function get_view()
    {
        return '<div class="alert alert-danger">No Order Specified</div>';
    }

    protected function get_orderID_view()
    {
        $orderID = (int)$this->orderID;
        if ($orderID === 0) {
            return '<div class="alert alert-danger">Invalid order. <a href="OrderViewPage.php">Create new order</a>?</div>';
        }
        $refer = filter_input(INPUT_SERVER, 'HTTP_REFERER');
        $return_path = ($refer && strstr($refer,'fannie/ordering/NewSpecialOrdersPage.php')) ? $refer : '';
        if (!empty($return_path)) {
            $this->session->specialOrderRedirect = $return_path;
        } elseif (isset($this->session->specialOrderRedirect)) {
            $return_path = $this->session->specialOrderRedirect;
        } else {
            $return_path = $this->config->get('URL') . "ordering/";
        }
        $ret = '';

        $prev = $next = -1;
        $found = false;
        $cachepath = sys_get_temp_dir()."/ordercache/";
        $cachekey = FormLib::get('k');
        if ($cachekey && file_exists($cachepath.$cachekey)) {
            $fptr = fopen($cachepath.$cachekey,'r');
            while (($buffer = fgets($fptr, 4096)) !== false) {
                if ((int)$buffer == $orderID) $found = true;
                elseif (!$found) $prev = (int)$buffer;
                elseif ($found) {
                    $next = (int)$buffer;
                    break;
                }
            }
            fclose($fptr);

            $ret .= '<div class="row">
                <div class="col-sm-6 text-left">';
            if ($prev == -1) {
                $ret .= '<span class="glyphicon glyphicon-chevron-left"></span>Prev';
            } else {
                $ret .= sprintf('<a href="?orderID=%d&k=%s" class="btn btn-default btn-xs">
                    <span class="glyphicon glyphicon-chevron-left"></span>Prev</a>',$prev,$cachekey);
            }
            $ret .= '</div><div class="col-sm-6 text-right">';
            if ($next == -1) {
                $ret .= '<span class="glyphicon glyphicon-chevron-right"></span>Next';
            } else {
                $ret .= sprintf('<a href="?orderID=%d&k=%s" class="btn btn-default btn-xs">
                    <span class="glyphicon glyphicon-chevron-right"></span>Next</a>',$next,$cachekey);
            }
            $ret .= '</div></div>';
        }

        $ret .= <<<HTML
<p />
<input type=hidden id=redirectURL value="{$return_path}" />
<div class="panel panel-default">
    <div class="panel-heading">Customer Information</div>
    <div class="panel-body" id="customerDiv"></div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">Order Items</div>
    <div class="panel-body" id="itemDiv"></div>
</div>
<div id="footerDiv"></div>
<input type=hidden value="{$orderID}" id="init_oid" />
HTML;

        $this->addScript('orderview.js');
        $this->addScript('../item/autocomplete.js');

        return $ret;
    }

    public function unitTest($phpunit)
    {
        if (!class_exists('SpecialOrderTests', false)) {
            include(dirname(__FILE__) . '/SpecialOrderTests.php');
        }
        $tester = new SpecialOrderTests($this->connection, $this->config, $this->logger);
        $tester->testCreateOrder($this, $phpunit);
        $tester->testOrderView($this, $phpunit);
        $tester->testSetCustomer($this, $phpunit);
        $tester->testAddItem($this, $phpunit);
        $tester->testEditItem($this, $phpunit);
        $tester->testDeleteItem($this, $phpunit);
        $tester->testEditCustomer($this, $phpunit);
        $tester->testToggles($this, $phpunit);

        $phpunit->assertNotEquals(0, strlen($this->getQtyForm(1, 3, 1, 'foo')));
        $phpunit->assertNotEquals(0, strlen($this->getDeptForm(1, 1, 'foo')));
    }
}

FannieDispatch::conditionalExec();

