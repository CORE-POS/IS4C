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
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class SaleValueReport extends FannieReportPage 
{
    public $description = '[Sale Value] shows the dollar value given in sales batch pricing';

    protected $title = "Fannie : Sale Value Report";
    protected $header = "Sale Value Report";
    public $report_set = 'Batches';
    protected $report_headers = array('Group', 'Promo Total Sales', 'Discount off Retail');
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $fromWhere = FormLib::standardItemFromWhere();
        $from = $fromWhere['query'];
        $args = $fromWhere['args'];
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
            $display = $this->form->displayBy;
        } catch (Exception $ex) {
            return array();
        }

        $groupBy = $dbc->dateymd('tdate');
        if ($display === 'Department') {
            $groupBy = $dbc->concat('t.department', "' '", 'd.dept_name');
        }

        $query = "SELECT {$groupBy} AS display,
                SUM(total) AS sales,
                SUM(t.quantity * (regPrice-unitPrice)) AS diff
            {$from}
                AND t.charflag <> 'SO'
                AND t.discounttype <> 0
                AND t.regPrice <> t.unitPrice
            GROUP BY {$groupBy}";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['display'],
                sprintf('%.2f', $row['sales']),
                sprintf('%.2f', $row['diff']),
            );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sum = array_reduce($data, function($c, $i) { return $c + $i[2]; });

        return array('Total', '', $sum);
    }

    public function form_content()
    {
        $form = FormLib::dateAndDepartmentForm(true);
        $extraField = <<<HTML
<div class="form-group">
    <label class="col-sm-4 control-label">Display by</label>
    <div class="col-sm-8">
        <select name="displayBy" class="form-control">
            <option>Date</option>
            <option>Department</option>  
        </select>
    </div>
</div>
HTML;
        $extraField = json_encode($extraField);
        $this->addOnloadCommand("\$('#date-dept-form-left-col').html({$extraField});");

        return $form;
    }

    public function helpContent()
    {
        return '<p>
            List the difference between sale price and retail price
            for a given date range. Results can be displayed by date
            or by department.
            </p>';
    }
}

FannieDispatch::conditionalExec();

