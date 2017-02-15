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

/**
  @class HarvestNewTenderReport
  Generate a tender report using the NEW methods (no TTG)
*/
class HarvestNewTenderReport extends TenderReport 
{

/** 
 Print a tender report
 
 This tender report is based on a single tender tape view
 rather than multiple views (e.g. ckTenders, ckTenderTotal, etc)
 adding a new tender is mostly just a matter of adding it
 to the $DESIRED_TENDERS array (exception being if you want
 special handling in the tender tape view (e.g., three
 tender types are actually compined under EBT)
 */
static public function get($session)
{
    $DESIRED_TENDERS = is_array($session->get("TRDesiredTenders")) ? $session->get('TRDesiredTenders') : array();

    $DESIRED_TENDERS = array_merge($DESIRED_TENDERS, array(
        "CP"=>"COUPONS TENDERED", 
        "FS"=>"EBT CARD TENDERS", 
        "CK"=>"CHECK TENDERS", 
        "AR"=>"ACCOUNTANT ONLY", 
        "EQ"=>"EQUITY"
    ));

    $dba = Database::mDataConnect();

    $blank = self::standardBlank();
    $fieldNames = self::standardFieldNames();
    $ref = ReceiptLib::centerString(trim($session->get("CashierNo"))." ".trim($session->get("cashier"))." ".ReceiptLib::build_time(time()))."\n\n";
    $receipt = "";

    $itemize = 0;
    foreach ($DESIRED_TENDERS as $tenderCode => $header) { 
        $query = "select tdate,register_no,trans_no,-total AS tender
                   from dlog where emp_no=".$session->get("CashierNo").
            " and trans_type='T' AND trans_subtype='".$tenderCode."'
             AND total <> 0 ORDER BY tdate";
        switch($tenderCode){
        case 'FS':
            $query = "select tdate,register_no,trans_no,-total AS tender
                from dlog where emp_no=".$session->get("CashierNo").
                " and trans_type='T' AND trans_subtype IN ('EF','EC','EB','EK')
                  AND total <> 0 ORDER BY tdate";
            break;
        case 'CK':
            $query = "select tdate,register_no,trans_no,-total AS tender
                from dlog where emp_no=".$session->get("CashierNo").
                " and trans_type='T' AND trans_subtype IN ('PE','BU','EL','PY','TV')
                  AND total <> 0 ORDER BY tdate";
            break;
        case 'MC':
            $query = "select tdate,register_no,trans_no,-total AS tender
                from dlog where emp_no=".$session->get("CashierNo").
                " and trans_type='T' AND trans_subtype  IN ('CP','MC') AND
                  upc NOT LIKE '%MAD%' AND total <> 0 ORDER BY tdate";
            break;
        case 'AR':
            $query = "select tdate,register_no,trans_no,total AS tender
                from dlog where emp_no=".$session->get("CashierNo").
                " and trans_type='D' AND total <> 0 AND department = 98
                  ORDER BY tdate";
            break;
        case 'EQ':
            $query = "select tdate,register_no,trans_no,total AS tender
                from dlog where emp_no=".$session->get("CashierNo").
                " and trans_type='D' AND department IN (70,71)
                  AND total <> 0 ORDER BY tdate";
            break;
        }
        $result = $dba->query($query);
        $numRows = $dba->numRows($result);
        if ($numRows <= 0) continue;

        //$receipt .= chr(27).chr(33).chr(5);

        $titleStr = "";
        for ($i = 0; $i < strlen($header); $i++)
            $titleStr .= $header[$i]." ";
        $titleStr = substr($titleStr,0,strlen($titleStr)-1);
        $receipt .= ReceiptLib::centerString($titleStr)."\n";

        $receipt .= $ref;
        if ($itemize == 1) $receipt .=    ReceiptLib::centerString("------------------------------------------------------");
        if ($itemize == 1) $receipt .= $fieldNames;
        $sum = 0;

        while ($row = $dba->fetchRow($result)) {
            if ($itemize == 1) {
                $receipt .= self::standardLine($row['tdate'], $row['register_no'], $row['trans_no'], $row['tender']);
            }
            $sum += $row["tender"];
        }
        
        $receipt.= ReceiptLib::centerString("------------------------------------------------------");

        $receipt .= substr($blank.$blank.$blank."Count: ".$numRows."  Total: ".number_format($sum,2), -56)."\n";
        $receipt .= str_repeat("\n", 4);
    }

    return $receipt.chr(27).chr(105);
}

}

