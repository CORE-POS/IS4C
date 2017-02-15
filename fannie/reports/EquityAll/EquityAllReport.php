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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class EquityAllReport extends FannieReportPage 
{
    public $description = '[Equity Balances] lists current or near-current equity totals for all members';
    public $report_set = 'Membership';
    public $themed = true;

    protected $report_headers = array('Mem #', 'Last Name', 'First Name', 'Equity', 'Due Date');
    protected $title = "Fannie : All Equity Report";
    protected $header = "All Equity Report";
    protected $required_fields = array('submit');

    public function readinessCheck()
    {
        return $this->tableExistsReadinessCheck($this->config->get('TRANS_DB'), 'equity_live_balance');
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $type_restrict = "c.Type IN ('PC')";
        if (FormLib::get('memtypes', 1) == 2) {
            $type_restrict = "c.Type NOT IN ('TERM')";
        } elseif (FormLib::get('memtypes', 1) == 3) {
            $type_restrict = '1=1';
        }

        $equity_restrict = "(n.payments > 0)";
        if (FormLib::get('owed',1) == 2) {
            $equity_restrict = "(n.payments > 0 AND n.payments < 100)";
        }
        $table = 'equity_history_sum';
        $num = 'n.card_no';
        if (FormLib::get('grain', 1) == 2) {
            $table = 'equity_live_balance';
            $num = 'n.memnum';
        }

        $query = "SELECT $num as memnum,c.LastName,c.FirstName,n.payments,m.end_date
            FROM custdata AS c LEFT JOIN "
            . $this->config->get('TRANS_DB') . $dbc->sep() . $table . " as n ON
            $num=c.CardNo AND c.personNum=1
            LEFT JOIN memDates as m ON $num=m.card_no
            WHERE $type_restrict AND $equity_restrict
            ORDER BY $num";

        $prep = $dbc->prepare($query);

        $res = $dbc->execute($prep);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        $record = array();
        if (FormLib::get('excel') === '') {
            $record[] = sprintf('<a href="%s%d">%d</a>',$this->config->get('URL')."reports/Equity/index.php?memNum=",$row['memnum'],$row['memnum']);
        } else {
            $record[] = $row['memnum'];
        }
        $record[] = $row['LastName'];
        $record[] = $row['FirstName'];
        $record[] = sprintf('%.2f',$row['payments']);
        $record[] = $row['end_date'];

        return $record;
    }

    public function form_content()
    {
        ob_start();
        ?>
<form action="EquityAllReport.php" method="get">
<div class="form-group">
    <label>Active status</label>
    <select name="memtypes" class="form-control">
        <option value=1><?php echo _('Active Owners'); ?></option>
        <option value=2><?php echo _('Non-termed Owners'); ?></option>
        <option value=3><?php echo _('All Owners'); ?></option>
    </select>
</div>
<div class="form-group">
    <label>Equity balance</label>
    <select name="owed" class="form-control">
        <option value=1>Any balance</option>
        <option value=2>less than $100</option>
    </select>
</div>
<div class="form-group">
    <label>As of</label>
    <select name="grain" class="form-control">
        <option value=1>Yesterday</option>
        <option value=2>Right this Second (slower)</option>
    </select>
</div>
<p>
    <button type="submit" name="submit" value="1" 
        class="btn btn-default">Submit</button>
</p>
</form>
        <?php
        return ob_get_clean();

    }

    public function helpContent()
    {
        return '<p>
            List equity balances for members or a subset of members.
            Pulling live data is a bit slower than pulling
            as-of-yesterday data.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('memnum'=>1, 'LastName'=>'test', 'FirstName'=>'test', 'payments'=>100, 'end_date'=>'2000-01-01');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

