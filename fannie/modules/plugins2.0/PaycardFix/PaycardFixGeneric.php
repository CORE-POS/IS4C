<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('MSoapClient')) {
    include(__DIR__ . '/../RecurringEquity/MSoapClient.php');
}


class PaycardFixGeneric extends FannieRESTfulPage
{

    protected $header = 'Generic Refund Card Transaction';
    protected $title = 'Generic Refund Card Transaction';
    public $discoverable = true;
    protected $must_authenticate = true;
    protected $auth_classes = array('admin');

    protected function post_handler()
    {
        try {
            $date = $this->form->date;
            $orig = $this->form->trans;
            $amount = $this->form->amount;
            $via = $this->form->via;
            $storeID = $this->form->store;
        } catch (Exception $ex) {
            return 'PaycardFixGeneric.php';
        }

        $EMP = 1001;
        $REG = 30;
        $TRANS = DTrans::getTransNo($this->connection, $EMP, $REG);

        $dtransactions = $this->config->get('TRANS_DB') . $this->connection->sep() . 'dtransactions';
        $dtrans = DTrans::defaults();
        $dtrans['emp_no'] = $EMP;
        $dtrans['register_no'] = $REG;
        $dtrans['trans_no'] = $TRANS;
        $dtrans['trans_type'] = 'D';
        $dtrans['department'] = 703;
        $dtrans['description'] = 'MISCRECEIPT';
        $dtrans['upc'] = $amount . 'DP703';
        $dtrans['quantity'] = 1;
        $dtrans['ItemQtty'] = 1;
        $dtrans['trans_id'] = 1;
        $dtrans['total'] = -1*$amount;
        $dtrans['unitPrice'] = -1*$amount;
        $dtrans['regPrice'] = -1*$amount;
        $dtrans['card_no'] = 11;
        $dtrans['store_id'] = $storeID;
        $prep = DTrans::parameterize($dtrans, 'datetime', $this->connection->now());
        $insP = $this->connection->prepare("INSERT INTO {$dtransactions} ({$prep['columnString']}) VALUES ({$prep['valueString']})");
        $insR = $this->connection->execute($insP, $prep['arguments']);

        $dtrans['trans_type'] = 'T';
        $dtrans['trans_subtype'] = 'CC';
        $dtrans['department'] = 0;
        $dtrans['description'] = 'Credit Card';
        $dtrans['upc'] = '';
        $dtrans['quantity'] = 0;
        $dtrans['ItemQtty'] = 0;
        $dtrans['trans_id'] = 2;
        $dtrans['total'] = $amount;
        $dtrans['unitPrice'] = 0;
        $dtrans['regPrice'] = 0;
        $dtrans['store_id'] = $storeID;
        $prep = DTrans::parameterize($dtrans, 'datetime', $this->connection->now());
        $insR = $this->connection->execute($insP, $prep['arguments']);

        DTrans::addItem($this->connection, $TRANS, array(
            'description' => "Original: {$date} {$orig}",
            'trans_type' => 'C',
            'trans_subtype' => 'CM',
            'register_no', $REG,
            'emp_no', $EMP,
        ));
        DTrans::addItem($this->connection, $TRANS, array(
            'description' => "Refund via: {$via}",
            'trans_type' => 'C',
            'trans_subtype' => 'CM',
            'register_no', $REG,
            'emp_no', $EMP,
        ));

        return $this->config->get('URL') . 'admin/LookupReceipt/RenderReceiptPage.php?date=' . date('Y-m-d') . "&receipt={$EMP}-{$REG}-{$TRANS}";
    }

    protected function get_view()
    {
        $stores = FormLib::storePicker();
        return <<<HTML
<form method="post">
    <div class="form-group">
        <label>Refund Amount</label>
        <input type="text" name="amount" class="form-control" required />
    </div>
    <div class="form-group">
        <label>Original Transaction Date</label>
        <input type="text" name="date" class="form-control date-field" required />
    </div>
    <div class="form-group">
        <label>Original Transaction Number</label>
        <input type="text" name="trans" class="form-control" required />
    </div>
    <div class="form-group">
        <label>Refunded via</label>
        <select class="form-control" name="via">
            <option>Mercury</option>
            <option>FAPS</option>
        </select>
    </div>
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Log Transaction</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

