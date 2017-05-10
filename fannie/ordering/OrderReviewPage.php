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

class OrderReviewPage extends FannieRESTfulPage
{
    protected $header = 'Review Order';
    protected $title = 'Review Order';
    protected $must_authenticate = true;
    public $description = '[Review Special Order] lists and an archived special order';
    public $page_set = 'Special Orders';

    public function preprocess()
    {
        $this->__routes[] = 'get<orderID>';
        $this->__routes[] = 'get<orderID><history>';
        $this->__routes[] = 'get<orderID><items>';
        $this->__routes[] = 'get<orderID><customer>';

        return parent::preprocess();
    }

    public function get_orderID_customer_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();
        $orderID = $this->orderID;

        $names = array();
        $pn = 1;
        $status_row = array(
            'Type' => 'REG',
            'status' => ''
        );

        $dbc->selectDB($this->config->get('TRANS_DB'));
        $orderModel = new SpecialOrdersModel($dbc);
        $orderModel->specialOrderID($orderID);
        $orderModel->load();
        $dbc->selectDB($this->config->get('OP_DB'));
        
        // look up member id 
        $memNum = 0;
        $findMem = $dbc->prepare("SELECT card_no,voided FROM {$TRANS}CompleteSpecialOrder WHERE order_id=?");
        $memR = $dbc->execute($findMem, array($orderID));
        if ($dbc->num_rows($memR) > 0) {
            $memW = $dbc->fetch_row($memR);
            $memNum = $memW['card_no'];
            $pn = $memW['voided'];
        }

