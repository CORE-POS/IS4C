<?php
/*******************************************************************************

    Copyright 201666666e Foods Co-op

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

class NewMemsByZipReport extends FannieReportPage 
{
    public $description = '[New Members By Zip Code] lists the number of new owners by zip code for a given period';
    public $report_set = 'Membership';

    protected $title = "Fannie : New Members By Zip Code";
    protected $header = "New Members By Zip Code";
    protected $required_fields = array('date1', 'date2', 'dept');
    protected $report_headers = array('Zip Code', 'Number of Members', 'Total Equity');
    protected $sort_direction = 1;
    protected $sort_column = 1;

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        try {
            $args = array($this->form->date1, $this->form->date2, $this->form->dept);
        } catch (Exception $ex) {
            return array();
        }
        $store = FormLib::get('store', 0);
        $args[] = $store;

        $dlog = DTransactionsModel::selectDLog($this->form->date1, $this->form->date2);
        $prep = $dbc->prepare('
            SELECT LEFT(m.zip, 5) AS zip, 
                COUNT(DISTINCT d.card_no) AS numMembers,
                SUM(total) AS ttl
            FROM ' . $dlog . ' AS d
                INNER JOIN meminfo AS m ON d.card_no=m.card_no
            WHERE d.tdate BETWEEN ? AND ?
                AND d.department=?
                AND ' . DTrans::isStoreID($store, 'd') . '
            GROUP BY LEFT(m.zip, 5)
        ');
        $res = $dbc->execute($prep, $args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            $row['zip'],
            $row['numMembers'],
            sprintf('%.2f', $row['ttl']),
        );
    }

    public function form_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $ret = preg_match_all("/[0-9]+/",$this->config->get('EQUITY_DEPARTMENTS'),$depts);
        if ($ret == 0){
            return '<div class="alert alert-danger">Error: can\'t read Equity department definitions</div>';
        }
        $eq_depts = array_pop($depts);
        
        list($inStr, $args) = $dbc->safeInClause($eq_depts);
        $prep = $dbc->prepare('SELECT dept_no, dept_name FROM departments WHERE dept_no IN (' . $inStr . ') ORDER BY dept_no');
        $res = $dbc->execute($prep, $args);
        $dOpts = array();
        while ($row = $dbc->fetchRow($res)) {
            $dOpts .= sprintf('<option value="%d">%s</option>', $row['dept_no'], $row['dept_name']);
        }

        $stores = FormLib::storePicker();
        $dates = FormLib::standardDateFields();

        return <<<HTML
<form method="get">
    <div class="col-sm-5">
        <div class="form-group">
            <label>Made purchase in</label>
            <select name="dept" class="form-control">
                {$dOpts}
            </select> 
        </div>
        <div class="form-group">
            <label>Store</label>
            {$stores['html']}
        </div>
        <p>
            <button type="submit" class="btn btn-default btn-core">Submit</button>
        </p>
    </div>
    {$dates}
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

