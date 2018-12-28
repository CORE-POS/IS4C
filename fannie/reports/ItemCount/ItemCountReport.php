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

class ItemCountReport extends FannieReportPage 
{
    public $description = '[Item Count] shows the number of unique items sold in a given time period';
    public $report_set = 'Movement';

    protected $title = "Fannie : Item Count Report";
    protected $header = "Item Count Report";
    protected $report_headers = array('Department','Dept#','Items','Qty Sales','$ Sales');
    protected $required_fields = array('date1', 'date2');
    protected $new_tablesorter = true;

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
            SELECT t.department,
                d.dept_name,
                count(distinct t.upc) AS items,
                SUM(t.total) AS ttl,
                " . DTrans::sumQuantity('t') . " AS qty
            {$parts['query']}
                AND trans_type='I'
                AND trans_status <> 'R'
                AND charflag <> 'SO'
            GROUP BY t.department,
                d.dept_name";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $parts['args']);

        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['dept_name'],
                $row['department'],
                $row['items'],
                sprintf('%.2f', $row['qty']),
                sprintf('%.2f', $row['ttl']),
            );
        }

        return $data;
    }

    public function form_content()
    {
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

