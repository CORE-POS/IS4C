<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op.

    This file is part of IT CORE.

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

namespace COREPOS\pos\lib\ReceiptBuilding\Savings;
use COREPOS\pos\lib\Database;

/**
  @class DefaultReceiptSavings
  Print "savings" messages for receipt
*/
class SeparateReceiptSavings extends DefaultReceiptSavings 
{
    public function savingsMessage($trans_num)
    {
        $valid = preg_match('/^(\d+)\D+(\d+)\D+(\d+)$/', $trans_num, $matches);
        if (!$valid) {
            return '';
        }
        $emp = $matches[1];
        $reg = $matches[2];
        $trans = $matches[3];

        $dbc = Database::tDataConnect();
        $query = "
            SELECT
                SUM(CASE WHEN discounttype IN (1) THEN discount ELSE 0 END) AS sales,
                SUM(CASE WHEN trans_status='M' THEN -total ELSE 0 END) AS memSales,
                SUM(CASE WHEN discounttype IN (2) THEN discount ELSE 0 END) AS availableMemSales,
                SUM(CASE WHEN upc='DISCOUNT' THEN -total ELSE 0 END) AS transDiscount,
                SUM(CASE WHEN trans_subtype IN ('CP','IC') THEN -total ELSE 0 END) as coupons,
                MAX(percentDiscount) AS percentDiscount
            FROM localtranstoday
            WHERE emp_no=" . ((int)$emp)
                . " AND register_no=" . ((int)$reg)
                . " AND trans_no=" . ((int)$trans);
        $result = $dbc->query($query);
        if (!$result || $dbc->numRows($result) == 0) {
            return '';
        }
        $row = $dbc->fetchRow($result);
        
        $msg = '';
        if ($row['transDiscount'] > 0) {
            $msg .= $row['percentDiscount'] . _('% DISCOUNT SAVINGS = $') . number_format($row['transDiscount'], 2) . "\n";
        }
        if ($row['sales'] + $row['memSales'] > 0) {
            $msg .= _('SALE SAVINGS = $') . number_format($row['sales'] + $row['memSales'], 2) . "\n";
        }
        if ($row['coupons'] > 0) {
            $msg .= _('COUPONS = $') . number_format($row['coupons'], 2) . "\n";
        }

        return $msg;
    }
}
