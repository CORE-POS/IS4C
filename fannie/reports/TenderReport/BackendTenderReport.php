<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class BackendTenderReport extends FannieReportPage
{
    public $description = '[Tender Report] receates a basic tender report for an employee or register';
    public $report_set = 'Tenders';

    protected $title = "Fannie : Tender Report";
    protected $header = "Tender Report";
    protected $multi_report_mode = true;

    protected $report_headers = array('Date', 'Receipt#', 'Type', 'Amount');
    protected $required_fields = array('date1');

    private function where($emp, $reg)
    {
        if ($emp) {
            return array(' emp_no=? ', array($emp));
        } else {
            return array(' register_no=? ', array($reg));
        }
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $date1 = $this->form->date1;
        try {
            $emp = $this->form->emp;
        } catch (Exception $ex) {
            $emp = false;
        }
        try {
            $reg = $this->form->reg;
        } catch (Exception $ex) {
            $reg = false;
        }

        if (!$emp && !$reg) {
            echo '<strong>Employee or register # is required</strong>';
            return array(array());
        }

        $dlog = DTransactionsModel::selectDlog($date1);
        list($where, $args) = $this->where($emp, $reg);

        $typeP = $dbc->prepare('
            SELECT trans_subtype
            FROM ' . $dlog . ' AS d
            WHERE ' . $where . ' 
                AND tdate BETWEEN ? AND ?
                AND trans_type=\'T\'
            GROUP BY trans_subtype');
        $args[] = $date1 . ' 00:00:00';
        $args[] = $date1 . ' 23:59:59';
        $typeR = $dbc->execute($typeP, $args);

        $detailP = $dbc->prepare('
            SELECT tdate,
                trans_num,
                description,
                -1*total AS total
            FROM ' . $dlog . ' AS d
            WHERE ' . $where . '
                AND tdate BETWEEN ? AND ?
                AND trans_type=\'T\'
                AND total <> 0
                AND trans_subtype=?
            ORDER BY tdate, trans_id');

        $data = array();
        while ($row = $dbc->fetchRow($typeR)) {
            $data[] = $this->getSubReport($dbc, $detailP, $args, $row['trans_subtype']);
        }
        if (count($data) == 0) {
            $data[] = array();
        }

        return $data;
    }

    private function getSubReport($dbc, $detailP, $args, $type)
    {
        $args[] = $type;
        $res = $dbc->execute($detailP, $args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['tdate'],
                $row['trans_num'],
                $row['description'],
                sprintf('%.2f', $row['total']),
            );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sum = 0.0;
        foreach($data as $row) {
            $sum += $row[3];
        }

        return array('Total', '', null, $sum);
    }

    public function form_content()
    {
        ob_start();
        ?>
<form method="get" action="BackendTenderReport.php">
    <div class="form-group"> 
        <label>Employee #</label>
        <input type="text" name="emp" class="form-control"
            placeholder="Employee or Register is required" />
    </div>
    <div class="form-group"> 
        <label>Register #</label>
        <input type="text" name="reg" class="form-control"
            placeholder="Employee or Register is required" />
    </div>
    <div class="form-group"> 
        <label>Date</label>
        <input type=text id=date1 name=date1 required
            class="form-control date-field" />
    </div>
    <div class="form-group"> 
        <button type=submit name=submit value="Submit"
            class="btn btn-default btn-core">Submit</button>
        <button type=reset name=reset value="Start Over"
            class="btn btn-default btn-reset">Start Over</button>
    </div>
</form>
        <?php

        return ob_get_clean();
    }
    
    public function helpContent()
    {
        return '<p>
            Lists all tenders for an employee or register
            on a given day.
            </p>';
    }
}

FannieDispatch::conditionalExec();

