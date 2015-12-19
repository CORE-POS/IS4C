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

/**
  @class WfcTenderReport
  Generate a tender report
*/
class WfcTenderReport extends TenderReport 
{

    static private $DESIRED_TENDERS = array("CK"=>"CHECK TENDERS",
                 "CC"=>"CREDIT CARD TENDERS",
                 "GD"=>"GIFT CARD TENDERS",
                 "TC"=>"GIFT CERT TENDERS",
                 "MI"=>"STORE CHARGE TENDERS",
                 "EF"=>"EBT CARD TENDERS",
                 "CP"=>"COUPONS TENDERED",
                 "IC"=>"INSTORE COUPONS TENDERED",
                 "ST"=>"STAMP BOOKS SOLD",
                 "BP"=>"BUS PASSES SOLD",
                 "AR"=>"AR PAYMENTS",
                 "EQ"=>"EQUITY SALES",
                 "SN"=>"SANDWICH CARD",
                 "CF"=>"COFFEE CARD",
                 "SC"=>"STORE CREDIT"
             );

/** 
 Print a tender report
 
 This tender report is based on a single tender tape view
 rather than multiple views (e.g. ckTenders, ckTenderTotal, etc)
 adding a new tender is mostly just a matter of adding it
 to the $DESIRED_TENDERS array (exception being if you want
 special handling in the tender tape view (e.g., three
 tender types are actually compined under EBT)
 */
static public function get()
{
    $db_a = Database::mDataConnect();

    $blank = self::standardBlank();
    $fieldNames = self::standardFieldNames();
    $ref = ReceiptLib::centerString(trim(CoreLocal::get("CashierNo"))." ".trim(CoreLocal::get("cashier"))." ".ReceiptLib::build_time(time()))."\n\n";
    $receipt = "";

    foreach (self::$DESIRED_TENDERS as $tender_code => $header) { 
        $query = "select tdate,register_no,trans_no,-total AS tender
                   from dlog where emp_no=".CoreLocal::get("CashierNo").
            " and trans_type='T' AND trans_subtype='".$tender_code."'
              ORDER BY tdate";
        switch($tender_code){
        case 'CC':
            $query = "select tdate,register_no,trans_no,-total AS tender
                from dlog where emp_no=".CoreLocal::get("CashierNo").
                " and trans_type='T' AND trans_subtype IN ('CC','AX')
                  ORDER BY tdate";
            break;
        case 'EF':
            $query = "select tdate,register_no,trans_no,-total AS tender
                from dlog where emp_no=".CoreLocal::get("CashierNo").
                " and trans_type='T' AND trans_subtype IN ('EF','EC')
                  ORDER BY tdate";
            break;
        case 'CP':
            $query = "select tdate,register_no,trans_no,-total AS tender
                from dlog where emp_no=".CoreLocal::get("CashierNo").
                " and trans_type='T' AND trans_subtype ='CP' AND
                  upc NOT LIKE '%MAD%' ORDER BY tdate";
            break;
        case 'SC':
            $query = "select tdate,register_no,trans_no,-total AS tender
                from dlog where emp_no=".CoreLocal::get("CashierNo").
                " and trans_type='T' AND trans_subtype ='SC' AND
                  total<0 ORDER BY tdate";
            break;
        case 'AR':
            $query = "select tdate,register_no,trans_no,total AS tender
                from dlog where emp_no=".CoreLocal::get("CashierNo").
                " and trans_type='D' AND department = 990
                  ORDER BY tdate";
            break;
        case 'EQ':
            $query = "select tdate,register_no,trans_no,total AS tender
                from dlog where emp_no=".CoreLocal::get("CashierNo").
                " and trans_type='D' AND department IN (991,992)
                  ORDER BY tdate";
            break;
        case 'ST':
            $query = "select tdate,register_no,trans_no,total AS tender
                from dlog where emp_no=".CoreLocal::get("CashierNo").
                " and trans_type='I' AND upc = '0000000001065'
                  ORDER BY tdate";
            break;
        case 'BP':
            $query = "select tdate,register_no,trans_no,total AS tender
                from dlog where emp_no=".CoreLocal::get("CashierNo").
                " and trans_type='I' AND upc IN('0000000007573','0000000007574')
                  ORDER BY tdate";
            break;
        case 'SN':
            $query = "select tdate,register_no,trans_no,total AS tender
                from dlog where emp_no=".CoreLocal::get("CashierNo").
                " and trans_type='I' AND upc LIKE '002001000%'
                  ORDER BY tdate";
            break;
        case 'CF':
            $query = "select tdate,register_no,trans_no,total AS tender
                from dlog where emp_no=".CoreLocal::get("CashierNo").
                " and trans_type='I' AND upc LIKE '002000600%'
                  ORDER BY tdate";
            break;
        }
        $result = $db_a->query($query);
        $num_rows = $db_a->num_rows($result);
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

        $receipt .= chr(27).chr(105);
    }

    return $receipt.chr(27).chr(105);
}

}

