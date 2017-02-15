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

class OpenRingsReport extends FannieReportPage 
{
    public $description = '[Open Rings] shows UPC-less sales for a department or group of departments over a given date range.';
    public $report_set = 'Transaction Reports';

    protected $title = "Fannie : Open Rings Report";
    protected $header = "Open Rings Report";

    protected $report_headers = array('Date', 'Open Rings Sales', '# of Open Rings', 'Percentage');
    protected $required_fields = array('date1', 'date2');

    public function report_description_content()
    {
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $buyer = FormLib::get('buyer', '');
    
        $ret = array();
        if ($buyer === '') {
            $ret[] = 'Department '.$deptStart.' to '.$deptEnd;
        } elseif ($buyer == -1) {
            $ret[] = 'All Super Departments';
        } elseif ($buyer == -2) {
            $ret[] = 'All Retail Departments';
        } else {
            $ret[] = 'Super Department '.$buyer;
        }

        return $ret;
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $deptMulti = FormLib::get('departments', array());
    
        $buyer = FormLib::get('buyer', '');

        // args/parameters differ with super
        // vs regular department
        $args = array($date1.' 00:00:00', $date2.' 23:59:59');
        $where = ' 1=1 ';
        if ($buyer !== '') {
            if ($buyer > -1) {
                $where .= ' AND s.superID=? ';
                $args[] = $buyer;
            } elseif ($buyer == -2) {
                $where .= ' AND s.superID <> 0 ';
            }
        }
        if ($buyer != -1) {
            list($conditional, $args) = DTrans::departmentClause($deptStart, $deptEnd, $deptMulti, $args);
            $where .= $conditional;
        }

        $tempTables = array(
            'connection' => $dbc,
            'clauses' => array(
                array(
                    'sql' => 'trans_type IN (?, ?)',
                    'params' => array('I', 'D'),
                ),
            ),
        );
        $dlog = DTransactionsModel::selectDlog($date1, $date2, $tempTables);

        $query = "SELECT year(tdate),month(tdate),day(tdate),
          SUM(CASE WHEN trans_type='D' THEN total ELSE 0 END) as total,
          SUM(CASE WHEN trans_type='D' THEN abs(quantity) ELSE 0 END) as qty,
          SUM(CASE WHEN trans_type='D' THEN 1.0 ELSE 0.0 END) /
          SUM(CASE WHEN trans_type IN ('I','D') THEN 1.0 ELSE 0.0 END) as percentage
          FROM $dlog as d ";
        // join only needed with specific buyer
        if ($buyer !== '' && $buyer > -1) {
            $query .= 'LEFT JOIN superdepts AS s ON d.department=s.dept_ID ';
        } elseif ($buyer == -2) {
            $query .= 'LEFT JOIN MasterSuperDepts AS s ON d.department=s.dept_ID ';
        }
        $query .= "WHERE trans_type IN ('I','D')
            AND tdate BETWEEN ? AND ?
            AND $where
            GROUP BY year(tdate),month(tdate),day(tdate)
            ORDER BY year(tdate),month(tdate),day(tdate)";

        $prep = $dbc->prepare($query);
        $result = $dbc->execute($query, $args);

        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }
    
    private function rowToRecord($row)
    {
        return array(
            sprintf('%d/%d/%d', $row[1], $row[2], $row[0]),
            sprintf('%.2f', $row['total']),
            sprintf('%.2f', $row['qty']),
            sprintf('%.2f%%', $row['percentage']*100),
        );
    }

    public function calculate_footers($data)
    {
        if (count($data) == 0) {
            return array();
        }

        $sum_qty = 0.0;
        $sum_ttl = 0.0;
        $sum_percents = 0.0;
        foreach($data as $row) {
            $sum_qty += $row[2];
            $sum_ttl += $row[1];
            $sum_percents += $row[3];
        }

        $avg = $sum_percents / ((float)count($data));

        return array('Totals', sprintf('%.2f',$sum_qty), sprintf('%.2f',$sum_ttl), sprintf('%.2f%%', $avg));
    }

    public function form_content()
    {
        ob_start();
        ?>
<form method="get" class="form-horizontal">
<div class="row">
    <div class="col-sm-6">
        <?php echo FormLib::standardDepartmentFields('buyer'); ?>
        <div class="form-group">
            <label class="control-label col-sm-4">Save to Excel
                <input type=checkbox name=excel id=excel value=1>
            </label>
            <label class="col-sm-4 control-label">Store</label>
            <div class="col-sm-4">
                <?php $ret=FormLib::storePicker();echo $ret['html']; ?>
            </div>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <label class="col-sm-4 control-label">Start Date</label>
            <div class="col-sm-8">
                <input type=text id=date1 name=date1 class="form-control date-field" required />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">End Date</label>
            <div class="col-sm-8">
                <input type=text id=date2 name=date2 class="form-control date-field" required />
            </div>
        </div>
        <div class="form-group">
            <?php echo FormLib::date_range_picker(); ?>                            
        </div>
    </div>
</div>
<p>
    <button type=submit name=submit value="Submit" class="btn btn-default btn-core">Submit</button>
    <button type=reset name=reset class="btn btn-default btn-reset"
        onclick="$('#super-id').val('').trigger('change');">Start Over</button>
</p>
</form>
        <?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Open Rings are dollar amounts simply tied to a department as
            opposed to an item with a proper UPC. The report shows the number
            of open rings and value of those rings for each day in the date range.
            The percentage is relative to all items sold in that set of departments
            that day.</p>';
    }

    public function unitTest($phpunit)
    {
        $data = array(0=>2000, 1=>1, 2=>2, 'total'=>1, 'qty'=>1, 'percentage'=>1);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
        $phpunit->assertInternalType('array', $this->calculate_footers($this->dekey_array(array($data))));
    }
}

FannieDispatch::conditionalExec();

