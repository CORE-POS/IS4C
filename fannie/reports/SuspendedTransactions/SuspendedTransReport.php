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

class SuspendedTransReport extends FannieReportPage 
{
    public $description = '[Suspended Transactions Report] lists transactions that were suspended and never resumed.';
    public $report_set = 'Cashiering';

    protected $report_headers = array('Date', 'Receipt', 'UPC', 'Description', 'Total', 'Line#');
    protected $sort_column = 1;
    protected $title = "Fannie : Suspended Transactions Report";
    protected $header = "Suspended Transactions";
    protected $required_fields = array('date');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $date = $this->form->date;

        $prep = $dbc->prepare('
            SELECT datetime,
                emp_no,
                register_no,
                trans_no,
                upc,
                description,
                total,
                trans_id
            FROM suspended
            WHERE datetime BETWEEN ? AND ?');
        $res = $dbc->execute($prep, array($date . ' 00:00:00', $date . ' 23:59:59'));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['datetime'],
                sprintf('%d-%d-%d', $row['emp_no'], $row['register_no'], $row['trans_no']),
                $row['upc'],
                $row['description'],
                sprintf('%.2f', $row['total']),
                $row['trans_id']
            );
        }

        return $data;
    }

    public function form_content()
    {
        return '<form method="get">
            <div class="form-group">
                <label>Date</label>
                <input type="text" class="form-control date-field" name="date" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default btn-core">Submit</button>
            </div>
            </form>';
    }
}

FannieDispatch::conditionalExec();

