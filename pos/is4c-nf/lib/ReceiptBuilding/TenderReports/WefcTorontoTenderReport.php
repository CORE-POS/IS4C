<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op
    Copyright 2014 West End Food Coop, Toronto

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
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\ReceiptLib;

/**
  @class WefcTorontoTenderReport
  Like PFC tender report except reports for the entire
   current day instead of just the current shift.
  Reports from localtranstoday if offline.
*/
class WefcTorontoTenderReport extends TenderReport {

static private $dashLine = '';
static private $tTable = '';
static private $tDate = '';
static private $excl = '';
static private $shiftCutoff = '';
static private $db_a = NULL;

/**
 Prepare the tender report.
 @return string The report, ready to send to printer.
*/
static public function get($session){
    global $CORE_LOCAL;

    /* First, check for anything still in
     * . localtemptrans
     * . dtransactions
     * and for a proper End of Shift.
     */
    $localCheck = "";
    $db_a = Database::tDataConnect();

    $localQ = "SELECT *
        FROM localtemptrans
        ORDER BY datetime";
    $localR = $db_a->query($localQ);
    $rowCount1 = 0;
    $transCount1 = 0;
    $endOfShiftCount1 = 0;
    $lastUPC1 = "";
    while ($localRow = $db_a->fetch_row($localR)){
        $rowCount1++;
        $lastUPC1 = $localRow['upc'];
        if ($lastUPC1 == 'ENDOFSHIFT') {
            $endOfShiftCount1++;
        }
        if ($localRow['trans_type'] == 'A') {
            $transCount1++;
        }
    }

    $localQ = "SELECT *
        FROM dtransactions
        ORDER BY datetime";
    $localR = $db_a->query($localQ);
    $rowCount2 = 0;
    $transCount2 = 0;
    $endOfShiftCount2 = 0;
    $lastUPC2 = "";
    while ($localRow = $db_a->fetch_row($localR)){
        $rowCount2++;
        $lastUPC2 = $localRow['upc'];
        if ($lastUPC2 == 'ENDOFSHIFT') {
            $endOfShiftCount2++;
        }
        if ($localRow['trans_type'] == 'A') {
            $transCount2++;
        }
    }

    /* Ideally, either
         * . both tables are empty
         * . there is one record, an upc=ENDOFSHIFT,
     *    in either localtemptrans or dtransactions
     *    and the other table is empty.
     */
    if ( $rowCount1 == 0 && $rowCount2 == 0) {
        $localCheck .= "\n\nThe shift was ended properly, buffers empty. " .
            "Good!";

        }
    elseif (
        ($lastUPC1 == 'ENDOFSHIFT' && $rowCount1 == 1 && $rowCount2 == 0) ||
        ($lastUPC2 == 'ENDOFSHIFT' && $rowCount2 == 1 && $rowCount1 == 0) 
    ) {
        $localCheck .= "\n\nThe shift was ended properly, EndOfShift only. " .
            "Good!";
    }
    elseif ( $lastUPC1 == 'ENDOFSHIFT' && 
                ($rowCount1 > 0 || $endOfShiftCount1 > 0) ) {
        $localCheck .= "\n\nThe local transaction-in-progress buffer suggests " .
            "the shift was ended but perhaps not properly. " .
            "\n Please SignOff once again and then re-run this report." .
            "\n If the same problem or another one is reported just use the " .
            "\n  last or best report you have.";
                }
    elseif ( $rowCount1 > 0) {
            $localCheck .= "\nThe local transaction-in-progress still contains " .
                "$rowCount1 items." .
                "\nPlease complete or cancel the current transaction " .
                "and run this " .
                "\n report again." .
                "\n If the same problem or another one is reported just use the " .
                "\n  last or best report you have.";
    }
    elseif ( $lastUPC2 == 'ENDOFSHIFT' && 
                ($rowCount2 > 0 || $endOfShiftCount2 > 0) ) {
        $localCheck .= "\n\nThe local completed-transaction buffer suggests " .
            "the shift " .
            "\n was ended but perhaps not properly. " .
            "\n Please SignOff once again and then re-run this report." .
            "\n If the same problem or another one is reported just use the " .
            "\n  last or best report you have.";
                }
    else {
        $localCheck .= "\n\nThe local completed-transaction buffer suggests " .
            "the shift was not " .
            "\n ended properly. " .
            "\nPlease SignOff, again if you already have, and then re-run " .
            "this report." .
            "\n If the same problem or another one is reported just use the " .
            "\n  last or best report you have.";
    }

    if ($transCount2 > 0) {
        $localCheck .= "\n\nThe local completed-transaction buffer contains " .
            "$transCount2 transactions " .
            "\n that probably will not appear in this report. " .
            "\n Please alert the shift co-ordinator.";
    }

    if (MiscLib::pingport($CORE_LOCAL->get("mServer"), $CORE_LOCAL->get("mDBMS"))) {
        $db_a = Database::mDataConnect();
        $tTable = "dlog";
        $tDate = "tdate";
        $tSource = "Fannie";
        $excl = "";
    } else {
        $db_a = Database::tDataConnect();
        $tTable = "localtranstoday";
        $tDate = "datetime";
        $tSource = "Lane" . $CORE_LOCAL->get('laneno');
        $excl = " AND d.trans_status not in ('D','X','Z') AND d.emp_no <> 9999 " .
            "AND d.register_no <> 99";
    }

        self::$db_a = $db_a;
    self::$tTable = $tTable;
    self::$tDate = $tDate;
    self::$excl = $excl;
        $shiftCutoff = date('Y-m-d 00:00:00');
        self::$shiftCutoff = $shiftCutoff;

        $DESIRED_TENDERS = is_array($session->get("TRDesiredTenders")) ? $session->get('TRDesiredTenders') : array();

    self::$dashLine = str_repeat('-',54);
        $receipt = "";
        $blank = str_repeat(' ', 13);
/* Literal spacing from left margin.
                          C a s h
  ------------------------------------------------------
  Time      Lane    Trans #   Emp #   Mem #      Amount        
  03:27 PM  1        1           62   484         -6.00
  04:14 PM  1        1           63   484         -7.00
  ------------------------------------------------------
                                Count: 2  Total: -13.00
*/

    $fieldNames = "  ".substr("Time".$blank, 0, 10)
            .substr("Lane".$blank, 0, 8)
            .substr("Trans #".$blank, 0, 10)
            .substr("Emp #".$blank, 0, 8)
            .substr("Mem #".$blank, 0, 11)
            .substr("Amount".$blank, 0, 14)."\n";
    $ref = ReceiptLib::centerString(trim($CORE_LOCAL->get("CashierNo"))." ".
        trim($CORE_LOCAL->get("cashier"))." ".ReceiptLib::build_time(time())).
        "\n";

    $cashier_names = "";
    $cashierQ = "SELECT CONCAT(SUBSTR(e.FirstName,1,1),SUBSTR(e.Lastname,1,1)) as cashier
        FROM $tTable d, ".$CORE_LOCAL->get('pDatabase').".employees e
        WHERE d.emp_no = e.emp_no AND d.register_no = ". $CORE_LOCAL->get('laneno').
            " AND d.trans_type <> 'L' 
            AND d.{$tDate} >= '".$shiftCutoff."'{$excl}
        GROUP BY d.emp_no
        ORDER BY d.{$tDate}";
        
    $cashierR = $db_a->query($cashierQ);

    for ($i = 0; $i < $row = $db_a->fetchRow($cashierR); $i++) {
            $cashier_names .= $row['cashier'].", ";
    }

    $receipt .= ReceiptLib::centerString("T E N D E R   R E P O R T")."\n";
    $receipt .= ReceiptLib::centerString("Data from $tSource")."\n";
    $receipt .= $ref;
    $receipt .= ReceiptLib::centerString("Lane " . $CORE_LOCAL->get('laneno').
        " -- Cashiers: " . $cashier_names)."\n\n";

    if ($localCheck) {
        $receipt .= "$localCheck\n\n\n";
    }

    /* NET TOTAL
     * Does not include tenders such as StoreCharge and Coupons.
     */
    if ($CORE_LOCAL->get('store') == 'WEFC_Toronto') {
        $netTenderList = "'CA','CK','DC','CC'";
        $netTenderMessage = "(Only: Cash, Cheque, Debit, Credit)";
    } else {
        $netTenderList = "'CA','CK','DC','CC','FS','EC'";
        $netTenderMessage = "(Only: Cash, Check, Debit, Credit, SNAPs)";
    }
    $netQ = "SELECT -SUM(total) AS net, COUNT(total)
      FROM $tTable d
        WHERE register_no=".$CORE_LOCAL->get('laneno').
        " AND trans_subtype IN({$netTenderList})" .
        " AND {$tDate} >= '{$shiftCutoff}'{$excl}";
    $netR = $db_a->query($netQ);
    $net = $db_a->fetch_row($netR);
    $receipt .= "  ".substr("NET Total: ".$blank.$blank,0,20);
    $receipt .= substr($blank.number_format(($net[0]),2),-8).
        "\n  {$netTenderMessage}\n";
    $receipt .= "\n";

    /* Total for each of a Tender Type or combination.
     * Each is listed even if no items.
     * The PFC version was driven by Desired Tenders.
     */
    $receipt .= self::trTotal('CA','CASH');
    if ($CORE_LOCAL->get('store') == 'WEFC_Toronto') {
            $receipt .= self::trTotal('CK','CHEQUE');
    } else {
            $receipt .= self::trTotal('CK','CHECK');
        }
    $receipt .= self::trTotal(array('CP','MC'),'VENDOR COUPON');
    $receipt .= self::trTotal('CC','CREDIT CARD');
    $receipt .= self::trTotal('DC','DEBIT CARD');
    if (!$CORE_LOCAL->get('store') == 'WEFC_Toronto') {
        $receipt .= self::trTotal('FS','SNAP - FOOD');
        $receipt .= self::trTotal('EC','SNAP - CASH');
    }
    $receipt .= self::trTotal('GC','GIFT CARD');
    $receipt .= self::trTotal('MI','INSTORE CHARGE');
    $receipt .= self::trTotal('IC','INSTORE COUPON');
    $receipt .= "\n";
    if ($CORE_LOCAL->get('store') == 'WEFC_Toronto') {
        $receipt .= self::trTotal(array('CA','CK'),'CASH + CHEQUE');
        $receipt .= self::trTotal(array('DC','CC'),'DEBIT + CREDIT');
    } else {
        $receipt .= self::trTotal('PT','PATRONAGE');
        $receipt .= self::trTotal(array('CA','CK'),'CASH + CHECK');
        $receipt .= self::trTotal(array('DC','CC','FS','EC'),'DEB/CRED/SNAP');
    }
    if (!$CORE_LOCAL->get('store') == 'WEFC_Toronto') {
        $receipt .= self::trTotal(45,'RCVD. on ACCT.');
        $receipt .= self::trTotal(37,'FRMRS MARKET SNAP');
    }
 
    $receipt .= ReceiptLib::centerString(self::$dashLine);

    $receipt .= str_repeat("\n", 5);

    /* Detail for each Desired Tender Type or combination.
     * Types with no items are skipped.
     * The output seems very similar to trTotal().
     */
    /* If you share a credit/debit-card terminal between cash registers
     *  you might want to list those tenders for all lanes
     *  to help with day-end reconciliation.
     * Add an item to this array for each tender you want treated that way.
     */
    $allLaneTenders = array();
    $allLaneTenders[] = 'CC';
    $allLaneTenders[] = 'DC';
    foreach(array_keys($DESIRED_TENDERS) as $tender_code){ 
        /* Skip Tender Type if no items of that type.
         * The first search seems the same as the lower search except for total tests.
         */
        if (in_array($tender_code,$allLaneTenders)) {
            $registerArg = '';
        } else {
            $registerArg = " AND register_no=".$CORE_LOCAL->get('laneno');
        }
        $query = "SELECT {$tDate}".
            " FROM {$tTable} d".
            " WHERE trans_subtype = '".$tender_code."'".
                $registerArg .
                " AND {$tDate} >= '{$shiftCutoff}'{$excl}".
            " ORDER BY {$tDate}";
        $result = $db_a->query($query);
        $numRows = $db_a->num_rows($result);
        if ($numRows <= 0) {
            continue;
        }

        $titleStr = "";
        $itemize = 1;
        for ($i = 0; $i < strlen($DESIRED_TENDERS[$tender_code]); $i++)
            $titleStr .= $DESIRED_TENDERS[$tender_code][$i]." ";
        $titleStr = substr($titleStr,0,strlen($titleStr)-1);
        $receipt .= ReceiptLib::centerString($titleStr)."\n";

        if ($registerArg == '') {
            $receipt .= _("The list includes items for all lanes.");
        }
        if ($itemize == 1) {
            $receipt .= ReceiptLib::centerString(self::$dashLine)."\n";
        }

        $query = "SELECT {$tDate},register_no,emp_no,trans_no,card_no,total".
            " FROM {$tTable} d".
            " WHERE trans_subtype = '".$tender_code."'".
                $registerArg .
                " AND (total <> 0 OR total <> -0) ".
                " AND {$tDate} >= '{$shiftCutoff}'{$excl}".
            " ORDER BY {$tDate}";
        $result = $db_a->query($query);
        $numRows = $db_a->num_rows($result);

        if ($itemize == 1) {
            $receipt .= $fieldNames;
        }
        $sum = 0;

        for ($i = 0; $i < $numRows; $i++) {
            $row = $db_a->fetchRow($result);
            $timeStamp = self::timeStamp($row["$tDate"]);
            if ($itemize == 1 && $row["total"]) {
                $receipt .= "  ".substr($timeStamp.$blank, 0, 10)
                .substr($row["register_no"].$blank, 0, 9)
                .substr($row["trans_no"].$blank, 0, 8)
                .substr($blank.$row['emp_no'], -6)
                .substr($blank.$row["card_no"],-6)
                .substr($blank.number_format($row["total"], 2), -14)."\n";
            }
            $sum += $row["total"];
        }
        
        $receipt .= ReceiptLib::centerString(self::$dashLine)."\n";

        $receipt .= substr($blank.$blank.$blank."Count: ".$numRows.
            "  Total: ".number_format($sum,2), -55)."\n";
        $receipt .= str_repeat("\n", 3);

    // each desired tender.
    }

    /* Each item of purchase of these kinds.
     * first-param depts are PioneerFoodCoop, not WEFC_Toronto
     */
    $receipt .= self::trTotal(36,'MEMBER EQUITY',True,False);
    $receipt .= self::trTotal(45,'RCVD / ACCOUNT',True,False);
    $receipt .= self::trTotal(41,'GIFT CARDS SOLD',True,False);
    
    $receipt .= str_repeat("\n", 5);

    // cut paper?
    return $receipt.chr(27).chr(105);

// get()
}


/**
 @param $codes tender code(s) or department(s)
 @param $label label(s) for codes
 @param $listEach Boolean
  - True List each transaction
  - False List the sum of the transactions
 @return string A section of the report for the tender or department.
 */
static private function trTotal($codes,$label,$listEach=False,$reportIfNone=True) {
    global $CORE_LOCAL;

    $db_a = self::$db_a;

    $blank = str_repeat(' ',13);
    // Names padded on right with blanks. %10s?
    $fieldNames = "  ".substr("Time".$blank, 0, 10)
            .substr("Lane".$blank, 0, 8)
            .substr("Trans #".$blank, 0, 10)
            .substr("Emp #".$blank, 0, 8)
            .substr("Mem #".$blank, 0, 11)
            .substr("Amount".$blank, 0, 14)."\n";

    if (is_array($codes)) $codes = "'" . implode("','", $codes) . "'";
    elseif (!is_numeric($codes)) $codes = "'".$codes."'";
    $codeField = (!is_numeric($codes)) ? 'trans_subtype' : 'department';
    
    if($listEach===False) {
        $tenderQ = "SELECT -SUM(total) AS net, COUNT(total)" .
            " FROM " . self::$tTable . " d" .
        " WHERE register_no=".$CORE_LOCAL->get('laneno').
        " AND $codeField IN($codes) AND " . self::$tDate . " >= '" .
            self::$shiftCutoff . "'" . self::$excl;
    } else {
        $tenderQ = "SELECT " . self::$tDate . ",register_no,emp_no,trans_no,card_no,total" .
            " FROM " . self::$tTable . " d" .
            " WHERE register_no=".$CORE_LOCAL->get('laneno').
            " AND $codeField IN($codes) AND " . self::$tDate . " >= '" .
                self::$shiftCutoff . "'" . self::$excl;
            " ORDER BY " . self::$tDate;
    }
    $tenderR = $db_a->query($tenderQ);
    $tender = $db_a->fetchRow($tenderR);
    $numRows = $db_a->num_rows($tenderR);
    $ret = '';
    // Reports even if 0. reportIfNone
    if (!$reportIfNone && $numRows == 0) {
        return '';
    }

    if($listEach===False) {
        $ret = "  ".substr($label.$blank.$blank,0,20).
            substr($blank.number_format(($tender[0]),2),-8).substr($blank.
            $tender[1],-8)."\n";
    } else {
        $sum = 0;
        $ret = ReceiptLib::centerString($label)."\n";
        $ret .= ReceiptLib::centerString(self::$dashLine)."1\n";
        $ret .= $fieldNames;
        for ($i = 0; $i < $numRows; $i++) {
            $row = $db_a->fetchRow($tenderR);
            $timeStamp = TenderReport::timeStamp($row["self::$tDate"]);
            $ret .= "  ".substr($timeStamp.$blank, 0, 10)
                .substr($row["register_no"].$blank, 0, 9)
                .substr($row["trans_no"].$blank, 0, 8)
                .substr($blank.$row['emp_no'], -8)
                .substr($blank.$row["card_no"],-6)
                .substr($blank.number_format($row["total"], 2), -14)."\n";
            $sum += $row["total"];
        }
        $ret .= ReceiptLib::centerString(self::$dashLine)."2\n";
        $ret .= substr($blank.$blank.$blank."Count: ".$numRows.
            "  Total: ".number_format($sum,2), -55)."\n";

        $ret .= str_repeat("\n", 3);
    }    
    
    return $ret;

// trTotal()
}

// WefcTorontoTenderReport class
}

