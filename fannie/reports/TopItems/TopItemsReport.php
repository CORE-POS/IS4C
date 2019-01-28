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

class TopItemsReport extends FannieReportPage 
{
    public $description = '[Top Items] shows the highest movement items';
    public $report_set = 'Movement';

    protected $title = "Fannie : Top Items Report";
    protected $header = "Top Items Report";
    protected $report_headers = array('UPC','Brand','Item','Dept#','Dept Name','Qty', '$ Sales');
    protected $required_fields = array('date1', 'date2');

    protected $sort_column = 5;
    protected $sort_direction = 1;

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
                " . DTrans::sumQuantity('t') . " AS qty,
                SUM(total) AS ttl
            {$parts['query']}
                AND trans_type='I'
                AND charflag <> 'SO'
            GROUP BY t.upc,
                p.brand,
                p.description,
                t.department,
                d.dept_name";
        if (FormLib::get('noSales')) {
            $query = str_replace('t.store_id =', '-999 <', $query);
            $query .= ' HAVING SUM(CASE WHEN t.store_id=? THEN t.quantity ELSE 0 END) = 0 ';
            $parts['args'][] = FormLib::get('noSales');
        }
        $query .= " ORDER BY " . DTrans::sumQuantity('t') . " DESC";
        $query = $dbc->addSelectLimit($query, FormLib::get('top'));
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $parts['args']);

        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row, $dbc, $daysP)
    {

        return array(
            $row['upc'],
            $row['brand'] === null ? '' : $row['brand'],
            $row['description'],
            $row['department'],
            $row['dept_name'],
            sprintf('%.2f', $row['qty']),
            sprintf('%.2f', $row['ttl']),
        );
    }
    
    public function form_content()
    {
        $model = new StoresModel($this->connection);
        $model->hasOwnItems(1);
        $opts = $model->toOptions();
        $extra = <<<HTML
<div class="form-group">
    <label class="col-sm-4 control-label">With no Sales at</label>
    <div class="col-sm-8">
        <select name="noSales" class="form-control">
            <option value="">n/a</option>
            {$opts}
        </select>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-4 control-label"># of Items</label>
    <div class="col-sm-8">
        <input class="form-control" name="top" value="50" />
    </div>
</div>
HTML;
        $extra = json_encode($extra);
        $this->addOnloadCommand("\$('#date-dept-form-left-col').append({$extra});");
        return FormLib::dateAndDepartmentForm(true);
    }

    public function helpContent()
    {
        return '<p>
            Sale effect shows items\' movement when in a sales batch and
            when not in a sales batch separately. Averages are based on the
            number of days an item was and was not on sale not the total
            number of days in the reporting period.
            </p>';
    }
}

FannieDispatch::conditionalExec();