        // Get member info from custdata, non-member info from SpecialOrders
        if ($memNum != 0) {
            $namesP = $dbc->prepare("SELECT personNum,FirstName,LastName FROM custdata
                WHERE CardNo=? ORDER BY personNum");
            $namesR = $dbc->execute($namesP,array($memNum));
            while($namesW = $dbc->fetch_row($namesR)) {
                $names[$namesW['personNum']] = array($namesW['FirstName'],$namesW['LastName']);
            }

            $statusQ = $dbc->prepare("SELECT Type FROM custdata WHERE CardNo=?");
            $statusR = $dbc->execute($statusQ,array($memNum));
            $status_row  = $dbc->fetch_row($statusR);
            if ($status_row['Type'] == 'INACT') {
                $status_row['status'] = 'Inactive';
            } elseif ($status_row['Type'] == 'INACT2') {
                $status_row['status'] = 'Inactive';
            } elseif ($status_row['Type'] == 'TERM') {
                $status_row['status'] = 'Terminated';
            }
        }

        $q = $dbc->prepare("SELECT entry_date FROM {$TRANS}SpecialOrderHistory 
                WHERE order_id=? AND entry_type='CONFIRMED'");
        $r = $dbc->execute($q, array($orderID));
        $confirm_date = "";
        if ($dbc->num_rows($r) > 0) {
            $confirm_date = array_pop($dbc->fetch_row($r));
        }

        $callback = 1;
        $user = 'Unknown';
        $orderDate = '';
        $q = $dbc->prepare("SELECT datetime,numflag,mixMatch FROM 
                {$TRANS}CompleteSpecialOrder WHERE order_id=? AND trans_id=0");
        $r = $dbc->execute($q, array($orderID));
        if ($dbc->num_rows($r) > 0) {
            list($orderDate,$callback,$user) = $dbc->fetch_row($r);
        }

        $ret = "";
        $ret .= sprintf('<input type="hidden" id="orderID" value="%d" />',$orderID);
        $ret .= '<div class="row">
            <div class="col-sm-6 text-left">';
        $ret .= sprintf('<b>Owner Number</b>: %s',
                ($memNum==0?'':$memNum));
        $ret .= '<br />';
        $ret .= '<b>Owner</b>: '.($status_row['Type']=='PC'?'Yes':'No');
        $ret .= '<br />';
        if (!empty($status_row['status'])) {
            $ret .= '<b>Account status</b>: '.$status_row['status'];
            $ret .= '<br />';
        }
        $ret .= "<b>Taken by</b>: ".$user."<br />";
        $ret .= "<b>On</b>: ".date("M j, Y g:ia",strtotime($orderDate))."<br />";
        $ret .= '</div>
            <div class="col-sm-6 text-right">';
        $ret .= '<b>Call to Confirm</b>: ';
        if ($callback == 1) {
            $ret .= 'Yes';
        } else {
            $ret .= 'No';
        }
        $ret .= '<br />';   
        $ret .= '<span id="confDateSpan">'.(!empty($confirm_date)?'Confirmed '.$confirm_date:'Not confirmed')."</span> ";
        $ret .= '<br />';

        $ret .= '<a href="index.php" class="btn btn-default">Done</a>';
        $ret .= '</div></div>';

        $ret .= '<table class="table table-bordered table-striped">';

        // names
        if (empty($names)) {
            $ret .= sprintf('<tr><th>First Name</th><td>%s
                    </td>',$orderModel->firstName(),$orderID);
            $ret .= sprintf('<th>Last Name</th><td>%s
                    </td>',
                    $orderModel->lastName(),$orderID);
        } else {
            $ret .= '<tr><th>Name</th><td colspan="2">';
            foreach($names as $p=>$n) {
                if ($p == $pn) $ret .= $n[0].' '.$n[1];
            }
            $ret .= '</td>';
            $ret .= '<td>&nbsp;</td>';
        }

        $super = new SuperDeptNamesModel($dbc);
        $super->superID($orderModel->noteSuperID());
        $super->load();
        $ret .= '<td colspan="4">Notes for: ' . $super->super_name() . '</td></tr>';

        // address
        $street = $orderModel->street();
        $street2 = '';
        if(strstr($street,"\n")) {
            list($street, $street2) = explode("\n", $street, 2);
        }

        $ret .= sprintf('<tr><th>Address</th><td>%s
            </td><th>E-mail</th><td>%s</td>
            <td rowspan="2" colspan="4">%s
            </td></tr>
            <tr><th>Addr (2)</th><td>%s
            </td><th>City</th><td>%s
            </td></tr>
            <tr><th>Phone</th><td>%s</td>
            <th>Alt. Phone</th><td>%s</td>
            <th>State</th>
            <td>%s</td>
            <th>Zip</th><td>%s</td></tr>',
            $street,
            $orderModel->email(),
            $orderModel->notes(),
            $street2,
            $orderModel->city(),
            $orderModel->phone(),
            $orderModel->altPhone(),
            $orderModel->state(),
            $orderModel->zip()
        );
            
        $ret .= '</table>';

        echo $ret;

        return false;
    }

    public function get_orderID_items_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();

        $dQ = $dbc->prepare("SELECT dept_no,dept_name FROM departments order by dept_no");
        $dR = $dbc->execute($dQ);
        $depts = array(0=>'Unassigned');
        while ($dW = $dbc->fetch_row($dR)) {
            $depts[$dW['dept_no']] = $dW['dept_name'];
        }

        $ret = '<table class="table table-bordered table-striped">';
        $ret .= '<tr><th>UPC</th><th>SKU</th><th>Description</th><th>Cases</th><th>SRP</th><th>Actual</th><th>Qty</th><th>Dept</th></tr>';
        $q = $dbc->prepare("SELECT o.upc,o.description,total,quantity,department,
            sku,ItemQtty,regPrice,o.discounttype,o.charflag,o.mixMatch FROM {$TRANS}CompleteSpecialOrder as o
            left join vendors as n on o.mixMatch=n.vendorName
            left join vendorItems as v on o.upc=v.upc AND o.upc <> '0000000000000' AND v.vendorID=n.vendorID
            WHERE order_id=? AND trans_type='I'
            ORDER BY trans_id DESC");
        $r = $dbc->execute($q, array($this->orderID));
        while ($row = $dbc->fetch_row($r)) {
            $ret .= $this->printItemRow($row, $depts);
        }
        $ret .= '</table>';

        echo $ret;

        return false;
    }

    private function printItemRow($row, $depts)
    {
        $ret = sprintf('<tr>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%d</td>
                <td>%.2f</td>
                <td>%.2f</td>
                <td>%.2f</td>',
                $row['upc'],
                (!empty($row['sku'])?$row['sku']:'&nbsp;'),
                $row['description'],
                $row['ItemQtty'],
                $row['regPrice'],
                $row['total'],
                $row['quantity']
        );
        $ret .= '<td>' . $row['department'] . ' ' . $depts[$row['department']] . '</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= sprintf('<td colspan="2" align="right">Unit Price: $%.2f</td>',
            ($row['regPrice']/$row['ItemQtty']/$row['quantity']));
        $ret .= sprintf('<td>From: %s</td>',$row['mixMatch']);
        $ret .= '<td>Discount</td>';
        if ($row['discounttype'] == 1 || $row['discounttype'] == 2) {
            $ret .= '<td id="discPercent'.$row['upc'].'">Sale</td>';
        } else if ($row['regPrice'] != $row['total']) {
            $ret .= sprintf('<td id="discPercent%s">%d%%</td>',$row['upc'],
                round(100*(($row['regPrice']-$row['total'])/$row['regPrice'])));
        } else {
            $ret .= '<td id="discPercent'.$row['upc'].'">0%</td>';
        }
        $ret .= sprintf('<td colspan="4">Printed: %s</td>',
                ($row['charflag']=='P'?'Yes':'No'));
        $ret .= '</tr>';
        $ret .= '<tr class="small"><td class="small" colspan="8"><span style="font-size:1;">&nbsp;</span></td></tr>';

        return $ret;
    }

    public function get_orderID_history_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));

