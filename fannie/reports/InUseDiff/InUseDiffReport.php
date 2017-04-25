<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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

class InUseDiffReport extends FannieReportPage 
{
    public $description = '[In Use Diff] shows items with differing inUse status at different stores.';
    public $report_set = 'Multistore';

    protected $report_headers = array('UPC', 'Brand', 'Description', 'Dept#', 'Dept');
    protected $title = "Fannie : In Use Diff Report";
    protected $header = "In Use Diff Report";
    protected $required_fields = array('buyer');

    public function fetch_report_data()
    {
        try {
            $super = $this->form->buyer;
            $dept1 = $this->form->deptStart;
            $dept2 = $this->form->deptEnd;
        } catch (Exception $ex) {
            return array();
        }

        $dbc = $this->connection;
        $model = new StoresModel($dbc);
        $model->hasOwnItems(1);
        $stores = array();
        foreach ($model->find() as $obj) {
            $stores[$obj->storeID()] = $obj->description();
        }

        $where = ' 1=1 ';
        $args = array();
        if ($super == -2) {
            $where .= ' AND m.superID <> 0 ';
        } elseif ($super >= 0) {
            $where .= ' AND m.superID=? ';
            $args[] = $super;
        }

        $storeCols = '';
        foreach ($stores as $id => $name) {
            $intID = (int)$id;
            $storeCols .= "MAX(CASE WHEN store_id={$intID} THEN inUse ELSE 0 END) as store{$intID},";
            $storeCols .= "MAX(CASE WHEN store_id={$intID} THEN auto_par ELSE 0 END) AS avg{$intID},";
            $this->report_headers[] = $name;
            $this->report_headers[] = 'Daily Avg.';
        }

        $query = '
            SELECT p.upc,
                MAX(p.brand) AS brand,
                MAX(p.description) AS description,
                MAX(d.dept_no) AS dept_no,
                MAX(d.dept_name) AS dept_name,
                ' . $storeCols . '
                1 as commaKill
            FROM products AS p
                LEFT JOIN departments AS d ON p.department=d.dept_no ';
        if ($super == -2) {
            $query .= ' LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID ';
        } elseif ($super >= 0) {
            $query .= ' LEFT JOIN superdepts AS m ON p.department=m.dept_ID ';
        }
        $query .= ' 
            WHERE ' . $where . '
                AND p.department BETWEEN ? AND ? 
            GROUP BY upc
            HAVING MAX(inUse) <> MIN(inUse)';
        $args[] = $dept1;
        $args[] = $dept2;

        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $record = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row['dept_no'],
                $row['dept_name'],
            );
            foreach ($stores as $id=>$name) {
                $record[] = $row['store' . $id];
                $record[] = sprintf('%.2f', $row['avg'.$id]);
            }
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        $depts = FormLib::standardDepartmentFields('buyer');
        $stores = FormLib::storePicker();
        return <<<HTML
<form method="get" class="form-horizontal">
    <div class="row">
        <div class="col-sm-8">
            {$depts}
        </div>
    </div>
    <p>
        <button type="submit" class="btn btn-default btn-core">Submit</button>
        <button type="reset" class="btn btn-default btn-reset"
            onclick="$('#super-id').val('').trigger('change');">Start Over</button>
    </p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

