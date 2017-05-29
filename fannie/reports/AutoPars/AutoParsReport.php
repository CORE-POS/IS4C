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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class AutoParsReport extends FannieReportPage 
{
    public $description = '[Auto Pars Reports] shows approximate daily movement for items when not on sale';
    public $report_set = 'Operational Data';

    protected $title = "Fannie : Auto Pars Report";
    protected $header = "Auto Pars Report";
    protected $report_headers = array('UPC','Brand','Item','Dept#','Dept Name','Daily Par');
    protected $required_fields = array('buyer');
    protected $sort_direction = 1;
    protected $sort_column = 5;

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
                p.auto_par
            FROM products AS p
                LEFT JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN subdepts AS b ON p.subdept=b.subdept_no 
                LEFT JOIN {$superTable} AS m ON p.department=m.dept_ID
            WHERE {$where}
                AND p.store_id=?
                AND (p.inUse=1 OR p.auto_par > 0)
            ORDER BY auto_par DESC";
        if ($asLC) {
            $query = "
                SELECT u.likeCode AS upc,
                    'Likecode' AS brand,
                    l.likeCodeDesc AS description,
                    p.department,
                    d.dept_name,
                    SUM(p.auto_par) AS auto_par
                FROM products AS p
                    LEFT JOIN departments AS d ON p.department=d.dept_no
                    LEFT JOIN subdepts AS b ON p.subdept=b.subdept_no 
                    LEFT JOIN {$superTable} AS m ON p.department=m.dept_ID
                    LEFT JOIN upcLike AS u ON p.upc=u.upc
                    LEFT JOIN likeCodes AS l ON u.likeCode=l.likeCode
                WHERE {$where}
                    AND p.store_id=?
                    AND (p.inUse=1 OR p.auto_par > 0)
                GROUP BY u.likeCode,
                    l.likeCodeDesc,
                    p.department,
                    d.dept_name
                ORDER BY SUM(p.auto_par) DESC";
            $this->report_headers[0] = 'Likecode';
        }
        $args[] = $store;
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $date1 = date('Y-m-d', strtotime('90 days ago'));
        $date2 = date('Y-m-d', strtotime('yesterday'));

        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                sprintf('<a href="../ProductMovement/ProductMovementModular.php?date1=%s&date2=%s&upc=%s&store=%d">%s</a>',
                    $date1, $date2, $row['upc'], $store, $row['upc']),
                $row['brand'],
                $row['description'],
                $row['department'],
                $row['dept_name'],
                sprintf('%.2f', $row['auto_par']),
            );
            if (count($data) > 1000) {
                break;
            }
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            date('Y-m-d', mktime(0, 0, 0, $row['month'], $row['day'], $row['year'])),
            $row['upc'],
            $row['brand'],
            $row['description'],
            $row['department'],
            $row['dept_name'],
            $row['salesCode'],
            $row['super_name'],
            sprintf('%.2f', $row['quantity']),
            sprintf('%.2f', $row['cost']),
            sprintf('%.2f', $row['total']),
            empty($row['shrinkReason']) ? 'n/a' : $row['shrinkReason'],
            $row['charflag'] == 'C' ? 'No' : 'Yes',
        );
    }
    
    public function form_content()
    {
        $dept = FormLib::standardDepartmentFields('buyer');
        $stores = FormLib::storePicker();
        return <<<HTML
<form method="get" class="form-horizontal">
<div class="col-sm-6">
    <p>{$dept}</p>
    <p>{$stores['html']}</p>
    <p><label><input type="checkbox" value="1" name="lc" /> As likecodes</p>
    <p>
        <button type="submit" class="btn btn-default btn-core">Submit</button>
        <button type="reset" class="btn btn-default btn-reset"
            onclick="$('#super-id').val('').trigger('change');">Start Over</button>
    </p>
</div>
</form>
HTML;
    }

    public function helpContent()
    {
        return '<p>
            Lists approximate daily movement for each item when the impact
            of promotional sales is ignored. The corresponding "Auto Pars"
            scheduled task must be enabled to populate data for this report.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('month'=>1, 'day'=>1, 'year'=>2000, 'upc'=>'4011',
            'brand'=>'b','description'=>'test', 'department'=>1, 'dept_name'=>'test',
            'salesCode'=>100, 'super_name'=>'test', 'quantity'=>1,
            'cost'=>1, 'total'=>1, 'shrinkReason'=>'test', 'charflag'=>'C');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

