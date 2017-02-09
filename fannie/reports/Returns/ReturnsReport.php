<?php
/*******************************************************************************

    Copyright 2017 Whole Foods Co-op

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

class ReturnsReport extends FannieReportPage 
{
    public $description = '[Returns Report] is a movement report limited to just returns';
    public $report_set = 'Movement Reports';

    protected $title = "Fannie : Returns Report";
    protected $header = "Returns Report";
    protected $report_headers = array('UPC','Brand','Item','Dept#','Dept Name','Qty','Total');
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        try {
            $dlog = DTransactionsModel::selectDlog($this->form->date1, $this->form->date2);
        } catch (Exception $ex) {
            return array();
        }

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $parts = FormLib::standardItemFromWhere();
        $query = "
            SELECT t.upc,
                p.brand,
                p.description,
                t.department,
                d.dept_name,
                SUM(t.quantity) AS qty,
                SUM(t.total) AS ttl
            {$parts['query']}
                AND trans_status = 'R'
            GROUP BY t.upc,
                p.brand,
                p.description,
                t.department,
                d.dept_name";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $parts['args']);

        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            $row['upc'],
            $row['brand'] === null ? '' : $row['brand'],
            $row['description'] === null ? '' : $row['description'],
            $row['department'],
            $row['dept_name'],
            sprintf('%.2f', $row['qty']),
            sprintf('%.2f', $row['ttl']),
        );
    }

    public function calculate_footers($data)
    {
        $sums = array_reduce($data, function($c, $i) {
            $c[0] += $i[5];
            $c[1] += $i[6];
            return $c;
        }, array(0, 0));

        return array('Total', null, null, null, null, $sums[0], $sums[1]);
    }
    
    public function form_content()
    {
        ob_start();
        ?>
        <form method="get" action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
        <div class="row">
            <?php echo FormLib::standardItemFields(); ?>
            <?php echo FormLib::standardDateFields(); ?>
        </div>
        <div class="row form-horizontal">
            <div class="form-group">
                <label class="col-sm-1 control-label">Store</label>
                <div class="col-sm-2">
                    <?php $s = FormLib::storePicker(); echo $s['html']; ?>
                </div>
                <label class="col-sm-2 control-label">
                    <input type="checkbox" name="excel" value="csv" />
                    Excel
                </label>
            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-default btn-core">Get Report</button>
            <button type="reset" class="btn btn-default btn-reset">Reset Form</button>
        </p>
        </form>
        <?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            The returns report shows movement data limited to only return rings.
            This can include both UPCs and open rings.
            </p>';
    }
}

FannieDispatch::conditionalExec();

