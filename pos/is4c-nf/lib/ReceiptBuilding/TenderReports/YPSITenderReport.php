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
  @class YPSITenderReport
  Ypsilanti Food Co-op custom tender report format
*/

class YPSITenderReport extends TenderReport {

static public function get($session)
{
    $shiftCutoff = date('Y-m-d 00:00:00');
    $excl = " AND emp_no <> 9999 ";

    $DESIRED_TENDERS = is_array($session->get("TRDesiredTenders")) ? $session->get('TRDesiredTenders') : array();

    $dba = Database::mDataConnect();

    $blank = self::standardBlank();
    $fieldNames = self::standardFieldNames();
    $ref = ReceiptLib::centerString(trim($session->get("CashierNo"))." ".trim($session->get("cashier"))." ".ReceiptLib::build_time(time()))."\n\n";
    $receipt = "";

    $cashier_names = "";
    $cashierQ = "SELECT CONCAT(SUBSTR(e.FirstName,1,1),e.Lastname) as cashier
        FROM dlog d, is4c_op.employees e
        WHERE d.emp_no = e.emp_no AND d.register_no = ". $session->get('laneno')." AND d.emp_no <> 9999 AND d.trans_type <> 'L' 
        AND d.tdate >= '".$shiftCutoff."'
        GROUP BY d.emp_no ORDER BY d.tdate";
        
    $cashierR = $dba->query($cashierQ);

    for ($i = 0; $i < $row = $dba->fetchRow($cashierR); $i++) {
            $cashier_names .= $row['cashier'].", ";
    }

    $receipt .= ReceiptLib::centerString("T E N D E R   R E P O R T")."\n";
    $receipt .= $ref;
    $receipt .= ReceiptLib::centerString("Cashiers: " . $cashier_names)."\n\n";

    // NET TOTAL
    $netQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
        WHERE register_no=".$session->get('laneno').
        " AND (trans_subtype IN('CA','CK','DC','CC','EF','FS','EC','GD','TC','WT') OR (trans_subtype = 'MI' AND staff <> 1))
        AND tdate >= '$shiftCutoff'$excl";
    $netR = $dba->query($netQ);
    $net = $dba->fetchRow($netR);
    $receipt .= "  ".substr("GROSS Total: ".$blank.$blank,0,20);
    $receipt .= substr($blank.number_format(($net[0]),2),-8)."\n";

    $receipt .= "\n";
    $receipt .=    trTotal($session,'CA','CASH');
    $receipt .=    trTotal($session,'CK','CHECK');
    $receipt .=    trTotal($session,'CC','CREDIT CARD');
    $receipt .=    trTotal($session,'DC','DEBIT CARD');
    $receipt .=    trTotal($session,'EF','EBT - FOOD');
    $receipt .=    trTotal($session,'EC','EBT - CASH');
    $receipt .=    trTotal($session,'GD','GIFT CARD');
    $receipt .=    trTotal($session,'TC','GIFT CERT.');    
    $receipt .=    trTotal($session,'WT','WIC');
    $receipt .= "\n";
    $receipt .=    trTotal($session,array('CP','MC'),'VENDOR COUPON');
    $receipt .=    trTotal($session,'MI','STORE CHARGE');
    $receipt .=    trTotal($session,'IC','INSTORE COUPON');
    $receipt .= "\n\n";

    foreach(array_keys($DESIRED_TENDERS) as $tender_code){ 
        $query = "select datetime from dtransactions where emp_no=".$session->get("CashierNo").
            " and tender_code = '".$tender_code."' AND trans_status NOT IN ('X', 'Z') order by tdate";
        $result = $dba->query($query);
        $numRows = $dba->numRows($result);
        if ($numRows <= 0) continue;

        //$receipt .= chr(27).chr(33).chr(5);

        $titleStr = "";
        $itemize = 1;
        for ($i = 0; $i < strlen($DESIRED_TENDERS[$tender_code]); $i++)
            $titleStr .= $DESIRED_TENDERS[$tender_code][$i]." ";
        $titleStr = substr($titleStr,0,strlen($titleStr)-1);
        $receipt .= ReceiptLib::centerString($titleStr)."\n";

        $receipt .= $ref;
        if ($itemize == 1) $receipt .=    ReceiptLib::centerString("------------------------------------------------------");

        $query = "SELECT datetime AS tdate,
                    register_no,
                    trans_no,
                    CASE
                        WHEN trans_subtype='CA' AND total >= 0 THEN total
                        WHEN trans_subtype='CA' AND total < 0 THEN 0
                        ELSE -1*total
                    END AS tender
                  FROM dtransactions 
                  WHERE emp_no=".$session->get("CashierNo")."
                    AND tender_code = '".$tender_code."' 
                    AND trans_status NOT IN ('X','Z')
                  ORDER BY datetime";
        $result = $dba->query($query);
        $numRows = $dba->numRows($result);
        
        if ($itemize == 1) $receipt .= $fieldNames;
        $sum = 0;

        for ($i = 0; $i < $numRows; $i++) {
            $row = $dba->fetchRow($result);
            $timeStamp = self::timeStamp($row["tdate"]);
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

function trTotal($session,$k, $label,$i=False) 
{
    $dba = Database::mDataConnect();

    $blank = "             ";
    $fieldNames = "  ".substr("Time".$blank, 0, 10)
            .substr("Lane".$blank, 0, 8)
            .substr("Trans #".$blank, 0, 8)
            .substr("Emp #".$blank, 0, 10)
            .substr("Mem #".$blank, 0, 10)
            .substr("Amount".$blank, 0, 12)."\n";
    $shiftCutoff = date('Y-m-d 00:00:00');
    $lookup = $dba->query("SELECT MAX(datetime) FROM dtransactions 
        WHERE DATE(datetime) = CURDATE() AND upc='ENDOFSHIFT' AND 
        register_no=".$session->get('laneno'));
    if ($dba->numRows($lookup) > 0){
        $row = $dba->fetchRow($lookup);
        if ($row[0] != '') $shiftCutoff = $row[0];
    }

    if (is_array($k)) $k = "'" . implode("','", $k) . "'";
    if (!is_numeric($k)) { 
        if ($k[0] == '#') {
            $k = substr($k,1);
            $q = 'card_no';
        } else {
            $k = "'".$k."'";
            $q = 'trans_subtype';
        }
    } else {
        $q = 'department';
    }
    // $q = (!is_numeric($k)) ? 'trans_subtype' : 'department';
    
    if($i===False) {
        $tenderQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
            WHERE register_no=".$session->get('laneno').
            " AND $q IN($k) AND tdate >= '$shiftCutoff' AND emp_no <> 9999";
    } else {
        $tenderQ = "SELECT tdate,register_no,emp_no,trans_no,card_no,total FROM dlog 
            WHERE register_no=".$session->get('laneno').
            " and $q IN($k) AND tdate >= '$shiftCutoff' AND emp_no <> 9999 order by tdate";
    }
    $tenderR = $dba->query($tenderQ);
    $tender = $dba->fetchRow($tenderR);
    $numRows = $dba->numRows($tenderR);

    if($i===False) {
        $ret = "  ".substr($label.$blank.$blank,0,20).substr($blank.number_format(($tender[0]),2),-8).substr($blank.$tender[1],-8)."\n";
    } else {
        $sum = 0;
        $ret = ReceiptLib::centerString($label)."\n";
        $ret .=    ReceiptLib::centerString("------------------------------------------------------");
        $ret .= $fieldNames;
        // for ($i = 0; $i < $numRows; $i++) {
        while ($row = $dba->fetchRow($tenderR)) {
            // $row = $dba->fetchRow($tenderR);
            $timeStamp = TenderReport::timeStamp($row["tdate"]);
            $ret .= "  ".substr($timeStamp.$blank, 0, 10)
                .substr($row["register_no"].$blank, 0, 9)
                .substr($row["trans_no"].$blank, 0, 8)
                .substr($blank.$row['emp_no'], -5)
                .substr($blank.$row["card_no"],-6)
                .substr($blank.number_format($row["total"], 2), -12)."\n";
            $sum += $row["total"];
        }
        $ret .= ReceiptLib::centerString("------------------------------------------------------");
        $ret .= substr($blank.$blank.$blank."Count: ".$numRows."  Total: ".number_format($sum,2), -56)."\n";

        $ret .= str_repeat("\n", 3);
    }    
    
    return $ret;
}

