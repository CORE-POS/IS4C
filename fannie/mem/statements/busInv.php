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

$trans = $FANNIE_TRANS_DB;
if ($FANNIE_SERVER_DBMS == 'MSSQL') $trans .= ".dbo";

if (isset($_REQUEST['send_email']) || isset($_REQUEST['skip_email']) || isset($_REQUEST['cardno'])){
    $cns = isset($_REQUEST['cardno'])?$_REQUEST['cardno']:array();;
    if (isset($_REQUEST['send_email'])){
        $to = $_REQUEST['email'];
        $sub = "Whole Foods Co-op Invoice";
        $msg = str_replace("\n","<br />",$_REQUEST['msg']);
        $msg = str_replace("\r","",$msg);
        $msg = "<html><body>".$msg."</body></html>";
        $headers = "From: Whole Foods Co-op <mms@wholefoods.coop>\r\n";
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        mail($to,$sub,$msg,$headers);
        $logQ = $dbc->prepare_statement("INSERT INTO emailLog VALUES (".$dbc->now().",?,?,'AR (Business Any Balance)')");
        $dbc->exec_statement($logQ,array($_REQUEST['curcard'],$to));
        echo "<i>E-mail sent to $to</i><hr />";
    }
    else if (isset($_REQUEST['skip_email'])){
        $to = $_REQUEST['email'];
        echo "<i>Did not send e-mail to $to</i><hr />";
    }
    if (!empty($cns)){
        $cur = array_shift($cns);
        $q = $dbc->prepare_statement("SELECT m.card_no, 
           CASE WHEN c.firstname='' THEN c.lastname ELSE c.firstname+' '+c.lastname END,
           m.email_1,n.balance
           FROM meminfo AS m LEFT JOIN
           custdata as c on c.cardno=m.card_no and c.personnum=1
           LEFT JOIN {$trans}.ar_live_balance AS n
           ON m.card_no=n.card_no
           WHERE m.card_no=?");
        $r = $dbc->exec_statement($q,array($cur));
        $w = $dbc->fetch_row($r);

        echo "<form action=busInv.php method=post>";
        foreach($cns as $c)
            echo "<input type=hidden name=cardno[] value=$c />";
        echo "<input type=hidden name=curcard value=$cur />";
        echo "<b>Email Address</b>: <input type=text size=45 name=email value=\"$w[2]\" /><br /><br />";
        echo "<b>Message Preview</b>:<br />";
        $bal = sprintf("%.2f",$w[3]);

        $trans = $FANNIE_TRANS_DB;
        if ($FANNIE_SERVER_DBMS=='MSSQL') $trans .= ".dbo";
        $priorQ = $dbc->prepare_statement("SELECT sum(charges) - sum(payments) FROM {$trans}.ar_history
            WHERE datediff(dd,getdate(),tdate) < -90
            AND card_no = ?");
        $priorR = $dbc->exec_statement($priorQ,array($cur));
        $priorBalance = array_pop($dbc->fetch_row($priorR));

        $msg = "Whole Foods Co-op
610 East Fourth Street
Duluth, MN 55805
(218) 728-0884

$w[0]
$w[1]
$w[2]

If payment has been made or sent, please ignore this invoice. If you have any questions about this invoice or would like to make arrangements to pay your balance, please write or call the Finance Department at the above address or e-mail mms@wholefoods.coop.\n"; 

$msg .= "\n<b>Balance Forward</b>: \$".$priorBalance."\n";

        $histQ = $dbc->prepare_statement("SELECT card_no, max(charges) as charges, max(payments) as payments, 
            convert(varchar(50),date,101), trans_num,min(description),min(dept_name),
            count(*)
            FROM AR_statementHistory WHERE card_no = ?
            group by convert(varchar(50),date,101),trans_num,card_no
            order by max(date) desc");
        $gazetteFlag = False;
        $msg .= "<table border=\"1\"><tr><td colspan=\"4\">Recent 90 Day History</td></tr>
<tr><td>Date</td><td>Receipt</td><td>Charges</td><td>Payments</td></tr>";
        $histR = $dbc->exec_statement($histQ,array($cur));
        while ($histW = $dbc->fetch_row($histR)){
            $msg .= sprintf("<tr><td>%s</td><td>%s</td><td>\$%.2f</td><td>\$%.2f</td></tr>",
                $histW[3],$histW[4],$histW[1],$histW[2]);
            if ($histW[2] > 0)
                $msg .= "<tr><td></td><td colspan=\"3\">Payment - Thank You!</td></tr>";
            elseif ($histW[7] > 0)
                $msg .= "<tr><td></td><td colspan=\"3\">(Multiple items)</td></tr>";
            elseif (!empty($histW[5]))
                $msg .= "<tr><td></td><td colspan=\"3\">$histW[5]</td></tr>";
            else
                $msg .= "<tr><td></td><td colspan=\"3\">$histW[6]</td></tr>";
            if ($histW[5] == 'Gazette Ad' || $histW[6] == 'Gazette Ad')
                $gazetteFlag = True;
        }
        $msg .= "</table>\n";

        $msg .= "<b>Amount Due</b>: \$".$bal."\n";

        if ($gazetteFlag){
            $msg .= "<br /><br />";
            $msg .= "To continue, discontinue, or alter your gazette advertisement, ";
            $msg .= "Please <a href=\"http://wholefoods.coop/gz/?m=$cur\">Click Here</a> ";
            $msg .= "or copy/paste this link into your browser's address bar:<br />"; 
            $msg .= "http://wholefoods.coop/gz/?m=$cur";
            $msg .= "<br /><br />";
        }

        $msg = str_replace("\n","<br />",$msg);
        echo $msg;

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
    echo "<form action=busInv.php method=post>";
    $query = $dbc->prepare_statement("SELECT c.cardno, c.lastname,i.email_1
                           FROM 
                           custdata as c 
               LEFT JOIN meminfo AS i ON c.cardno=i.card_no
               LEFT JOIN {$trans}.ar_live_balance AS n
               ON c.cardno=n.card_no
                           WHERE c.type not in ('TERM') and
                           c.memtype = 2 AND c.personnum=1
               and n.balance > 0    
                           ORDER BY c.cardno");
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
