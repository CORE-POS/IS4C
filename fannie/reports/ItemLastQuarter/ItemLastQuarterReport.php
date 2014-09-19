<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
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

class ItemLastQuarterReport extends FannieReportPage 
{
    public $description = '[Item Last Quarter] shows an item\'s weekly sales for the previous 13 weeks
        as both raw totals and as a percentage of overall store sales.';
    public $report_set = 'Movement Reports';

    protected $title = "Fannie : Item Last Quarter Report";
    protected $header = "Item Last Quarter Report";

    protected $report_headers = array('Week', 'Qty', 'Ttl', '% All', '% Super', '% Dept');
    protected $required_fields = array('upc');

    public function report_description_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $prod = new ProductsModel($dbc);
        $prod->upc(BarcodeLib::padUPC(FormLib::get('upc')));
        $prod->load();
        return array('Weekly Sales For ' . $prod->upc() . ' ' . $prod->description());
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $upc = FormLib::get('upc');
        $upc = BarcodeLib::padUPC($upc);

        $query = "SELECT 
                    l.quantity, l.total,
                    l.percentageStoreSales, l.percentageSuperDeptSales,
                    l.percentageDeptSales, l.weekLastQuarterID as wID,
                    w.weekStart, w.weekEnd
                FROM products AS p
                    LEFT JOIN " . $FANNIE_ARCHIVE_DB . $dbc->sep() . "productWeeklyLastQuarter AS l
                        ON p.upc=l.upc
                    LEFT JOIN " . $FANNIE_ARCHIVE_DB . $dbc->sep() . "weeksLastQuarter AS w
                        ON l.weekLastQuarterID=w.weekLastQuarterID 
                WHERE p.upc = ?
                ORDER BY l.weekLastQuarterID";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($upc));

        $data = array();
        while($row = $dbc->fetch_row($result)) {
            $record = array(
                'Week ' . date('Y-m-d', strtotime($row['weekStart'])) . ' to ' . date('Y-m-d', strtotime($row['weekEnd'])),
                sprintf('%.2f', $row['quantity']),
                sprintf('%.2f', $row['total']),
                sprintf('%.4f%%', $row['percentageStoreSales'] * 100),
                sprintf('%.4f%%', $row['percentageSuperDeptSales'] * 100),
                sprintf('%.4f%%', $row['percentageDeptSales'] * 100),
            );
            $data[] = $record;
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        return array();
    }

    public function form_content()
    {
        global $FANNIE_URL;
        return "<form action=\"{$_SERVER['PHP_SELF']}\" method=\"get\">
                <b>UPC</b> <input type=text name=upc id=upc />
                <input type=submit value=\"Get Report\" />
                </form>";
    }

    public function readinessCheck()
    {
        global $FANNIE_ARCHIVE_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_ARCHIVE_DB);
        if (!$dbc->tableExists('productWeeklyLastQuarter')) {
            $this->error_text = _("You are missing an important table") . " ($FANNIE_ARCHIVE_DB.productWeeklyLastQuarter). ";
            $this->error_text .= " Visit the <a href=\"{$FANNIE_URL}install\">Install Page</a> to create it.";
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
}

FannieDispatch::conditionalExec();

