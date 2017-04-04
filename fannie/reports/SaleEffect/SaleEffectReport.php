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

class SaleEffectReport extends FannieReportPage 
{
    public $description = '[Sale Effect] shows movement discrepancies when an item is on sale vs. not on sale.';
    public $report_set = 'Batches';

    protected $title = "Fannie : Sale Effect Report";
    protected $header = "Sale Effect Report";
    protected $report_headers = array('UPC','Brand','Item','Dept#','Dept Name','Sale Qty', 'Sale Avg', 'Retail Qty', 'Retail Avg');
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
                t.store_id,
                p.brand,
                p.description,
                t.department,
                d.dept_name,
                MIN(tdate) AS firstSold,
                MAX(tdate) AS lastSold,
                SUM(CASE WHEN t.discounttype=0 THEN t.quantity ELSE 0 END) AS retailQty,
                SUM(CASE WHEN t.discounttype<>0 THEN t.quantity ELSE 0 END) AS saleQty
            {$parts['query']}
                AND trans_type='I'
                AND trans_status <> 'R'
                AND charflag <> 'SO'
            GROUP BY t.upc,
                p.brand,
                p.description,
                t.department,
                d.dept_name";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $parts['args']);

        $daysP = $dbc->prepare("
            SELECT YEAR(tdate),
                MONTH(tdate),
                DAY(tdate)
            FROM {$dlog}
            WHERE upc=?
                AND store_id=?
                AND discounttype <> 0
                AND tdate BETWEEN ? AND ?
            GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate)");

        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = $this->rowToRecord($row, $dbc, $daysP);
        }

        return $data;
    }

    private function rowToRecord($row, $dbc, $daysP)
    {
        $periodStart = new DateTime($row['firstSold']);
        $periodEnd = new DateTime($row['lastSold']);
        $diff = $periodEnd->diff($periodStart);
        $numDays = $diff->days + 1;
        $daysR = $dbc->execute($daysP, array($row['upc'], $row['store_id'], $this->form->date1 . ' 00:00:00', $this->form->date2 . ' 23:59:59'));
        $saleDays = $dbc->numRows($daysR);
        $nonSaleDays = $numDays - $saleDays;

        return array(
            $row['upc'],
            $row['brand'] === null ? '' : $row['brand'],
            $row['description'],
            $row['department'],
            $row['dept_name'],
            sprintf('%.2f', $row['saleQty']),
            sprintf('%.2f', $saleDays == 0 ? 0 : $row['saleQty'] / $saleDays),
            sprintf('%.2f', $row['retailQty']),
            sprintf('%.2f', $nonSaleDays == 0 ? 0 : $row['retailQty'] / $nonSaleDays),
        );
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

