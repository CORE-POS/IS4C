<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'/classlib2.0/FannieAPI.php');
}

class B2BInvoicePage extends FannieRESTfulPage
{
    protected $header = 'B2B Invoice';
    protected $title = 'B2B Invoice';
    public $disoverable = false;

    protected function post_id_handler()
    {
        $EMP_NO = 1001;
        $LANE_NO = 30;
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $invoice = new B2BInvoicesModel($dbc);
        $invoice->b2bInvoiceID($this->id);
        $invoice->description(FormLib::get('description'));
        $invoice->amount(FormLib::get('amount'));
        $invoice->coding(FormLib::get('coding'));
        $invoice->customerNotes(FormLib::get('customerNotes'));
        $invoice->internalNotes(FormLib::get('internalNotes'));
        $invoice->lastModifiedBy(FannieAuth::getUID($this->current_user));
        /**
         * Mark the invoice as paid
         * Write a new transaction to offset the original.
         *
         * On a reversal:
         *  The "sale" portion is a positive B2B INVOICING ring to offset the original
         *  The "tender" portion is an open ring offsets the original sale
         * On a tender:
         *  The "sale" portion is a positive B2B INVOICING ring to offset the original
         *  The tender is an actual tender record.
         */
        if (FormLib::get('payFlag', 0)) {
            $amt = $dbc->prepare('SELECT amount FROM B2BInvoices WHERE b2bInvoiceID=?');
            $amt = $dbc->getValue($amt, array($this->id));
            $dRecord = DTrans::defaults();
            $dRecord['emp_no'] = $EMP_NO;
            $dRecord['register_no'] = $LANE_NO;
            $dRecord['trans_type'] = 'D';
            $dRecord['department'] = 994;
            $dRecord['upc'] = $amt . 'DP994';
            $dRecord['description'] = 'B2B INVOICING';
            $dRecord['quantity'] = 1;
            $dRecord['ItemQtty'] = 1;
            $dRecord['unitPrice'] = $amt;
            $dRecord['total'] = $amt;
            $dRecord['regPrice'] = $amt;
            $dRecord['charflag'] = 'B2';
            $dRecord['numflag'] = $this->id;
            $dRecord['trans_id'] = 1;

            $tRecord = DTrans::defaults();
            $tRecord['emp_no'] = $EMP_NO;
            $tRecord['register_no'] = $LANE_NO;
            $tRecord['trans_type'] = 'T';
            $tRecord['department'] = 0;
            $tRecord['total'] = -1*$amt;
            $tRecord['quantity'] = 0;
            $tRecord['ItemQtty'] = 0;
            switch (FormLib::get('payMethod')) {
                case 'CK':
                    $tRecord['trans_subtype'] = 'CK';
                    $tRecord['description'] = 'Check';
                    break;
                case 'CC':
                    $tRecord['trans_subtype'] = 'CC';
                    $tRecord['description'] = 'Credit Card';
                    break;
                case 'CA':
                    $tRecord['trans_subtype'] = 'CA';
                    $tRecord['description'] = 'Cash';
                    break;
            }
            $tRecord['charflag'] = 'B2';
            $tRecord['numflag'] = $this->id;
            $tRecord['trans_id'] = 2;

            $dRecord['trans_no'] = DTrans::getTransNo($dbc, $EMP_NO, $LANE_NO);
            $tRecord['trans_no'] = $dRecord['trans_no'];
            $dParam = DTrans::parameterize($dRecord, 'datetime', $dbc->now());
            $insD = $dbc->prepare("INSERT INTO dtransactions
                    ({$dParam['columnString']}) VALUES ({$dParam['valueString']})");
            $tParam = DTrans::parameterize($tRecord, 'datetime', $dbc->now());
            $insT = $dbc->prepare("INSERT INTO dtransactions
                    ({$tParam['columnString']}) VALUES ({$tParam['valueString']})");
            $dbc->execute($insP, $pParam['arguments']);
            $dbc->execute($insT, $tParam['arguments']);
            $invoice->paidDate(date('Y-m-d H:i:s'));
            $invoice->paidTransNum('1001-30-' . $dTrecord['trans_no']);
            $invoice->isPaid(FormLib::get('payMethod') == 'RV' ? 2 : 1);
        }
        $invoice->save();

        return 'B2BInvoicePage.php?id=' . $this->id;
    }

    protected function get_id_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $this->invoice = new B2BInvoicesModel($dbc);
        $this->invoice->b2bInvoiceID($this->id);
        $loaded = $this->invoice->load();
        $dbc->selectDB($this->config->get('OP_DB'));
        if (!$loaded) {
            $this->invoice = false;
            return true;
        }

        $memP = $dbc->prepare("SELECT c.LastName, c.FirstName, m.* FROM custdata AS c 
                LEFT JOIN meminfo AS m ON c.CardNo=m.card_no 
            WHERE c.personNum=1 AND c.CardNo=?");
        $this->member = $dbc->getRow($memP, array($this->invoice->cardNo()));

        return true;
    }

    protected function get_id_view()
    {
        if (!$this->invoice) {
            return '<div class="alert alert-danger">Invoice not found</div>' . $this->get_view();
        }
        $invoice = $this->invoice->toStdClass();
        $creator = FannieAuth::getName($invoice->createdBy);
        $modifier = FannieAuth::getName($invoice->lastModifiedBy);
        $finalized = $invoice->isPaid ? 'collapse' : '';
        $finalAs = $invoice->isPaid == 2 ? 'Reversed' : 'Paid';
        if (!$invoice->isPaid) {
            $invoice->paidDate = 'Not yet paid';
        }

        $ret = <<<HTML
<form method="post" action="B2BInvoicePage.php">
<input type="hidden" name="id" value="{$this->id}" />
<table class="table table-bordered">
    <tr>
        <th>Invoice #</th>
        <td colspan="2">{$this->id}</td>
    <tr>
        <th>For</th>
        <td colspan="2"><input type="text" name="description" value="{$invoice->description}" class="form-control" /></td>
    </tr>
    <tr>
        <th>Amount</th>
        <td colspan="2"><input type="text" name="amount" value="{$invoice->amount}" class="form-control" /></td>
    </tr>
    <tr>
        <th>Created</th>
        <td>{$invoice->createdDate}</td>
    </tr>
    <tr>
        <th>{$finalAs}</th>
        <td>{$invoice->paidDate} {$invoice->paidTransNum}</td>
    </tr>
    <tr>
        <th>Notes for Customer</th>
        <td colspan="2"><textarea class="form-control" rows="3" name="customerNotes">{$invoice->customerNotes}</textarea></td>
    </tr>
    <tr>
        <th>Customer</th>
        <td colspan="2">{$this->member['card_no']} {$this->member['FirstName']} {$this->member['LastName']}</td>
    </tr>
    <tr>
        <th>Mailing Address</th>
        <td colspan="2">{$this->member['street']}, {$this->member['city']}, {$this->member['state']} {$this->member['zip']}</td>
    </tr>
    <tr>
        <th>Email Address</th>
        <td colspan="2">{$this->member['email_1']}</td>
    </tr>
    <tr>
        <th>Phone #</th>
        <td colspan="2">{$this->member['phone']}</td>
    </tr>
    <tr>
        <th>Internal Coding</th>
        <td colspan="2"><input type="text" name="coding" class="form-control" value="{$invoice->coding}" /></td>
    </tr>
    <tr>
        <th>Internal Notes</th>
        <td colspan="2"><textarea class="form-control" rows="3" name="internalNotes">{$invoice->internalNotes}</textarea></td>
    </tr>
</table>
<hr />
<!--
<p class="form-inline {$finalized}">
    <label>Mark invoice as paid</label>
    <select class="form-control" name="payFlag"><option value="0">No</option><option value="1">Yes</option></select>
    <select class="form-control" name="payMethod">
        <option value="CK">Check</option>
        <option value="CC">Credit Card</option>
        <option value="CA">Cash</option>
    </select>
</p>
-->
<p>
    <button type="submit" class="btn btn-default">Update Invoice</button>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="B2BInvoicePage.php" class="btn btn-default">Lookup Another Invoice</a>
</p>
<p>
Created by {$creator}<br />
Last modified by {$modifier}<br />
</p>
</form>
HTML;

        return $ret;
    }

    protected function get_view()
    {
        $this->addOnloadCommand("\$('#inv-num').focus();");
        return <<<HTML
<form method="get" action="B2BInvoicePage.php">
    <div class="form-group">
        <label>Invoice #</label>
        <input type="text" class="form-control" id="inv-num" name="id" required />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Get Invoice</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

