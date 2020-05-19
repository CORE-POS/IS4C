<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class ReprintByCard extends FannieRESTfulPage
{
    protected $header = 'Look Up Receipt by Payment Card';
    protected $title = 'Look Up Receipt by Payment Card';

    public $description = '[Reprint by Payment Card] look up receipt using payment card data';

    private $error = false;
    private $results = array();

    public function preprocess()
    {
        $this->addRoute('get<date>');

        return parent::preprocess();
    }

    protected function get_date_handler()
    {
        $date = FormLib::get('date');
        $ts = strtotime($date);
        if ($ts === false) {
            $this->error = 'Invalid date';
            return true;
        }
        $date = date('Ymd', $ts);

        $pan = trim(FormLib::get('pan'));
        $name = trim(FormLib::get('name'));
        if ($pan == '' && $name == '') {
            $this->error = 'Last 4 or name is required';
            return true;
        }

        $query = "
            SELECT empNo, registerNo, transNo, amount, requestDatetime
            FROM " . FannieDB::fqn('PaycardTransactions', 'trans') . "
            WHERE dateID=? ";
        $args = array($date);
        if ($pan != '') {
            $query .= " AND PAN LIKE ? ";
            $args[] = '%' . $pan;
        }
        if ($name != '') {
            $query .= ' AND name LIKE ? ';
            $args[] = '%' . $name . '%';
        }
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $this->results[] = $row;
        }

        if (count($this->results) == 0) {
            $this->error = 'No transaction found';
            return true;
        } elseif (count($this->results) == 1) {
            $match = $this->results[0];
            list($date,) = explode(' ', $match['requestDatetime'], 2);
            $trans = $match['empNo'] . '-' . $match['registerNo'] . '-' . $match['transNo'];
            
            return 'RenderReceiptPage.php?date=' . $date . '&receipt=' . $trans;
        }

        return true;
    }

    protected function get_date_view()
    {
        if ($this->error) {
            return '<div class="alert alert-danger">' . $this->error . '</div>'
                . $this->get_view();
        }

        $tbody = '';
        foreach ($this->results as $row) {
            list($date,) = explode(' ', $row['requestDatetime']);
            $trans = $row['empNo'] . '-' . $row['registerNo'] . '-' . $row['transNo'];
            $link = 'RenderReceiptPage.php?date=' . $date . '&receipt=' . $trans;
            $tbody .= sprintf('<tr><td>%s</td><td><a href="%s">%s</a></td><td>%.2f</td></tr>',
                $row['requestDatetime'], $link, $trans, $row['amount']);
        }

        return <<<HTML
<table class="table table-bordered table-striped">
    <tr><th>Date+Time</th><th>Receipt</th><th>Amount</th></tr>
    {$tbody}
</table>
HTML;
    }

    protected function get_view()
    {
        return <<<HTML
<form method="get" action="ReprintByCard.php">
    <div class="form-group">
        <label>Payment Date</label>
        <input type="text" class="form-control date-field" name="date" />
    </div>
    <div class="form-group">
        <label>Last 4 digits</label>
        <input type="text" class="form-control" name="PAN" />
    </div>
    <div class="form-group">
        <label>Name on the Card</label>
        <input type="text" class="form-control" name="name" />
    </div>
    <div class="form-group">
        <button class="btn btn-default">Lookup Receipt</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

