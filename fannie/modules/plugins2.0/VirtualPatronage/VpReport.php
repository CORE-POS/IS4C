<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class VpReport extends FannieReportPage
{
    protected $title = 'Allocate Virtual Patronage';
    protected $header = 'Allocate Virtual Patronage';
    public $description = '[Virtual Patronage Report] shows information about vouchers issued for a given rebate';

    protected $required_fields = array('fy');

    function fetch_report_data()
    {
        $table = $this->config->get('TRANS_DB') . $this->connection->sep() . 'VirtualVouchers';
        $query = "SELECT v.cardNo,
            c.LastName,
            c.FirstName,
            v.amount
            v.expired,
            v.redeemed,
            v.redeemedAt,
            v.redeemedDate, 
            v.redeemedTrans
        FROM {$table} AS v
            LEFT JOIN custdata AS c ON v.cardNo=c.CardNo AND c.personNum=1
        WHERE v.fiscalYear=?";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($this->form->fy));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $redeemed = '';
            $rdate = '';
            $rtrans = '';
            switch ($row['redeemedAs']) {
                case 'VOUCHER':
                case 'CHECK':
                case 'DONATION':
                    $redeemed = $row['redeemedAs'];
                    break;
                default:
                    $redeemed = 'Unknown';
                    break;
            }
            if ($row['redeemed']) {
                $rdate = date('Y-m-d', strtotime($row['redeemedDate']));
                $rtrans = $row['redeemedTrans'];
            } else {
                $redeemed = 'n/a';
            }

            $record[] = array(
                $row['cardNo'],
                $row['LastName'],
                $row['FirstName'],
                sprintf('%.2f', $row['amount']),
                $row['expired'] ? 'Yes' : 'No',
                $redeemed,
                $rdate,
                $rtrans,
            );
        }

        return $data;
    }

    function form_content()
    {
        $table = $this->config->get('TRANS_DB') . $this->connection->sep() . 'VirtualVouchers';
        $res = $this->connection->query("SELECT fiscalYear FROM {$table} GROUP BY fiscalYear ORDER BY fiscalYear DESC");
        if ($this->connection->numRows($res) == 0) {
            return '<div class="alert alert-danger">No vouchers on file in the system</div>';
        }

        $opts = '';
        while ($row = $this->connection->fetchRow($res)) {
            $opts .= '<option>' . $row['FY'] . '</option>';
        }

        return <<<HTML
<form method="post">
    <div class="form-group">
        <label>Fiscal Year</label>
        <select name="fy" class="form-control">{$opts}</select>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Get Report</button>
    </div>
</form>
HTML;
    }

}

FannieDispatch::conditionalExec();

