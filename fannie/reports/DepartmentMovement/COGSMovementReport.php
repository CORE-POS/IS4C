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

use COREPOS\Fannie\API\lib\Operators as Op;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class COGSMovementReport extends FannieReportPage 
{
    protected $report_cache = 'none';
    protected $title = "Fannie :  Movement Report";
    protected $header = "Cost of Goods Movement Report";

    protected $required_fields = array('date1', 'date2');

    public $description = '[COGS Movement] shows movement with cost of goods by account';
    public $report_set = 'Movement Reports';
    protected $report_headers = array('Sales Code', 'Retail', 'Cost', 'Margin');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $onlyRD = FormLib::get('onlyRD', false);
        $query = '';
        $from_where = FormLib::standardItemFromWhere();
        $from_where['query'] = str_replace('LEFT JOIN vendorItems AS i ON p.upc=i.upc AND p.default_vendor_id=i.vendorID', '', $from_where['query']);
        $query = "
            SELECT 
                d.salesCode,
                SUM(total) AS retail,
                SUM(CASE WHEN ABS(t.cost) < 1000 THEN t.cost ELSE d.margin * total END) AS cogs
            " . $from_where['query'] . "
            GROUP BY d.salesCode
            ORDER BY d.salesCode";

        $prep = $dbc->prepare($query);
        try {
            $result = $dbc->execute($prep, $from_where['args']);
        } catch (Exception $ex) {
            // MySQL 5.6 doesn't GROUP BY correctly
            return array();
        }
        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            $data[] = array(
                $row['salesCode'],
                sprintf('%.2f', $row['retail']),
                sprintf('%.2f', $row['cogs']),
                sprintf('%.2f', Op::div($row['retail'] - $row['cogs'], $row['retail']) * 100),
            );
        }

        return $data;
    }

    private function percent($a, $b)
    {
        if ($b == 0) return 0;
        return sprintf('%.2f', 100 * ($a/$b));
    }

    public function calculate_footers($data)
    {
        return array();
    }

    public function form_content()
    {
        ob_start();
        ?>
        <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
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
                <label class="col-sm-1 control-label">
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
}

FannieDispatch::conditionalExec();


