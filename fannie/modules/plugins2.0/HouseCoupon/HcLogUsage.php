<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class HcLogUsage extends FannieRESTfulPage
{

    protected function post_view()
    {
        $dtrans = DTrans::defaults();
        $dtrans['emp_no'] = $this->config->get('EMP_NO');
        $dtrans['register_no'] = $this->config->get('REGISTER_NO');
        $dtrans['trans_no'] = DTrans::getTransNo($this->connection, $dtrans['emp_no'], $dtrans['register_no']);
        $dtrans['upc'] = '00499999' . str_pad(FormLib::get('coupID'), 5, '0', STR_PAD_LEFT);
        $dtrans['description'] = 'Manual House Coupon';
        $dtrans['trans_type'] = 'T';
        $dtrans['trans_subtype'] = 'IC';
        $dtrans['quantity'] = 1;
        $dtrans['ItemQtty'] = 1;
        $dtrans['total'] = 0;
        $dtrans['trans_id'] = 1;

        $table = $this->config->get('TRANS_DB') . $this->connection->sep() . 'dtransactions';

        foreach (explode("\n", FormLib::get('cardno')) as $cardno) {
            $cardno = trim($cardno);
            if (!is_numeric($cardno)) continue;
            $dtrans['card_no'] = $cardno;

            $info = DTrans::parameterize($dtrans, 'datetime', $this->connection->now());
            $query = "INSERT INTO {$table} ({$info['columnString']}) VALUES ({$info['valueString']})";
            $prep = $this->connection->prepare($query);
            $res = $this->connection->execute($prep, $info['arguments']);

            $dtrans['trans_id'] += 1;
        }

        return '<div class="alert alert-success">Logged usage</div>';
    }

    protected function get_view()
    {
        $res = $this->connection->query('SELECT coupID, description FROM houseCoupons');
        $opts = '';
        while ($row = $this->connection->fetchRow($res)) {
            $opts .= sprintf('<option value="%d">%s</option>', $row['coupID'], $row['description']);
        }

        return <<<HTML
<form method="post">
<div class="form-group">
    <label>Coupon</label>
    <select name="coupID" class="form-control">{$opts}</select>
</div>
<div class="form-group">
    <label>Owner #(s)</label>
    <textarea name="cardno" class="form-control" rows="10"></textarea>
</div>
<div class="form-group">
    <button type="submit" class="btn btn-default btn-core">Log Usage</button>
</div>
HTML;
    }
}

FannieDispatch::conditionalExec();

