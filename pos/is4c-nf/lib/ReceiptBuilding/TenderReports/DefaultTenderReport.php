<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

namespace COREPOS\pos\lib\ReceiptBuilding\TenderReports;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;
use \CoreLocal;

/**
  @class DefaultTenderReport
  Generate a tender report
*/
class DefaultTenderReport extends TenderReport {

static protected $print_handler;

static public function setPrintHandler($ph)
{
    self::$print_handler = $ph;
}

/** 
 Print a tender report

 This tender report is based on a single tender tape view
 rather than multiple views (e.g. ckTenders, ckTenderTotal, etc).
 Which tenders to include is defined via checkboxes by the
 tenders on the install page's "extras" tab.
 */
static public function get()
{
    $DESIRED_TENDERS = CoreLocal::get("TRDesiredTenders");
    if (!is_array($DESIRED_TENDERS)) {
        $DESIRED_TENDERS = array();
    }

    $db_a = Database::mDataConnect();

    $blank = self::standardBlank();
    $fieldNames = self::standardFieldNames();
    $ref = ReceiptLib::centerString(trim(CoreLocal::get("CashierNo"))." ".trim(CoreLocal::get("cashier"))." ".ReceiptLib::build_time(time()))."\n\n";
    $receipt = "";

    foreach ($DESIRED_TENDERS as $tender_code => $titleStr) { 
        $query = "SELECT datetime AS tdate,
                    register_no,
                    trans_no,
                    CASE
                        WHEN trans_subtype='CA' AND total >= 0 THEN total
                        WHEN trans_subtype='CA' AND total < 0 THEN 0
                        ELSE -1*total
                    END AS tender
                  FROM dtransactions 
                  WHERE emp_no=".CoreLocal::get("CashierNo")."
                    AND trans_subtype = '".$tender_code."' 
                    AND trans_status NOT IN ('X','Z')
                  ORDER BY datetime";
        $result = $db_a->query($query);
        $num_rows = $db_a->numRows($result);
        if ($num_rows <= 0) continue;

        $titleStr = array_reduce(str_split($titleStr), function($carry,$i){ return $carry . $i . ' '; });
        $receipt .= ReceiptLib::centerString(trim($titleStr))."\n";

        $receipt .= $ref;
        $receipt .=    ReceiptLib::centerString("------------------------------------------------------");
        $receipt .= $fieldNames;

        $sum = 0;
        while ($row = $db_a->fetchRow($result)) {
            $receipt .= self::standardLine($row['tdate'], $row['register_no'], $row['trans_no'], $row['tender']);
            $sum += $row["tender"];
        }
        
        $receipt.= ReceiptLib::centerString("------------------------------------------------------");
        $receipt .= substr($blank.$blank.$blank."Count: ".$num_rows."  Total: ".number_format($sum,2), -56)."\n";
        $receipt .= str_repeat("\n", 4);
    }

    return $receipt.chr(27).chr(105);
}

}

