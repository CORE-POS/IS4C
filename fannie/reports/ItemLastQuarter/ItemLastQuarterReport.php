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

use COREPOS\Fannie\API\lib\Store;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ItemLastQuarterReport extends FannieReportPage 
{
    public $description = '[Item Last Quarter] shows an item\'s weekly sales for the previous 13 weeks
        as both raw totals and as a percentage of overall store sales.';
    public $report_set = 'Movement Reports';
    public $themed = true;

    protected $title = "Fannie : Item Last Quarter Report";
    protected $header = "Item Last Quarter Report";

    protected $report_headers = array('Week', 'Qty', 'Ttl', '% All', '% Super', '% Dept');
    protected $required_fields = array('upc');

    public function report_description_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $prod = new ProductsModel($dbc);
        $prod->upc(BarcodeLib::padUPC($this->form->upc));
        $prod->load();
        return array('Weekly Sales For ' . $prod->upc() . ' ' . $prod->description());
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $upc = $this->form->upc;
        $upc = BarcodeLib::padUPC($upc);
        $store = Store::getIdByIp();

        $query = "SELECT 
                    l.quantity, l.total,
                    l.percentageStoreSales, l.percentageSuperDeptSales,
                    l.percentageDeptSales, l.weekLastQuarterID as wID,
                    w.weekStart, w.weekEnd
                FROM products AS p
                    LEFT JOIN " . $FANNIE_ARCHIVE_DB . $dbc->sep() . "productWeeklyLastQuarter AS l
                        ON p.upc=l.upc AND p.store_id=l.storeID
                    LEFT JOIN " . $FANNIE_ARCHIVE_DB . $dbc->sep() . "weeksLastQuarter AS w
                        ON l.weekLastQuarterID=w.weekLastQuarterID 
                WHERE p.upc = ?
                    AND p.store_id=?
                ORDER BY l.weekLastQuarterID";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($upc, $store));

        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            'Week ' . date('Y-m-d', strtotime($row['weekStart'])) . ' to ' . date('Y-m-d', strtotime($row['weekEnd'])),
            sprintf('%.2f', $row['quantity']),
            sprintf('%.2f', $row['total']),
            sprintf('%.4f%%', $row['percentageStoreSales'] * 100),
            sprintf('%.4f%%', $row['percentageSuperDeptSales'] * 100),
            sprintf('%.4f%%', $row['percentageDeptSales'] * 100),
        );
    }

    public function form_content()
    {
        $this->add_onload_command('$(\'#upc\').focus();');
        return '
            <form action="' . $_SERVER['PHP_SELF'] . '" method="get">
            <div class="form-group form-inline">
                <label>UPC</label> 
                <input type=text name=upc id=upc class="form-control" />
                <button type=submit class="btn btn-default">Get Report</button>
            </div>
            </form>';
    }

    public function readinessCheck()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('ARCHIVE_DB'));
        if ($this->tableExistsReadinessCheck($this->config->get('ARCHIVE_DB'), 'productWeeklyLastQuarter') === false) {
            return false;
        } else {
            $testQ = 'SELECT upc FROM productWeeklyLastQuarter';
            $testQ = $dbc->addSelectLimit($testQ, 1);
            $testR = $dbc->query($testQ);
            if ($dbc->num_rows($testR) == 0) {
                $this->error_text = _('The product sales summary is missing. Run the Summarize Product Sales task.');
                return false;
            }
        }

        return true;
    }

    public function helpContent()
    {
        return '<p>
            Lists an item\'s sales over the previous thirteen weeks
            with its percentage of category sales.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('weekStart'=>'2000-01-01', 'weekEnd'=>'2000-01-06',
            'quantity'=>1, 'total'=>1, 'percentageStoreSales'=>0.1,
            'percentageSuperDeptSales'=>0.1, 'percentageDeptSales'=>0.1);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

