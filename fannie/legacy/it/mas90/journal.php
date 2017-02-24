<?php
include('../../../config.php');

require($FANNIE_ROOT.'auth/login.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");

include('../../db.php');

if (isset($_GET['action'])){

    $out = $_GET['action']."`";
    switch($_GET['action']){
    case "initDate":
        $date = $_GET["date"];
        $out .= mainDisplay($date,True);
        break;
    case 'reInit':
        $date = $_GET["date"];
        $out .= mainDisplay($date,False);
        break;
    case 'save':
        $date = $_GET["date"];
        $type = $_GET["type"];
        $data = $_GET["data"];
        save($date,$type,$data);
        if ($type == "X") $out .= "Save successful";
    }
    echo $out;
    return;
}

if (!validateUserQuiet('mas90_journal')){
    header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/mas90/journal.php");
    return;
}

function mainDisplay($date,$checkForSave=True){
    global $sql;
    $ret = "";
    
    $FROM_SAVED = False;
    if ($checkForSave){
        $checkQ = $sql->prepare("select * from dailyJournal where datediff(dd,tdate,?) = 0");
        $checkR = $sql->execute($checkQ, array($date));
        if ($sql->num_rows($checkR) > 0) $FROM_SAVED = True;
    }

    $ret .= "<a href=\"\" onclick=\"save(); return false;\">Save</a> | ";
    $ret .= "<a href=\"\" onclick=\"reInit(); return false;\">Reload from POS</a> | ";
    $ret .= "<a href=\"\" onclick=\"csv(); return false;\">Export to CSV</a><br />";

    $ret .= "<table id=thetable cellpadding=3 cellspacing=0 border=1>";
    $ret .= "<tr><td>&nbsp;</td><th>Input</th><th colspan=3>Journal Entries</th></tr>";
    $ret .= "<tr><td>&nbsp;</td><td>&nbsp;</td>";
    $ret .= "<td style=\"width:7em; text-align: center;\"><i>Debit</i></td>";
    $ret .= "<td style=\"width:7em; text-align: center;\"><i>Credit</i></td>";
    $ret .= "<td style=\"width:7em; text-align: center;\"><i>Account</i></td></tr>";

    $dlog = DTransactionsModel::selectDlog($date);
    
    $tenderQ = "select t.tenderName,-sum(d.total) as total 
        from $dlog as d, Tenders as t
        where datediff(dd,?,d.tDate) = 0
        and d.trans_status <> 'X'
        and d.trans_subtype = t.tenderCode
        group by t.tenderName";
    if ($FROM_SAVED){
        $tenderQ = "select sub_type,value from dailyJournal
                where datediff(dd,tdate,?) = 0 and
                type = 'T'";
    }
    $tenderP = $sql->prepare($tenderQ);
    $tenderR = $sql->execute($tenderP, array($date));
    $cash = 0;
    $check = 0;
    $MAD = 0;
    $RRR = 0;
    $coupons = 0;
    $GC = 0;
    $TC = 0;
    $storecharge = 0;
    $EBT = 0;
    $MC = 0;
    $Visa = 0;
    $Disc1 = 0;
    $Disc2 = 0;
    $instoreCoup = 0;
    while ($w = $sql->fetch_row($tenderR)){
        if ($w[0] == "Cash") $cash = $w[1];
        elseif ($w[0] == "Check") $check = $w[1];
        elseif ($w[0] == "MAD Coupon") $MAD = $w[1];
        elseif ($w[0] == "RRR Coupon") $RRR = $w[1];
        elseif ($w[0] == "Coupons") $coupons = $w[1];
        elseif ($w[0] == "Gift Card") $GC = $w[1];
        elseif ($w[0] == "GIFT CERT") $TC = $w[1];
        elseif ($w[0] == "InStore Charges") $storecharge = $w[1];
        elseif ($w[0] == "EBT") $EBT = $w[1];
        elseif ($w[0] == "MC") $MC = $w[1];
        elseif ($w[0] == "Visa") $Visa = $w[1];
        elseif ($w[0] == "Discover1") $Disc1 = $w[1];
        elseif ($w[0] == "Discover2") $Disc2 = $w[1];
        elseif ($w[0] == "InStoreCoupon") $instoreCoup = $w[1];
    }

    $ret .= "<tr><td>Deposit</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>Cash</td><td>";
    $ret .= "<input onchange=\"resumTenders();resumTotals();\" name=input id=inputCash type=text value=\"$cash\" size=8 /></td>";
    $ret .= "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>Check</td><td>";
    $ret .= "<input onchange=\"resumTenders();resumTotals();\" name=input id=inputCheck type=text value=\"$check\" size=8 /></td>";
    $ret .= "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>TOTAL Deposit</td><td id=depositTotal>&nbsp;</td>";
    $ret .= "<td name=jDebit id=jDepositTotal align=right>&nbsp;</td><td>&nbsp;</td><td align=right>10120</td></tr>";

    $ret .= "<tr><td>Credit Cards/EBT</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>EBT/Debit Approved</td><td>";
    $ret .= "<input onchange=\"resumTenders();resumTotals();\" name=input id=inputEBT type=text size=8 value=\"$EBT\" /></td>";
    $ret .= "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>MasterCard</td><td>";
    $ret .= "<input onchange=\"resumTenders();resumTotals();\" name=input id=inputCCMC type=text size=8 value=\"$MC\" /></td>";
    $ret .= "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>VISA</td><td>";
    $ret .= "<input onchange=\"resumTenders();resumTotals();\" name=input id=inputCCVisa type=text size=8 value=\"$Visa\" /></td>";
    $ret .= "<td name=jDebit id=jCCMain align=right>&nbsp;</td><td>&nbsp;</td><td align=right>&nbsp;</td></tr>";

    $style = "";
    if ($Disc1 == 0) $style = "display:none;";
    $ret .= "<tr style=\"$style\"><td>Discover Discount</td><td>";
    $ret .= "<input onchange=\"resumTenders();resumTotals();\" name=input id=inputCCDisc1 type=text size=8 value=\"$Disc1\" /></td>";
    $ret .= "<td name=jDebit id=jCCDisc1 align=right>&nbsp;</td><td>&nbsp;</td><td align=right>&nbsp;</td></tr>";

    $ret .= "<tr><td>Discover Outlet Total</td><td>";
    $ret .= "<input onchange=\"resumTenders();resumTotals();\" name=input id=inputCCDisc2 type=text size=8 value=\"$Disc2\" /></td>";
    $ret .= "<td name=jDebit id=jCCDisc2 align=right>&nbsp;</td><td>&nbsp;</td><td align=right>&nbsp;</td></tr>";

    $ret .= "<tr><td>Total FAPS</td><td id=totalFAPs>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
    $ret .= "<tr><td>Total All Credit Cards/EBT</td><td id=totalCCEBT>&nbsp;</td><td name=jDebit id=jTotalCCEBT align=right>&nbsp;</td>";
    $ret .= "<td>&nbsp;</td><td align=right>10120</td></tr>";

    $ret .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";


    $ret .= "<tr><td>RRR Coupon</td><td>";
    $ret .= "<input onchange=\"resumTenders();resumTotals();\" name=input id=inputRRR type=text value=\"$RRR\" size=8 /></td>";
    $ret .= "<td name=jDebit id=jRRR align=right>&nbsp;</td><td>&nbsp;</td><td align=right>63380</td></tr>";

    $ret .= "<tr><td>Coupons</td><td>";
    $ret .= "<input onchange=\"resumTenders();resumTotals();\" name=input id=inputCoupons type=text value=\"$coupons\" size=8 /></td>";
    $ret .= "<td name=jDebit id=jCoupons align=right>&nbsp;</td><td>&nbsp;</td><td align=right>10740</td></tr>";

    $ret .= "<tr><td>Gift Card as Tender</td><td>";
    $ret .= "<input onchange=\"resumTenders();resumTotals();\" name=input id=inputGC type=text value=\"$GC\" size=8 /></td>";
    $ret .= "<td name=jDebit id=jGC align=right>&nbsp;</td><td>&nbsp;</td><td align=right>21205</td></tr>";

    $ret .= "<tr><td>Gift Cert as Tender</td><td>";
    $ret .= "<input onchange=\"resumTenders();resumTotals();\" name=input id=inputTC type=text value=\"$TC\" size=8 /></td>";
    $ret .= "<td name=jDebit id=jTC align=right>&nbsp;</td><td>&nbsp;</td><td align=right>21200</td></tr>";

    $ret .= "<tr><td>InStore Charges</td><td>";
    $ret .= "<input onchange=\"resumTenders();resumTotals();\" name=input id=inputStoreCharge type=text value=\"$storecharge\" size=8 /></td>";
    $ret .= "<td name=jDebit id=jStoreCharge align=right>&nbsp;</td><td>&nbsp;</td><td align=right>10710</td></tr>";

    $ret .= "<tr><td>InStore Coupons</td><td>";
    $ret .= "<input onchange=\"resumTenders();resumTotals();\" name=input id=inputInStoreCoup type=text value=\"$instoreCoup\" size=8 /></td>";
    $ret .= "<td name=jDebit id=jInStoreCoup align=right>&nbsp;</td><td>&nbsp;</td><td align=right>67710</td></tr>";
    $ret .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>Tenders Total</td><td id=tenderTotal>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>SALES</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
    $ret .= "<tr><td>pCode</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $salesQ = "select d.pCode,sum(l.total) as total from
        $dlog as l join departments as d on l.department = d.dept_no
        where datediff(dd,?,tDate) = 0
        and l.department < 600 and l.department <> 0
        and l.trans_type <> 'T'
        group by d.pCode
        order by d.pCode";
    if ($FROM_SAVED){
        $salesQ = "select sub_type,value from dailyJournal where
               datediff(dd,tdate,?) = 0 and type='P' order by sub_type";
    }
    $pCodes = array(41201,41205,41300,41305,41310,41315,41400,41405,41407,41410,41415,41420,
            41425,41430,41435,41440,41500,41505,41510,41515,41520,41525,41530,41600,
            41605,41610,41640,41645,41700,41705);
    $i = 0;
    $slaesP = $sql->prepare($salesQ);
    $salesR = $sql->execute($salesP, array($date));
    while ($w = $sql->fetch_row($salesR)){
        if ($i >= count($pCodes)) break;
        while ($w[0] > $pCodes[$i]){
            $ret .= "<tr><td>{$pCodes[$i]}</td>";
            $ret .= "<td><input onchange=\"resumSales();resumTotals();\" name=input id=inputPcode{$pCodes[$i]} type=text size=8 value=0 /></td>";
            $ret .= "<td name=jDebit id=jDebit{$pCodes[$i]} align=right>&nbsp;</td>";
            $ret .= "<td name=jCredit id=jCredit{$pCodes[$i]} align=right>&nbsp;</td>";
            $ret .= "<td align=right>{$pCodes[$i]}</td></tr>";
            $i++;
        }
        if ($w[0] == $pCodes[$i]){
            $ret .= "<tr><td>{$pCodes[$i]}</td>";
            $ret .= "<td><input onchange=\"resumSales();resumTotals();\" name=input id=inputPcode{$pCodes[$i]} type=text size=8 value=\"$w[1]\" /></td>";
            $ret .= "<td name=jDebit id=jDebit{$pCodes[$i]} align=right>&nbsp;</td>";
            $ret .= "<td name=jCredit id=jCredit{$pCodes[$i]} align=right>&nbsp;</td>";
            $ret .= "<td align=right>{$pCodes[$i]}</td></tr>";
            $i++;
        }
    }
    while ($i < count($pCodes)){
        $ret .= "<tr><td>{$pCodes[$i]}</td>";
        $ret .= "<td><input onchange=\"resumSales();resumTotals();\" name=input id=inputPcode{$pCodes[$i]} type=text size=8 value=0 /></td>";
        $ret .= "<td name=jDebit id=jDebit{$pCodes[$i]} align=right>&nbsp;</td>";
        $ret .= "<td name=jCredit id=jCredit{$pCodes[$i]} align=right>&nbsp;</td>";
        $ret .= "<td align=right>{$pCodes[$i]}</td></tr>";
        $i++;
    }

    $ret .= "<tr><td>column TOTAL</td><td id=totalPcode>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
    
    $totalQ = $sql->prepare("select sum(l.total) as totalSales from $dlog as l
        where datediff(dd,?,tDate) = 0
        and l.department < 600 and l.department <> 0
        and l.trans_type <> 'T'");
    $totalR = $sql->execute($totalQ, array($date));
    $totalW = $sql->fetch_row($totalR);
    $ret .= "<tr><td>Total Sales POS report</td><td id=totalPOS>$totalW[0]</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>Difference</td><td id=salesDiff>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $otherQ = "select d.department,sum(total) as total from $dlog as d
        where datediff(dd,?,tDate) = 0
        and d.department > 300 and d.department <> 0 and d.register_no <> 20
        group by d.department order by d.department";
    if ($FROM_SAVED){
        $otherQ = "select sub_type,value from dailyJournal where
               datediff(dd,tdate,?) = 0 and type = 'O'";
    }    
    $otherP = $sql->prepare($otherQ);
    $otherR = $sql->execute($otherP, array($date));
    
    $gcSales = 0;
    $tcSales = 0;
    $miscPO = 0;
    $classA = 0;
    $classB = 0;
    $ar = 0;
    $ITCorrections = 0;
    $misc1 = 0;
    $misc2 = 0;
    $supplies = 0;
    $class = 0;
    $foundMoney = 0;
    $totes = 0;
    while ($w = $sql->fetch_row($otherR)){
        if ($w[0] == 902) $gcSales = $w[1];
        elseif ($w[0] == 900) $tcSales = $w[1];
        elseif ($w[0] == 604) $miscPO = $w[1];
        elseif ($w[0] == 992) $classA = $w[1];
        elseif ($w[0] == 991) $classB = $w[1];
        elseif ($w[0] == 990) $ar = $w[1];
        elseif ($w[0] == 800) $ITCorrections = $w[1];
        elseif ($w[0] == 801) $misc1 = $w[1];
        elseif ($w[0] == 802) $misc2 = $w[1];
        elseif ($w[0] == 600) $supplies = $w[1];
        elseif ($w[0] == 708) $class = $w[1];
        elseif ($w[0] == 700) $totes = $w[1];
        elseif ($w[0] == "FOUND") $foundMoney = $w[1];
    }

    $ret .= "<tr><td>Gift Card Sales</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputGCSales type=text size=8 value=\"$gcSales\" /></td>";
    $ret .= "<td name=jDebit id=jDebitGCSales align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditGCSales align=right>&nbsp;</td><td align=right>21205</td></tr>";

    $ret .= "<tr><td>Gift Certificate Sales</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputTCSales type=text size=8 value=\"$tcSales\" /></td>";
    $ret .= "<td name=jDebit id=jDebitTCSales align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditTCSales align=right>&nbsp;</td><td align=right>21200</td></tr>";

    $ret .= "<tr><td>Misc PO</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputMiscPO type=text size=8 value=\"$miscPO\" /></td>";
    $ret .= "<td name=jDebit id=jDebitMiscPO align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditMiscPO align=right>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>EQUITY</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>Class A Equity</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputClassA type=text size=8 value=\"$classA\" /></td>";
    $ret .= "<td name=jDebit id=jDebitClassA align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditClassA align=right>&nbsp;</td><td align=right>31100</td></tr>";

    $ret .= "<tr><td>Class B Equity</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputClassB type=text size=8 value=\"$classB\" /></td>";
    $ret .= "<td name=jDebit id=jDebitClassB align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditClassB align=right>&nbsp;</td><td align=right>31110</td></tr>";

    $ret .= "<tr><td>TOTAL Equity</td><td id=totalEquity>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>AR Payments</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputAR type=text size=8 value=\"$ar\" /></td>";
    $ret .= "<td name=jDebit id=jDebitAR align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditAR align=right>&nbsp;</td><td align=right>10710</td></tr>";

    $ret .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>Discounts</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $discountQ = "select m.memDesc, sum(d.total)*-1 as discount from
        $dlog as d inner join custdata as c on c.cardno = d.card_no
        inner join memtype as m on c.memType = m.memtype
        where datediff(dd,?,d.tdate) = 0
        and d.upc = 'DISCOUNT' and c.personnum = 1
        group by m.memDesc,d.upc";
    if ($FROM_SAVED){
        $discountQ = "select sub_type,value from dailyJournal where
                  datediff(dd,tdate,?) = 0 and type = 'D'";
    }
    $discountP = $sql->prepare($discountQ);
    $discountR = $sql->execute($discountP, array($date));
    $discMem = 0;
    $discStaffMem = 0;
    $discStaffNonMem = 0;
    while ($w = $sql->fetch_row($discountR)){
        if ($w[0] == "Member") $discMem = $w[1];
        elseif ($w[0] == "Staff Member") $discStaffMem = $w[1];
        elseif ($w[0] == "Staff NonMem") $discStaffNonMem = $w[1];
    }

    $ret .= "<tr><td>Member</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputMemDisc type=text size=8 value=\"$discMem\" /></td>";
    $ret .= "<td name=jDebit id=jDebitDiscMem align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditDiscMem align=right>&nbsp;</td><td align=right>66600</td></tr>";

    $ret .= "<tr><td>Staff Member</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputStaffMemDisc type=text size=8 value=\"$discStaffMem\" /></td>";
    $ret .= "<td name=jDebit id=jDebitDiscStaffMem align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditDiscStaffMem align=right>&nbsp;</td><td align=right>61170</td></tr>";

    $ret .= "<tr><td>Staff NonMem</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputStaffNonMemDisc type=text size=8 value=\"$discStaffNonMem\" /></td>";
    $ret .= "<td name=jDebit id=jDebitDiscStaffNonMem align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditDiscStaffNonMem align=right>&nbsp;</td><td align=right>61170</td></tr>";
    $ret .= "<tr><td>MAD Coupon</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputMAD type=text value=\"$MAD\" size=8 /></td>";
    $ret .= "<td name=jDebit id=jDebitMAD align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditMAD align=right>&nbsp;</td><td align=right>66600</td></tr>";

    $ret .= "<tr><td>TOTAL Discounts</td><td id=totalDisc>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $taxQ = "select sum(total) as tax_collected from $dlog as d
        where datediff(dd,?,tdate) = 0
        and d.upc = 'TAX' group by d.upc";
    if ($FROM_SAVED){
        $taxQ = "select value from dailyJournal where type = 'X'
             and datediff(dd,tdate,?) = 0";
    }
    $taxP = $sql->prepare($taxQ);
    $taxR = $sql->execute($taxP, array($date));
    $taxW = $sql->fetch_row($taxR);

    $ret .= "<tr><td>Sales Tax Collected</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputTax type=text size=8 value=\"$taxW[0]\" /></td>";
    $ret .= "<td name=jDebit id=jDebitTax align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditTax align=right>&nbsp;</td><td align=right>21180</td></tr>";

    $ret .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>Other Income</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>IT Corrections</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputITCorrections type=text size=8 value=\"$ITCorrections\" /></td>";
    $ret .= "<td name=jDebit id=jDebitITCorrections align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditITCorrections align=right>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td>Misc. #1</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputMisc1 type=text size=8 value=\"$misc1\" /></td>";
    $ret .= "<td name=jDebit id=jDebitMisc1 align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditMisc1 align=right>&nbsp;</td><td align=right>42231</td></tr>";

    $ret .= "<tr><td>Misc. #2</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputMisc2 type=text size=8 value=\"$misc2\" /></td>";
    $ret .= "<td name=jDebit id=jDebitMisc2 align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditMisc2 align=right>&nbsp;</td><td align=right>42232</td></tr>";


    $ret .= "<tr><td>Supplies (Stamps sold)</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputSupplies type=text size=8 value=\"$supplies\" /></td>";
    $ret .= "<td name=jDebit id=jDebitSupplies align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditSupplies align=right>&nbsp;</td><td align=right>64410</td></tr>";

    $ret .= "<tr><td>Class (public not staff)</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputClass type=text size=8 value=\"$class\" /></td>";
    $ret .= "<td name=jDebit id=jDebitClass align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditClass align=right>&nbsp;</td><td align=right>42225</td></tr>";

    $ret .= "<tr><td>Found Money</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputFound type=text size=8 value=\"$foundMoney\" /></td>";
    $ret .= "<td name=jDebit id=jDebitFound align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditFound align=right>&nbsp;</td><td align=right>63350</td></tr>";

    $ret .= "<tr><td>Totes</td><td>";
    $ret .= "<input onchange=\"resumOtherIncome();resumTotals();\" name=input id=inputTotes type=text size=8 value=\"$totes\" /></td>";
    $ret .= "<td name=jDebit id=jDebitTotes align=right>&nbsp;</td>";
    $ret .= "<td name=jCredit id=jCreditTotes align=right>&nbsp;</td><td align=right>63320</td></tr>";

    $miscCount = 0;
    if ($FROM_SAVED){
        $miscQ = $sql->prepare("select sub_type,value from dailyJournal where
              datediff(dd,?,tdate) = 0 and type='M'");
        $miscR = $sql->execute($miscQ, array($date));
        while ($row = $sql->fetch_row($miscR)){
            $ret .= "<tr><td>MiscReceipt</td>";
            $ret .= "<td><input onchange=\"resumMisc();resumTotals();\" type=text size=8 value=$row[1] id=inputMisc$miscCount /></td>";
            $ret .= "<td name=jDebit id=jDebitMisc$miscCount align=right>&nbsp;</td>";
            $ret .= "<td name=jCredit id=jCreditMisc$miscCount align=right>&nbsp;</td>";
            $ret .= "<td align=right><input type=text size=8 value=\"$row[0]\" id=accountMisc$miscCount /></td>";
            $ret .= "</tr>";
            $miscCount++;
        }
    }
    $ret .= "<tr><td><a href=\"\" onclick=\"addMisc(); return false;\">Add MiscReceipt</a></td>";
    $ret .= "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
    $ret .= "<input type=hidden id=miscCount value=$miscCount />";

    $ret .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><td><i>SHEET SUBTOTAL</i></td><td>&nbsp;</td>";
    $ret .= "<td id=sheetSubDebit align=right>&nbsp;</td><td id=sheetSubCredit align=right>&nbsp;</td><td>&nbsp;</td></tr>";
    $ret .= "<tr><td>Over/Short</td><td id=overshort>&nbsp;</td>";
    $ret .= "<td id=debitOvershort align=right>&nbsp;</td><td id=creditOvershort align=right>&nbsp;</td><td align=right>63350</td></tr>";

    $ret .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

    $ret .= "<tr><th>SHEET TOTAL</th><td>&nbsp;</td>";
    $ret .= "<td id=sheetDebit align=right>&nbsp;</td><td id=sheetCredit align=right>&nbsp;</td><td>&nbsp;</td></tr>";
    $ret .= "<tr><td><i>SHEET IMBALANCE</i></td><td>&nbsp;</td><td>&nbsp;</td><td id=sheetDiff align=right>&nbsp;</td><td>&nbsp;</td></tr>";
    $ret .= "</table>";

    return $ret;
}

function save($date,$type,$data){
    global $sql;
    if ($type == 'M'){
        $delQ = $sql->prepare("delete from dailyJournal where datediff(dd,tdate,?) = 0
            and type = 'M'");
        $sql->execute($delQ, array($date));
    }
    if ($data == "") return;
    $data_pairs = explode(";",$data);
    foreach ($data_pairs as $dp){
        list($sub,$val) = explode(":",$dp);
        $val = rtrim($val);
        if(empty($val)) $val = 0;    
        
        $checkQ = $sql->prepare("select value from dailyJournal where datediff(dd,tdate,?) = 0
               and type=? and sub_type=?");
        $checkR = $sql->execute($checkQ, array($date, $type, $sub));
        if ($type=="M" || $sql->num_rows($checkR) == 0){
            $insQ = $sql->prepare("insert dailyJournal values (?, ?, ?, ?)");
            $insR = $sql->execute($insQ, array($date, $type, $sub, $val));
        }
        else {
            $upQ = $sql->prepare("update dailyJournal set value=? where
                datediff(dd,tdate,?) = 0 and type=? and sub_type=?");
            $upR = $sql->execute($upQ, array($val, $date, $type, $sub));
        }
    }
}

?>

<html>
<head><title></title>
<script type=text/javascript src=journal.js></script>
<style type=text/css>
a {
    color: blue;
}
</style>
</head>
<input type=hidden id=currentDate />
<form onsubmit="initDate(); return false;">
<b>Date</b>: <input type=text id=init_date /> <input type=submit value=Submit />
</form>
<div id=contentarea>

</div>
