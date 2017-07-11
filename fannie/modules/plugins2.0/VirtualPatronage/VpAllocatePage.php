<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class VpAllocatePage extends FannieRESTfulPage
{
    protected $title = 'Allocate Virtual Patronage';
    protected $header = 'Allocate Virtual Patronage';
    public $description = '[Virtual Patronage Allocation] creates virtual vouchers based on an existing patronage rebate';

    /**
      Use fiscal year entries in op.patronage to create corresponding
      vouchers in trans.VirtualVouchers
    */
    protected function post_id_view()
    {
        $dbc = $this->connection;
        $table = $this->config->get('TRANS_DB') . $dbc->sep() . 'VirtualVouchers';
        $testP = $dbc->prepare("SELECT fiscalYear FROM {$table} WHERE fiscalYear=?");
        $exists = $dbc->getValue($testP, array($this->id));
        if ($exists !== false) {
            $msg = base64_encode('Vouchers already exists for ' . $this->id);
            return 'VpAllocatePage.php?id=' . $msg;
        }

        $alloP = $dbc->prepare("
            INSERT INTO {$table}
                (cardNo, fiscalYear, amount, issueDate)
            SELECT cardno,
                FY,
                cash_pat,
                " . $dbc->now() . "
            FROM patronage
            WHERE cash_pat > 0
                AND FY=?");
        $alloR = $dbc->execute($alloP, array($this->id));
        $exp = $this->form->tryGet('expires', false);
        if ($exp) {
            $expP = $dbc->prepare("UPDATE {$table} SET expireDate=? WHERE fiscalYear=?");
            $dbc->execute($expP, array($exp, $this->id));
        }
        $msg = base64_encode('Vouchers created for ' . $this->id);

        return 'VpAllocatePage.php?id=' . $msg;
    }

    protected function get_id_view()
    {
        return '<div class="alert alert-info">' . base64_decode($this->id) . '</div>'
            . $this->get_view();
    }
    
    protected function get_view()
    {
        $res = $this->connection->query("SELECT FY FROM patronage GROUP BY FY ORDER BY FY DESC");
        if ($this->connection->numRows($res) == 0) {
            return '<div class="alert alert-danger">No patronage rebates on file in the system</div>';
        }

        $opts = '';
        while ($row = $this->connection->fetchRow($res)) {
            $opts .= '<option>' . $row['FY'] . '</option>';
        }

        return <<<HTML
<form method="post">
    <div class="form-group">
        <label>Fiscal Year</label>
        <select name="id" class="form-control">{$opts}</select>
    </div>
    <div class="form-group">
        <label>Expiration Date</label>
        <input type="text" name="expires" class="form-control date-picker" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Allocate Virtual Vouchers</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

