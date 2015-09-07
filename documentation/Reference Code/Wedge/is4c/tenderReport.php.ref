<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
if (!function_exists("printReceipt")) include("printReceipt.php");
if (!function_exists("mDataConnect")) include("connect.php");

function tenderReport() {
    $db_a = mDataConnect();
    
    $blank = "             ";
    
    $eosQ = "select max(tdate) from dlog where register_no = " . $_SESSION["laneno"] . " and upc = 'ENDOFSHIFT'";
    $eosR = mysql_query($eosQ);
    $num_rows = mysql_num_rows($eosR);
    if ($num_rows > 0) {
        $row = mysql_fetch_row($eosR);
        $EOS = $row[0];
    }
    else {
        $EOS = '2007-08-01 12:00:00'; // probably could change...
    }

    $query_ckq = "select * from cktenders where tdate > '" . $EOS . "' and register_no = " . $_SESSION["laneno"] . " order by emp_no, tdate";
    $query_ccq = "select * from cctenders where tdate > '" . $EOS . "' and register_no = " . $_SESSION["laneno"] . " order by emp_no, tdate";
    $query_dcq = "select * from dctenders where tdate > '" . $EOS . "' and register_no = " . $_SESSION["laneno"] . " order by emp_no, tdate";
    $query_miq = "select * from mitenders where tdate > '" . $EOS . "' and register_no = " . $_SESSION["laneno"] . " order by emp_no, tdate";
    $query_bp = "select * from buspasstotals where tdate > '" . $EOS . "' and register_no = " . $_SESSION["laneno"] . " order by emp_no, tdate";

    $fieldNames = "  ".substr("Time".$blank, 0, 10)
        . substr("Lane".$blank, 0, 7)
        . substr("Trans #".$blank, 0, 6)
        . substr("Emp #".$blank, 0, 8)
        . substr("Change".$blank, 0, 10)
        . substr("Amount".$blank, 0, 10)."\n";

    $ref = centerString(trim($_SESSION["CashierNo"]) . "-" . trim($_SESSION['laneno']) . " " . trim($_SESSION["cashier"]) . " " . build_time(time())) . "\n";

    $receipt .= chr(27) . chr(33) . chr(5) . centerString("T E N D E R  R E P O R T") . "\n";
    $receipt .= $ref;
    $receipt .=    centerString("------------------------------------------------------");
    $receipt .= str_repeat("\n", 2);

    $netQ = "SELECT SUM(total) AS net
        from dlog
        where tdate > '" . $EOS . "'
        and register_no = " . $_SESSION['laneno']. "
        and trans_type IN('I','D')
        and trans_subtype <> 'IC'
        AND emp_no <> 9999";

    $netR = mysql_query($netQ);
    $row = mysql_fetch_row($netR);

    $receipt .= "  " . substr("NET Total: " . $blank . $blank,0,20);
    $receipt .= substr($blank.number_format(($row[0]), 2), -8) . "\n";
    $receipt .= "\n";

    $tendertotalsQ = "SELECT t.TenderName as tender_type,ROUND(-sum(d.total),2) as total,COUNT(*) as count
        FROM dlog d RIGHT JOIN is4c_op.tenders t
        ON d.trans_subtype = t.TenderCode
        AND tdate > '" . $EOS . "'
        AND register_no = ". $_SESSION['laneno']." 
        AND d.emp_no <> 9999
        GROUP BY t.TenderName";

    $results_ttq = mysql_query($tendertotalsQ);

    while($row = mysql_fetch_row($results_ttq))    {
        if(!isset($row[0]))    {
            $receipt .= "NULL";
        }
        else{
            $receipt .= "  ".substr($row[0] . $blank . $blank, 0, 20);
        }
        if(!isset($row[1])) {
            $receipt .= "    0.00";
        }
        else{
            $receipt .= substr($blank . number_format($row[1], 2), -8);
        }
        if(!isset($row[2])) {
            $receipt .= "NULL";
        }
        else{
            if(!isset($row[1])) {
                $row[2] = 0;
            }
            $receipt .= substr($blank . $row[2], -4, 4);
        }
        $receipt .= "\n";
    } $receipt .= "\n";

    $cack_tot = "SELECT ROUND(SUM(total),2) AS gross
        FROM dlog
        WHERE tdate > '" . $EOS . "'
        AND register_no = ". $_SESSION['laneno']."
        AND trans_subtype IN ('CA','CK')";
    $results_tot = mysql_query($cack_tot);
    $row = mysql_fetch_row($results_tot);

    $receipt .= "  " . substr("CA & CK Total: " . $blank . $blank, 0, 20);
    $receipt .= substr($blank . number_format(($row[0] * -1), 2), -8) . "\n";
    
    $card_tot = "SELECT ROUND(SUM(total),2) AS gross
        FROM dlog
        WHERE tdate > '" . $EOS . "'
        AND register_no = " . $_SESSION['laneno'] . "
        AND trans_subtype IN ('DC','CC','FS','EC')";
    $results_tot = mysql_query($card_tot);
    $row = mysql_fetch_row($results_tot);

    $receipt .= "  " . substr("DC / CC / EBT Total: " . $blank . $blank, 0, 20);
    $receipt .= substr($blank . number_format(($row[0] * -1), 2), -8) . "\n";

    $hchrg_tot = "SELECT ROUND(SUM(total),2) AS gross
        FROM dlog
        WHERE tdate > '" . $EOS . "'
        AND register_no = ". $_SESSION['laneno'] . "
        AND trans_subtype = 'MI'
        AND card_no <> 9999";
    $results_tot = mysql_query($hchrg_tot);
    $row = mysql_fetch_row($results_tot);

    $receipt .= "  " . substr("House Charge Total: " . $blank . $blank, 0, 20);
    $receipt .= substr($blank . number_format(($row[0] * -1), 2), -8) . "\n";

    $schrg_tot = "SELECT ROUND(SUM(total),2) AS gross
        FROM dlog
        WHERE tdate > '" . $EOS . "'
        AND register_no = ". $_SESSION['laneno'] . "
        AND trans_subtype = 'MI'
        AND card_no = 9999";
    $results_tot = mysql_query($schrg_tot);
    $row = mysql_fetch_row($results_tot);

    $receipt .= "  " . substr("Store Charge Total: " . $blank . $blank, 0, 20);
    $receipt .= substr($blank . number_format(($row[0] * -1), 2), -8);

    $receipt .= str_repeat("\n", 5);    // apbw/tt 3/16/05 Franking II

    $receipt .= chr(27) . chr(33) . chr(5) . centerString("C H E C K   T E N D E R S") . "\n";

    $receipt .=    centerString("------------------------------------------------------");
 
    $result_ckq = sql_query($query_ckq, $db_a);
    $num_rows_ckq = sql_num_rows($result_ckq);

    if ($num_rows_ckq > 0) {
        $receipt .= $fieldNames;
        for ($i = 0; $i < $num_rows_ckq; $i++) {
            $row_ckq = sql_fetch_array($result_ckq);
            $timeStamp = timeStamp($row_ckq["tdate"]);
            $receipt .= "  " . substr($timeStamp . $blank, 0, 10)
                . substr($row_ckq["register_no"] . $blank, 0, 7)
                . substr($row_ckq["trans_no"] . $blank, 0, 6)
                . substr($row_ckq["emp_no"] . $blank, 0, 6)
                . substr($blank.number_format($row_ckq["changeGiven"], 2), -10)
                . substr($blank.number_format($row_ckq["ckTender"], 2), -10) . "\n";
        }

        $receipt.= centerString("------------------------------------------------------");

        $query_ckq = "select SUM(ckTender) from cktenders where tdate > '" . $EOS . "' and register_no = " . $_SESSION["laneno"];
        $result_ckq = sql_query($query_ckq, $db_a);
        $row_ckq = sql_fetch_array($result_ckq);

        $receipt .= substr($blank . $blank . $blank . $blank . "Total: " . number_format($row_ckq[0], 2), -56) . "\n";
    }
    else {
        $receipt .= "\n\n" . centerString(" * * *   N O N E   * * * ") . "\n\n"
            . centerString("------------------------------------------------------");
    }

    $receipt .= str_repeat("\n", 3);    // apbw/tt 3/16/05 Franking II
    $receipt .= chr(27) . chr(33) . chr(5) . centerString("D E B I T  C A R D  T E N D E R S") . "\n";
    $receipt .= centerString("------------------------------------------------------");
 
    $result_dcq = sql_query($query_dcq, $db_a);
    $num_rows_dcq = sql_num_rows($result_dcq);

    if ($num_rows_dcq > 0) {
        $receipt .= $fieldNames;

        for ($i = 0; $i < $num_rows_dcq; $i++) {

            $row_dcq = sql_fetch_array($result_dcq);
            $timeStamp = timeStamp($row_dcq["tdate"]);
            $receipt .= "  ".substr($timeStamp.$blank, 0, 10)
                .substr($row_dcq["register_no"].$blank, 0, 7)
                .substr($row_dcq["trans_no"].$blank, 0, 6)
                .substr($row_dcq["emp_no"].$blank, 0, 6)
                .substr($blank.number_format($row_dcq["changeGiven"], 2), -10)
                .substr($blank.number_format($row_dcq["dcTender"], 2), -10)."\n";
        }

        $receipt.= centerString("------------------------------------------------------");

        $query_dcq = "select SUM(dcTender) from dctenders where tdate > '" . $EOS . "' and register_no = " . $_SESSION["laneno"];
        $result_dcq = sql_query($query_dcq, $db_a);
        $row_dcq = sql_fetch_array($result_dcq);

        $receipt .= substr($blank . $blank . $blank . $blank . "Total: " . number_format($row_dcq[0], 2), -56) . "\n";
    }
    else {
        $receipt .= "\n\n" . centerString(" * * *   N O N E   * * * ") . "\n\n"
            . centerString("------------------------------------------------------");
    }

    $receipt .= str_repeat("\n", 3);    // apbw/tt 3/16/05 Franking II

    $receipt .= chr(27) . chr(33) . chr(5) . centerString("C R E D I T   C A R D   T E N D E R S") . "\n";
    $receipt .= centerString("------------------------------------------------------");
 
    $result_ccq = sql_query($query_ccq, $db_a);
    $num_rows_ccq = sql_num_rows($result_ccq);

    if ($num_rows_ccq > 0) {
        $receipt .= $fieldNames;
        for ($i = 0; $i < $num_rows_ccq; $i++) {
            $row_ccq = sql_fetch_array($result_ccq);
            $timeStamp = timeStamp($row_ccq["tdate"]);
            $receipt .= "  ".substr($timeStamp.$blank, 0, 10)
                . substr($row_ccq["register_no"].$blank, 0, 7)
                . substr($row_ccq["trans_no"].$blank, 0, 6)
                . substr($row_ccq["emp_no"].$blank, 0, 6)
                . substr($blank.number_format($row_ccq["changeGiven"], 2), -10)
                . substr($blank.number_format($row_ccq["ccTender"], 2), -10) . "\n";
        }

        $receipt.= centerString("------------------------------------------------------");

        $query_ccq = "select SUM(ccTender) from cctenders where tdate > '" . $EOS . "' and register_no = " . $_SESSION["laneno"];
        $result_ccq = sql_query($query_ccq, $db_a);
        $row_ccq = sql_fetch_array($result_ccq);
        $receipt .= substr($blank . $blank . $blank . $blank . "Total: " . number_format($row_ccq[0], 2), -56) . "\n";
    }
    else {
        $receipt .= "\n\n" . centerString(" * * *   N O N E   * * * ") . "\n\n"
            . centerString("------------------------------------------------------");
    }

    $receipt .= str_repeat("\n", 3);    // apbw/tt 3/16/05 Franking II

    $receipt .= centerString("H O U S E / S T O R E   C H A R G E   T E N D E R S")."\n";
    $receipt .= centerString("------------------------------------------------------");

    $result_miq = sql_query($query_miq, $db_a);
    $num_rows_miq = sql_num_rows($result_miq);

    if ($num_rows_miq > 0) {
        $chgFieldNames = "  " . substr("Time" . $blank, 0, 10)
                . substr("Lane" . $blank, 0, 7)
                . substr("Trans #" . $blank, 0, 6)
                . substr("Emp #" . $blank, 0, 8)
                . substr("Member #" . $blank, 0, 10)
                . substr("Amount" . $blank, 0, 10) . "\n";
        
        $receipt .= $chgFieldNames;

        for ($i = 0; $i < $num_rows_miq; $i++) {
            $row_miq = sql_fetch_array($result_miq);
            $timeStamp = timeStamp($row_miq["tdate"]);
            $receipt .= "  ".substr($timeStamp . $blank, 0, 10)
                . substr($row_miq["register_no"] . $blank, 0, 7)
                . substr($row_miq["trans_no"] . $blank, 0, 6)
                . substr($row_miq["emp_no"] . $blank, 0, 6)
                . substr($row_miq["card_no"] . $blank, 0, 6)
                . substr($blank . number_format($row_miq["MiTender"], 2), -10) . "\n";
        }

        $receipt .= centerString("------------------------------------------------------");

        $query_miq = "select SUM(miTender) from mitenders where tdate > '" . $EOS . "' and register_no = " . $_SESSION["laneno"];
        $result_miq = sql_query($query_miq, $db_a);
        $row_miq = sql_fetch_array($result_miq);

        $receipt .= substr($blank . $blank . $blank . $blank . "Total: " . number_format($row_miq[0], 2), -56) . "\n";
    }
    else {
        $receipt .= "\n\n" . centerString(" * * *   N O N E   * * * ") . "\n\n"
            . centerString("------------------------------------------------------");
    }
    $receipt .= str_repeat("\n", 3);    // apbw/tt 3/16/05 Franking II

    $receipt .= chr(27) . chr(33) . chr(5) . centerString("T R I - M E T  P A S S E S   S O L D") . "\n";
    $receipt .= centerString("------------------------------------------------------");

    $result_bp = sql_query($query_bp, $db_a);
    $num_rows_bp = sql_num_rows($result_bp);

    if ($num_rows_bp > 0) {

        $receipt .= $fieldNames;

        for ($i = 0; $i < $num_rows_bp; $i++) {
            $row_bp = sql_fetch_array($result_bp);
            $timeStamp = timeStamp($row_bp["tdate"]);
            $receipt .= "  " . substr($timeStamp . $blank, 0, 10)
                . substr($row_bp["register_no"] . $blank, 0, 7)
                . substr($row_bp["trans_no"] . $blank, 0, 6)
                . substr($row_bp["emp_no"] . $blank, 0, 6)
                . substr($blank . ($row_bp["upc"]), -10)
                . substr($blank . number_format($row_bp["total"], 2), -10) . "\n";
        }

        $receipt.= centerString("------------------------------------------------------");
    }
    else {
        $receipt .= "\n\n" . centerString(" * * *   N O N E   * * * ") . "\n\n"
        . centerString("------------------------------------------------------");
    }

    $receipt .= str_repeat("\n", 8);    // apbw/tt 3/16/05 Franking II

    writeLine($receipt . chr(27) . chr(105));    // apbw/tt 3/16/05 Franking II
    sql_close($db_a);

    $_SESSION["msgrepeat"] = 1;
    $_SESSION["strRemembered"] = "ES";
        gohome();
}

function timeStamp($time) {
    return strftime("%I:%M %p", strtotime($time));
}

