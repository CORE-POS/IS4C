<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class GenericBillingPage extends FannieRESTfulPage 
{
    protected $title = "Fannie : Generic Biling";
    protected $header = "Generic Billing";

    public $description = '[Generic Billing] adds a specified amount and memo to a member\'s
    accounts receivable (AR) balance.';
    public $themed = true;

    private $LANE_NO=30;
    private $EMP_NO=1001;
    private $DEPT=703;

    function get_view()
    {
        $value = FormLib::get_form_value('id');
        $this->add_onload_command('$(\'#memnum\').val($(\'#sel\').val());');
        $this->addScript('../../src/javascript/vue.js');
        $this->addScript('billing.js?date=20191121');
        $accounts = \COREPOS\Fannie\API\member\MemberREST::search(
            array(
                'customerTypeID' => 2,
                'customers' => array(
                    array('accountHolder'=>1),
                ), 
            ),
            0,
            true
        );
        $opts = '';
        foreach ($accounts as $account) {
            $opts .= sprintf('<option %s value="%d">%d %s</option>',
                    ($value == $account['cardNo'] ? 'selected' : ''),
                    $account['cardNo'], $account['cardNo'],
                    $account['customers'][0]['lastName']);
        }

        return <<<HTML
<form onsubmit="genericBilling.getMemInfo(); return false;">
<div class="form-group form-inline">
    <label>Member #</label>:
    <input type=text id=memnum name=id 
        class="form-control" value="{$value}" />
    <select id=sel class="form-control"
        onchange="$('#memnum').val(this.value);">"
        {$opts}
    </select>
    <button type=submit class="btn btn-default">Submit</button>
</div>
</form><hr />
<div id="contentArea" v-bind:class="hidden">
    <form onsubmit="genericBilling.postBilling();return false;">
    <div class="col-sm-6">
        <table class="table">
            <tr>
                <th>Member</th>
                <td>{{ cardNo }}<input type=hidden id=form_memnum v-bind:value="cardNo" /></td>
                <th>Name</th>
                <td>{{ lastName }}</td>
            </tr>
            <tr>
                <th>Current Balance</th>
                <td>{{ balance }}</td>
                <th>Bill</th>
                <td>
                    <div class="input-group">
                        <span class="input-group-addon">$</span>
                        <input type=text class="form-control" id=amount required v-bind:value="amount" />
                    </div>
                </td>
            </tr>
            <tr>
                <th>For</th>
                <td colspan=3><input type=text maxlength=35 id=desc 
                    class="form-control" required v-bind:value="description" /></td>
            </tr>
        </table>
        <p>
            <button type=submit class="btn btn-default">Bill Account</button>
        </p>
    </div>
    </form>
</div>
<div id="resultArea">
    <div v-bind:class="alertClass" role="alert">
        <button type="button" class="close" v-on:click="hide">
            <span>&times;</span>
        </button>
        {{ message }}
    </div>
</div>
HTML;
    }

    function get_id_handler(){
        $sql = FannieDB::getReadOnly($this->config->get('OP_DB'));

        $account = \COREPOS\Fannie\API\member\MemberREST::get($this->id);
        $query = "SELECT n.balance
            FROM  " . FannieDB::fqn('ar_live_balance', 'trans') . " AS n 
            WHERE n.card_no=?";
        $prep = $sql->prepare($query);
        $row = $sql->getRow($prep, array($this->id));
        $json = array(
            'cardNo' => $this->id,
            'lastName' => $account['customers'][0]['lastName'],
            'balance' => $row['balance'],
        );
        echo json_encode($json);

        return false;
    }

    function post_id_handler()
    {
        $sql = FannieDB::get($this->config->get('TRANS_DB'));
        $json = array('msg' => '', 'billed' => 0);

        try {
            $amount = $this->form->amount;
            $desc = $this->form->desc;
        } catch (Exception $ex) {
            $json['msg'] = 'Invalid request';
        }
        if (isset($amount) && $amount === '') {
            $json['msg'] = "Amount is required";
        } elseif (isset($desc) && $desc === '') {
            $json['msg'] =  "Description is required";
        }
        if ($json['msg']) {
            echo json_encode($json);
            return false;
        }

        $desc = str_replace("'","''",$desc);
        $trans_no = DTrans::getTransNo($sql, $this->EMP_NO, $this->LANE_NO);
        $params = array(
            'card_no' => $this->id,
            'register_no' => $this->LANE_NO,
            'emp_no' => $this->EMP_NO,
        );
        DTrans::addOpenRing($sql, $this->DEPT, $amount, $trans_no, $params);

        $params['description'] = 'InStore Charges';
        $params['trans_type'] = 'T';
        $params['trans_subtype'] = 'MI';
        $params['total'] = -1*$amount;
        DTrans::addItem($sql, $trans_no, $params);

        $params['description'] = $desc;
        $params['trans_type'] = 'C';
        $params['trans_subtype'] = 'CM';
        $params['total'] = 0;
        DTrans::addItem($sql, $trans_no, $params);

        $json['msg'] = sprintf("Member <b>%d</b> billed <b>$%.2f</b>.<br />
                Receipt is %d-%d-%d.",$this->id,$amount,
                $this->EMP_NO,$this->LANE_NO,$trans_no);
        $json['billed'] = 1;
        $json['trans_no'] = $trans_no;
        echo json_encode($json);

        return false;
    }

    public function helpContent()
    {
        return '<p>Add a miscellaneous entry to a customer account\'s
            accounts receivable (AR) balance. The general use case 
            involves billing business customers for random services
            that lack specialized tooling.
            </p>'; 
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = 1;
        ob_start();
        $phpunit->assertEquals(false, $this->get_id_handler());
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));
        $form = new COREPOS\common\mvc\ValueContainer();
        $form->amount = 1;
        $form->desc = 'TEST';
        $this->setForm($form);
        ob_start();
        $phpunit->assertEquals(false, $this->post_id_handler());
        $json = ob_get_clean();
        $json = json_decode($json, true);
        $phpunit->assertArrayHasKey('trans_no', $json);
        $delP = $this->connection->prepare('DELETE FROM ' . FannieDB::fqn('dtransactions', 'trans') . ' WHERE emp_no=? AND register_no=? AND trans_no=?');
        $delR = $this->connection->execute($delP, array($this->EMP_NO, $this->LANE_NO, $json['trans_no']));
        $phpunit->assertNotEquals(false, $delR, 'Cleaning up: ' . $this->connection->error());
    }
}

FannieDispatch::conditionalExec();

