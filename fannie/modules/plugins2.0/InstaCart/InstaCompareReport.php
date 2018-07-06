<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class InstaCompareReport extends FannieReportPage 
{
    public $description = '[InstaCompare Report] shows comparitive pricing';
    public $report_set = 'Operational Data';

    protected $title = "Fannie : InstaCompare Report";
    protected $header = "InstaCompare Report";
    protected $report_headers = array('UPC','Brand','Item','Dept#','Dept Name','Our Price','Their Price', 'Diff');
    protected $required_fields = array('buyer');
    protected $new_tablesorter = true;

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $info = FormLib::standardItemFromWhere();

        $superID = FormLib::get('buyer');
        $depts = FormLib::get('departments', array());
        $dStart = FormLib::get('deptStart');
        $dEnd = FormLib::get('deptEnd');
        $subs = FormLib::get('subdepts', array());
        $store = FormLib::get('store');
        $asLC = FormLib::get('lc', false);
        $superTable = ($superID >= 0) ? 'superdepts' : 'MasterSuperDepts';
        $where = '1=1';
        $args = array();
        if ($superID >=0) {
            $where .= ' AND m.superID=? ';
            $args[] = $superID;
        } elseif ($superID == -2) {
            $where .= ' AND m.superID <> 0 ';
        }
        if (count($depts) > 0) {
            list($inStr, $args) = $dbc->safeInClause($depts, $args);
            $where .= " AND p.department IN ($inStr) ";
        } elseif ($dStart !== '' && $dEnd !== '') {
            $where .= ' AND p.department BETWEEN ? AND ? ';
            $args[] = $dStart;
            $args[] = $dEnd;
        }
        if (count($subs) > 0) {
            list($inStr, $args) = $dbc->safeInClause($subs, $args);
            $where .= " AND p.subdept IN ($inStr) ";
        }

        $query = "
            SELECT p.upc,
                p.brand,
                p.description,
                p.department,
                d.dept_name,
                p.normal_price,
                i.price,
                i.modified,
                i.url
            FROM products AS p
                INNER JOIN " . FannieDB::fqn('InstaCompares', 'plugin:InstaCartDB') . " AS i ON p.upc=i.upc
                LEFT JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN subdepts AS b ON p.subdept=b.subdept_no 
                LEFT JOIN {$superTable} AS m ON p.department=m.dept_ID
            WHERE {$where}
                AND p.store_id=1";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $date1 = date('Y-m-d', strtotime('90 days ago'));
        $date2 = date('Y-m-d', strtotime('yesterday'));

        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $record = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row['department'],
                $row['dept_name'],
                sprintf('%.2f', $row['normal_price']),
                sprintf('%.2f', $row['price']),
                sprintf('%.2f', $row['normal_price'] - $row['price']),
            );
            if ($row['normal_price'] - $row['price'] > 0.005) {
                $record['meta'] = FannieReportPage::META_COLOR;
                $record['meta_background'] = '#ffe4e1';
            } elseif ($row['normal_price'] - $row['price'] < 0.005) {
                $record['meta'] = FannieReportPage::META_COLOR;
                $record['meta_background'] = '#edf8d8';
            }
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        $dept = FormLib::standardDepartmentFields('buyer');
        return <<<HTML
<form method="get" class="form-horizontal">
<div class="col-sm-6">
    <p>{$dept}</p>
    <p>
        <button type="submit" class="btn btn-default btn-core">Submit</button>
        <button type="reset" class="btn btn-default btn-reset"
            onclick="$('#super-id').val('').trigger('change');">Start Over</button>
    </p>
</div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

