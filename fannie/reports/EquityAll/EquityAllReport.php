<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
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

    protected $report_headers = array('Mem #', 'Last Name', 'First Name', 'Equity', 'Due Date');
    protected $title = "Fannie : All Equity Report";
    protected $header = "All Equity Report";
    protected $required_fields = array('submit');

    public function readinessCheck()
    {
        global $FANNIE_TRANS_DB;
        return $this->tableExistsReadinessCheck($FANNIE_TRANS_DB, 'equity_live_balance');
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);

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

        $q = "SELECT $num as memnum,c.LastName,c.FirstName,n.payments,m.end_date
            FROM custdata AS c LEFT JOIN "
            . $FANNIE_TRANS_DB . $dbc->sep() . $table . " as n ON
            $num=c.CardNo AND c.personNum=1
            LEFT JOIN memDates as m ON $num=m.card_no
            WHERE $type_restrict AND $equity_restrict
            ORDER BY $num";

        $p = $dbc->prepare_statement($q);

        $r = $dbc->exec_statement($p);
        $data = array();
        while($w = $dbc->fetch_row($r)) {
            $record = array();
            if (FormLib::get('excel') === '') {
                $record[] = sprintf('<a href="%s%d">%d</a>',$FANNIE_URL."reports/Equity/index.php?memNum=",$w['memnum'],$w['memnum']);
            } else {
                $record[] = $w['memnum'];
            }
            $record[] = $w['LastName'];
            $record[] = $w['FirstName'];
            $record[] = sprintf('%.2f',$w['payments']);
            $record[] = $w['end_date'];
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        ob_start();
        ?>
<form action="EquityAllReport.php" method="get">
<b>Active status</b>:
<select name="memtypes">
    <option value=1><?php echo _('Active Owners'); ?></option>
    <option value=2><?php echo _('Non-termed Owners'); ?></option>
    <option value=3><?php echo _('All Owners'); ?></option>
</select>
<br /><br />
<b>Equity balance</b>:
<select name="owed">
    <option value=1>Any balance</option>
    <option value=2>less than $100</option>
</select>
<br /><br />
<b>As of</b>:
<select name="grain">
    <option value=1>Yesterday</option>
    <option value=2>Right this Second (slower)</option>
</select>
<br /><br />
<input type="submit" name="submit" value="Get Report" />
</form>
        <?php
        return ob_get_clean();

    }

}

FannieDispatch::conditionalExec();

?>
