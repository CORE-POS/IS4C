<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class CwGrowthReport extends FannieReportPage 
{
    protected $header = 'Year over Year Growth';
    protected $title = 'Year over Year Growth';
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Category', 'This Year', 'Previous Year', 'Growth');

    public function fetch_report_data()
    {
        $dbc = $this->connection;

        $ts1 = strtotime(FormLib::get('date1'));
        $ts2 = strtotime(FormLib::get('date2'));
        $start = date('Ymd', $ts1);
        $end = date('Ymd', $ts2);
        $store = FormLib::get('store');
        $supers = FormLib::get('super', array());
        list($inStr, $args) = $dbc->safeInClause($supers);

        $prep = $dbc->prepare("
            SELECT n.super_name, s.superID,
                SUM(d.total) AS ttl
            FROM " . FannieDB::fqn('sumDeptSalesByDay', 'plugin:WarehouseDatabase') . " AS d
                INNER JOIN superdepts AS s ON d.department=s.dept_ID
                INNER JOIN superDeptNames AS n ON s.superID=n.superID
            WHERE s.superID IN ({$inStr})
                AND d.store_id=?
                AND d.date_id BETWEEN ? AND ?
            GROUP BY n.super_name, s.superID");
        $args[] = $store;
        $args[] = $start;
        $args[] = $end; 

        $startLY = date('Ymd', mktime(0, 0, 0, date('n', $ts1), date('j', $ts1), date('Y', $ts1) - 1));
        $endLY = date('Ymd', mktime(0, 0, 0, date('n', $ts2), date('j', $ts2), date('Y', $ts2) - 1));
        $prevP = $dbc->prepare("
            SELECT 
                SUM(d.total) AS ttl
            FROM " . FannieDB::fqn('sumDeptSalesByDay', 'plugin:WarehouseDatabase') . " AS d
                INNER JOIN superdepts AS s ON d.department=s.dept_ID
            WHERE s.superID = ?
                AND d.store_id=?
                AND d.date_id BETWEEN ? AND ?
            GROUP BY s.superID");

        $res = $dbc->execute($prep, $args);
        $data[] = array();
        while ($row = $dbc->fetchRow($res)) {
            $superID = $row['superID'];
            $name = $row['super_name'];
            $cur = $row['ttl'];
            $prev = $dbc->getValue($prevP, array($superID, $store, $startLY, $endLY));
            $data[] = array(
                $name,
                number_format($cur),
                number_format($prev),
                sprintf('%.2f%%', ($cur - $prev) / $cur * 100),
            );
        }

        return $data;
    }

    public function form_content()
    {
        $stores = FormLib::storePicker();
        $dates = FormLib::standardDateFields();
        $model = new SuperDeptNamesModel($this->connection);
        $opts = '';
        foreach ($model->find() as $s) {
            $opts .= sprintf('<option value="%d">%s</option>', $s->superID(), $s->super_name());
        }

        return <<<HTML
<form method="get">
    <div class="row">
        <div class="col-sm-5">
            <div class="form-group">
                <label>Super Department(s)</label>
                <select name="super[]" multiple size="10" class="form-control">
                    {$opts}
                </select>
            </div>
            <div class="form-group">
                <label>Store</label>
                {$stores['html']}
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Get Report</button>
            </div>
        </div>
        {$dates}
    </div>
</form>
HTML;

    }
}

FannieDispatch::conditionalExec();

