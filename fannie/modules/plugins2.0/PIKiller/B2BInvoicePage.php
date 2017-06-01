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
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $invoice = new B2BInvoicesModel($dbc);
        $invoice->b2bInvoiceID($this->id);
        $invoice->description(FormLib::get('description'));
        $invoice->coding(FormLib::get('coding'));
        $invoice->customerNotes(FormLib::get('customerNotes'));
        $invoice->internalNotes(FormLib::get('internalNotes'));
        $invoice->lastModifiedBy(FannieAuth::getUID($this->current_user));
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
        <td colspan="2">{$invoice->amount}</td>
    </tr>
    <tr>
        <th>Created</th>
        <td>{$invoice->createdDate}</td>
        <td><a href="../../../admin/LookupReceipt/RenderReceiptPage.php?date={$invoice->createdDate}&receipt={$invoice->createdTransNum}">{$invoice->createdTransNum}</a></td>
    </tr>
    <tr>
        <th>Paid</th>
        <td>{$invoice->paidDate}</td>
        <td>{$invoice->paidTransNum}</td>
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
<p class="form-inline">
    <label>Mark invoice as paid</label>
    <select class="form-control" name="payFlag"><option value="0">No</option><option value="1">Yes</option></select>
    <select class="form-control" name="payMethod">
        <option value="CK">Check</option>
        <option value="CC">Credit Card</option>
        <option value="CA">Cash</option>
        <option value="RV">Reversal</option>
    </select>
    <input type="text" name="paidDate" class="form-control date-field" placeholder="Payment Date (optional)" />
</p>
<p>
    <button type="submit" class="btn btn-default">Update Invoice</button>
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
        return <<<HTML
<form method="get" action="B2BInvoicePage.php">
    <div class="form-group">
        <label>Invoice #</label>
        <input type="text" class="form-control" name="id" required />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Get Invoice</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

