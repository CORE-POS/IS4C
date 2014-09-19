<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
// A page to search the member base.
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);

$page_title='Fannie - Member Management Module';
$header='Send Statements';
include('../../src/header.html');

if (isset($_REQUEST['send_email']) || isset($_REQUEST['skip_email']) || isset($_REQUEST['cardno'])){
    $cns = isset($_REQUEST['cardno'])?$_REQUEST['cardno']:array();;
    if (isset($_REQUEST['send_email'])){
        $to = $_REQUEST['email'];
        $sub = "Whole Foods Co-op IOU Notice";
        $msg = str_replace("\n","<br />",$_REQUEST['msg']);
        $msg = str_replace("\r","",$msg);
        $msg = "<html><body>".$msg."</body></html>";
        $headers = "From: Whole Foods Co-op <mms@wholefoods.coop>\r\n";
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        mail($to,$sub,$msg,$headers);
        $logQ = $dbc->prepare_statement("INSERT INTO emailLog VALUES (".$dbc->now().",?,?,'AR (Member EOM)')");
        $dbc->exec_statement($logQ,array($_REQUEST['curcard'],$to));
        echo "<i>E-mail sent to $to</i><hr />";
    }
    else if (isset($_REQUEST['skip_email'])){
        $to = $_REQUEST['email'];
        echo "<i>Did not send e-mail to $to</i><hr />";
    }
    if (!empty($cns)){
        $cur = array_shift($cns);
        $q = $dbc->prepare_statement("SELECT m.card_no, a.memName,
           m.email_1,
           a.TwoMonthBalance,a.LastMonthCharges,
           a.LastMonthPayments,a.LastMonthBalance
           FROM AR_EOM_Summary a LEFT JOIN
           meminfo m ON a.cardno = m.card_no
           LEFT JOIN custdata as c on c.cardno=a.cardno and c.personnum=1
           WHERE a.cardno=?");
        $r = $dbc->exec_statement($q,array($cur));
        $w = $dbc->fetch_row($r);

        echo "<form action=indvAR.php method=post>";
        foreach($cns as $c)
            echo "<input type=hidden name=cardno[] value=$c />";
        echo "<input type=hidden name=curcard value=$cur />";
        echo "<b>Email Address</b>: <input type=text size=45 name=email value=\"$w[2]\" /><br /><br />";
        echo "<b>Message Preview</b>:<br />";
        $beg = sprintf("%.2f",$w[3]);
        $chg = sprintf("%.2f",$w[4]);
        $pay = sprintf("%.2f",$w[5]);
        $bal = sprintf("%.2f",$w[6]);
            $span = date("F Y",mktime(0,0,0,date('m')-1,1,date('Y')));
        $msg = "Whole Foods Co-op
610 East Fourth Street
Duluth, MN 55805
(218) 728-0884

$w[0]
$w[1]
$w[2]

If payment has been made or sent, please ignore this invoice. If you have any questions about this invoice or would like to make arrangements to pay your balance, please write or call the Finance Department at the above address or e-mail mms@wholefoods.coop. See below for WFC IOU Policies. 
<table border=\"1\"><tr><td colspan=\"5\">Balance summary July 2010</td></tr>
<tr><td>Beginning Balance</td><td>Charges</td><td>Payments</td><td>Ending Balance</td><td>Amount Due</td></tr>
<tr><td>\$$beg</td><td>\$$chg</td><td>\$$pay</td><td>\$$bal</td><td>\$$bal</td></tr>
</table>\n";
        $histQ = $dbc->prepare_statement("SELECT card_no, max(charges) as charges, max(payments) as payments, 
            convert(varchar(50),date,101), trans_num,min(description),min(dept_name),
            count(*)
            FROM AR_statementHistory WHERE card_no = ?
            group by convert(varchar(50),date,101),trans_num,card_no
            order by max(date) desc");
        $msg .= "<table border=\"1\"><tr><td colspan=\"4\">Recent 90 Day History</td></tr>
<tr><td>Date</td><td>Receipt</td><td>Charges</td><td>Payments</td></tr>";
        $histR = $dbc->exec_statement($histQ,array($cur));
        while ($histW = $dbc->fetch_row($histR)){
            $msg .= sprintf("<tr><td>%s</td><td>%s</td><td>\$%.2f</td><td>\$%.2f</td></tr>",
                $histW[3],$histW[4],$histW[1],$histW[2]);
            if ($histW[7] > 0)
                $msg .= "<tr><td></td><td colspan=\"3\">(Multiple items)</td></tr>";
            elseif (!empty($histW[5]))
                $msg .= "<tr><td></td><td colspan=\"3\">$histW[5]</td></tr>";
            else
                $msg .= "<tr><td></td><td colspan=\"3\">$histW[6]</td></tr>";
        }
        $msg .= "</table>\n";

        $msg = str_replace("\n","<br />",$msg);
        echo $msg;

        $msg .= "<hr />
IOU POLICY 

OF WHOLE FOODS COMMUNITY CO-OP, INC. 

WFC members may charge purchases to a maximum of $20.00 payable within two (2) weeks from the date incurred. 
IOU's must be signed by the maker. IOU's may not, under any circumstances, be paid with Food Stamps or EBT card. 
WFC asks that its members only use the charge system for emergencies. 

-Members with an IOU account credit balance will receive a reminder of that balance on each purchase receipt.
-Members with an IOU debit balance will receive a reminder of that balance on each purchase receipt.

If WFC is not reimbursed by a member within sixty (60) days from the date of an overdue IOU for the amount of 
that person's membership may be terminated by the Board and any remaining stock, after reimbursement for all 
indebtedness owed to WFC, will be converted to non-voting Class B stock.

If WFC is not reimbursed by a member within sixty (60) days from the date of a bounced check for the amount of 
that check plus the amount of any administrative fee, that person's membership may be terminated by the Board 
and any remaining stock, after reimbursement for all indebtedness owed to WFC, will converted to non-voting 
Class B stock.  

IOU credit balances over sixty (60) days will be credited to the Member's non-voting Class B stock and the IOU 
account will be adjusted to zero.   Members may request the return of Class B stock in excess of the amount 
required by the By-Laws by submitting to the Board a Request to Terminate that excess stock.

At the discretion of the General Manager, member business and non-profit agency accounts may have higher IOU 
limits and/or extended payment terms.

SPECIAL ORDERS
Special orders not picked up or paid for within thirty (30) days of the time items are received at WFC will 
be put out for sale or disposed of at management discretion.  Future special orders from members or from non-
members who have not previously promptly paid for and/or picked up special orders, at management discretion, 
may require prepayment.

NEWSLETTER ADS
Members may charge the cost of advertising their business in WFC's newsletter under the same IOU payment terms 
as noted above but on an IOU account separate from the member's IOU account for inventory purchases.   

Members will be mailed an invoice within ten (10) days of the date of publication for the amount of the advertising 
charge.  Failure to pay the amount due is then subject to the provisions of this IOU policy.

NOTE
Memberships with IOUs and/or other credit problems in excess of sixty (60) days may be placed on inactive status 
by management pending Board action.  Purchases by inactive members will not be recorded and will not count toward 
eligibility for a patronage rebate.   Purchases by inactive members are not eligible for member discounts or member 
specials. Memberships inactivated or terminated due to credit problems will be eligible for reactivation subject 
to Board discretion with respect to access to member credit benefits.";
        $msg = str_replace("\n","<br />",$msg);
        $msg = str_replace("\r","",$msg);
        $msg = str_replace("\"","",$msg);
        echo "<input type=hidden name=msg value=\"$msg\" />";

        echo "<input type=submit name=send_email value=\"Send Email\" />";
        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        echo "<input type=submit name=skip_email value=\"Do Not Send This Email\" />";
        echo "</form>";
    }
    else {
        echo "Done with all selected memberships<br />
            <a href=index.php>Home</a>";
    }
}
elseif (!isset($_REQUEST['cardno'])){
    echo "<form action=indvAR.php method=post>";
    $query = $dbc->prepare_statement("SELECT a.cardno, c.lastname,i.email_1
                   FROM AR_EOM_Summary a 
                   LEFT JOIN custdata as c on c.cardno=a.cardno and c.personnum=1
           LEFT JOIN meminfo AS i ON c.cardno=i.card_no
                   WHERE c.type not in ('TERM') and
                   c.memtype <> 9 and a.twoMonthBalance > 1
                   and c.Balance <> 0
                   and a.lastMonthPayments < a.twoMonthBalance
                   ORDER BY a.cardno");
    $result = $dbc->exec_statement($query);
    echo "<table cellspacing=0 cellpadding=4 border=1>
        <tr><th>&nbsp;</th><th>Member</th><th>E-mail</th></tr>";
    while($row = $dbc->fetch_row($result)){
        printf("<tr><td>%s</td>
            <td>%d - %s</td><td>%s</td></tr>",
            (empty($row[2])?'&nbsp;':"<input type=checkbox name=cardno[] value=$row[0] />"),
            $row[0],$row[1],$row[2]);
    }
    echo "</table>";
    echo "<input type=submit value=\"Preview Emails\" />";
    echo "</form>";
}

include('../../src/footer.html');
?>