        $prep = $dbc->prepare("SELECT entry_date, entry_type, entry_value
                           FROM SpecialOrderHistory
                           WHERE order_id = ?
                            AND entry_type IN ('AUTOCLOSE', 'PURCHASED')
                           ORDER BY entry_date");
        $result = $dbc->execute($prep, array($this->orderID));

        $ret = '<table class="table table-bordered table-striped small">';
        $ret .= '<tr>
                    <th>Date</th>
                    <th>Action</th>
                    <th>Details</th>
                 </tr>';
        while ($row = $dbc->fetch_row($result)) {
            $ret .= $this->printHistory($row);
        }
        $ret .= '</table>';

        echo $ret;

        return false;
    }

    private function printHistory($row)
    {
        if ($row['entry_type'] == 'PURCHASED') {
            $trans_num = $row['entry_value'];
            $tdate = date('Y-m-d', strtotime($row['entry_date']));
            $link = '../admin/LookupReceipt/RenderReceiptPage.php?date=' . $tdate . '&receipt=' . $trans_num;
            $row['entry_value'] = sprintf('<a href="%s" target="_%s">%s</a>', $link, $trans_num, $trans_num);
        }
        $ret = sprintf('<tr>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                         </tr>',
                            $row['entry_date'],
                            $row['entry_type'],
                            $row['entry_value']
        );

        return $ret;
    }

    public function get_view()
    {
        return '<div class="alert alert-danger">No Order Specified</div>';
    }

    public function get_orderID_view()
    {
        $body = <<<HTML
<p>
    <button type="button" class="btn btn-default"
        onclick="copyOrder({{orderID}}); return false;">
    Duplicate Order
    </button>
</p>
<div class="panel panel-default">
    <div class="panel-heading">Customer Information</div>
    <div class="panel-body" id="customerDiv"></div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">Order Items</div>
    <div class="panel-body" id="itemDiv"></div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">Order History</div>
    <div class="panel-body" id="historyDiv"></div>
</div>
HTML;
        return str_replace('{{orderID}}', $this->orderID, $body);
    }

    public function javascriptContent()
    {
        $js = <<<JAVASCRIPT
function copyOrder(oid){
    if (confirm("Copy this order?")){
        $.ajax({
            url:'ajax-calls.php',
            type:'post',
            data:'action=copyOrder&orderID='+oid,
            cache: false
        }).fail(function(e1,e2,e3){
            alert(e1);alert(e2);alert(e3);
        }).done(function(resp){
            location='OrderViewPage.php?orderID='+resp;
        });
    }
}
$(document).ready(function(){
    $.ajax({
        type: 'get',
        data: 'customer=1&orderID={{orderID}}',
        cache: false
    }).fail(function(e1,e2,e3){
        alert(e1);alert(e2);alert(e3);
    }).done(function(resp){
        $('#customerDiv').html(resp);
        var oid = $('#orderID').val();
        $.ajax({
            type: 'get',
            data: 'items=1&orderID='+oid+'&nonForm=yes',
            cache: false
        }).done(function(resp){
            $('#itemDiv').html(resp);
        });
        $.ajax({
            type: 'get',
            data: 'history=1&orderID='+oid,
            cache: false
        }).done(function(resp){
            $('#historyDiv').html(resp);
        });
    });

});
JAVASCRIPT;
        $orderID = property_exists($this, 'orderID') ? $this->orderID : 0;

        return str_replace('{{orderID}}', $orderID, $js);
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->javascriptContent()));
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->orderID=1;
        $phpunit->assertNotEquals(0, strlen($this->get_orderID_view()));
        ob_start();
        $this->get_orderID_customer_handler();
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));
        ob_start();
        $this->get_orderID_items_handler();
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));
        ob_start();
        $this->get_orderID_history_handler();
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));

        $item = array('upc'=>'1234','sku'=>'1234','description'=>'foo','ItemQtty'=>1,
            'regPrice'=>1,'total'=>1,'quantity'=>1,'department'=>1,'mixMatch'=>'foo',
            'discounttype'=>1, 'charflag'=>'P');
        $phpunit->assertNotEquals(0, strlen($this->printItemRow($item, array(1=>'foo'))));

        $entry = array('entry_type'=>'PURCHASED', 'entry_date'=>'2000-01-01', 'entry_value'=>1);
        $phpunit->assertNotEquals(0, strlen($this->printHistory($entry)));
    }
}

FannieDispatch::conditionalExec();

