<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class CWTransactionsReport extends FannieReportPage 
{
    public $description = '[Transactions Report] lists information about basket size and member type.
        Requires CoreWarehouse plugin.';
    public $themed = true;

    protected $title = 'Transactions Report';
    protected $header = 'Transactions Report';
    protected $required_fields = array('date1', 'date2');

    protected $report_headers = array(
        'Customer Type',
        'Is Owner',
        '# Transactions',
        'Avg Paid ($)',
        'Avg # Items',
        'Avg Item Value ($)',
        'Avg # Retail Items',
        'Avg Retail Value ($)',
    );

    function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $warehouseDB = $FANNIE_PLUGIN_SETTINGS['WarehouseDatabase'].$dbc->sep();

        $query = '
            SELECT COUNT(*) AS numTransactions,
                AVG(-tenderTotal) AS avgTenderTotal,
                AVG(retailQty+nonRetailQty) AS avgItemQty,
                AVG(retailTotal+nonRetailtotal) AS avgItemTotal,
                AVG(retailQty) AS avgRetailQty,
                AVG(retailTotal) AS avgRetailTotal,
                m.memDesc,
                m.custdataType
            FROM ' . $warehouseDB . 'transactionSummary AS t
                LEFT JOIN memtype AS m ON t.memType=m.memType
            WHERE date_id BETWEEN ? AND ?
            GROUP BY t.memType,
                m.memDesc,
                m.custdataType';
        $prep = $dbc->prepare($query);

        $date1 = FormLib::getDate('date1', date('Ymd'), 'Ymd');
        $date2 = FormLib::getDate('date2', date('Ymd'), 'Ymd');

        $result = $dbc->execute($prep, array($date1, $date2));
        $report = array();
        while ($w = $dbc->fetch_row($result)) {
            $report[] = array(
                $w['memDesc'],
                ($w['custdataType'] == 'PC' ? 'Yes' : 'No'),
                sprintf('%d', $w['numTransactions']),
                sprintf('%.2f', $w['avgTenderTotal']),
                sprintf('%.2f', $w['avgItemQty']),
                sprintf('%.2f', $w['avgItemTotal']),
                sprintf('%.2f', $w['avgRetailQty']),
                sprintf('%.2f', $w['avgRetailTotal']),
            );
        }

        return $report;
    }

    function calculate_footers($data)
    {
        $numTrans = array(0, 0, 0);
        $avgTender = array(0, 0, 0);
        $avgItemQty = array(0, 0, 0);
        $avgItemTtl = array(0, 0, 0);
        $avgRealQty = array(0, 0, 0);
        $avgRealTtl = array(0, 0, 0);
        foreach ($data as $row) {
            $numTrans[0] += $row[2];
            $avgTender[0] += $row[3] * $row[2];
            $avgItemQty[0] += $row[4] * $row[2];
            $avgItemTtl[0] += $row[5] * $row[2];
            $avgRealQty[0] += $row[6] * $row[2];
            $avgRealTtl[0] += $row[7] * $row[2];
            if ($row[1] == 'Yes') {
                $numTrans[1] += $row[2];
                $avgTender[1] += $row[3] * $row[2];
                $avgItemQty[1] += $row[4] * $row[2];
                $avgItemTtl[1] += $row[5] * $row[2];
                $avgRealQty[1] += $row[6] * $row[2];
                $avgRealTtl[1] += $row[7] * $row[2];
            } else {
                $numTrans[2] += $row[2];
                $avgTender[2] += $row[3] * $row[2];
                $avgItemQty[2] += $row[4] * $row[2];
                $avgItemTtl[2] += $row[5] * $row[2];
                $avgRealQty[2] += $row[6] * $row[2];
                $avgRealTtl[2] += $row[7] * $row[2];
            }
        }
        
        $labels = array('Total', 'Subtotal, Owner', 'Subtotal, Non-Owner');
        $ret = array();
        foreach (array(1, 2, 0) as $i) {
            $total_line = array(
                $labels[$i],
                '',
                sprintf('%d', $numTrans[$i]),
                sprintf('%.2f', $avgTender[$i] / $numTrans[$i]),
                sprintf('%.2f', $avgItemQty[$i] / $numTrans[$i]),
                sprintf('%.2f', $avgItemTtl[$i] / $numTrans[$i]),
                sprintf('%.2f', $avgRealQty[$i] / $numTrans[$i]),
                sprintf('%.2f', $avgRealTtl[$i] / $numTrans[$i]),
            );
            $ret[] = $total_line;
        }

        return $ret;
    }

    public function form_content()
    {
        return '
            <form action="' . $_SERVER['PHP_SELF'] . '" method="get">
            <div class="col-sm-5">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="text" name="date1" class="date-field form-control" 
                        id="date1" required />
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="text" name="date2" class="date-field form-control" 
                        id="date2" required />
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-default">Submit</button>
                </div>
            </div>
            <div class="col-sm-5">
                ' . FormLib::dateRangePicker() . '
            </div>
            </form>';
    }
}

FannieDispatch::conditionalExec();

