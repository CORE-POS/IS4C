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
        $sub = "Whole Foods Co-op Equity Notice";
        $msg = $_REQUEST['msg'];
        $headers = "From: Whole Foods Co-op <mms@wholefoods.coop>\r\n";
        mail($to,$sub,$msg,$headers);
        $logQ = $dbc->prepare_statement("INSERT INTO emailLog VALUES (".$dbc->now().",?,?,'Equity')");
        $dbc->exec_statement($logQ,array($_REQUEST['curcard'],$to));
        echo "<i>E-mail sent to $to</i><hr />";
    }
    else if (isset($_REQUEST['skip_email'])){
        $to = $_REQUEST['email'];
        echo "<i>Did not send e-mail to $to</i><hr />";
    }
    if (!empty($cns)){
        $cur = array_shift($cns);
        $q = $dbc->prepare_statement("SELECT m.card_no,c.firstname,c.lastname,
                    m.email_1,n.payments, 
            convert(varchar,d.end_date,101)
            FROM meminfo AS m LEFT JOIN
            custdata AS c ON m.card_no=c.cardno
            AND c.personnum=1 LEFT JOIN
            {$trans}.equity_live_balance AS n
            on m.card_no = n.memnum
            LEFT JOIN memDates AS d ON m.card_no=d.card_no
            WHERE cardno = ?");
        $r = $dbc->exec_statement($q,array($cn));
        $w = $dbc->fetch_row($r);

        echo "<form action=equity.php method=post>";
        foreach($cns as $c)
            echo "<input type=hidden name=cardno[] value=$c />";
        echo "<input type=hidden name=curcard value=$cur />";
        echo "<b>Email Address</b>: <input type=text size=45 name=email value=\"$w[3]\" /><br /><br />";
        echo "<b>Message Preview</b>:<br />";
        echo "<textarea rows=20 cols=60 name=msg>";
        $bal = sprintf("%.2f",100-$w[4]);
        $fn = strtoupper($w[1][0]).strtolower(substr($w[1],1));
        echo "Whole Foods Co-op
610 East Fourth Street
Duluth, MN 55805

Dear $fn,

This is a reminder regarding the balance of your required equity. From the date of joining WFC, you have two years to complete the purchase of the required $80.00 of Class B equity stock. Our records indicate that the \$$bal balance of Class B equity stock is due on $w[5]. If your receipts differ, please advise me immediately.

We hope you will choose to continue your membership. However, if we do not receive your payment by the due date, your membership will become inactive and you will not be eligible for Member-Owner discounts and benefits or to participate in the governance of WFC.

Member-Owners with restricted income may apply to the Fran Skinner Memorial Matching Fund for assistance with the purchase of Class B stock. Information on the Matching Fund is available on our web site (http://wholefoods.coop) and in the store.

If you have any questions, please do not hesitate to ask. I can be reached at the number above or at mms@wholefoods.coop 

Sincerely yours, 
WHOLE FOODS COMMUNITY CO-OP, INC.

Amanda Borgren 
Member Services";
        echo "</textarea>";
        echo "<br />";
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
    echo "<form action=equity.php method=post>";
    $query = $dbc->prepare_statement("select m.card_no,c.lastname,i.email_1,
        datediff(mm,getdate(),m.end_date) as months_left from
        memDates as m left join custdata as c
        on m.card_no=c.cardno and c.personnum=1
        left join {$trans}.equity_live_balance as n on
        m.card_no = n.memnum left join meminfo as i
        on i.card_no=m.card_no
        where ".$dbc->monthdiff($dbc->now(),'m.end_date')." BETWEEN 0 AND 2
        and c.type NOT IN ('REG','TERM','INACT2') and n.payments < 100 
        order by ".$dbc->monthdiff($dbc->now(),'m.end_date')." DESC, m.card_no");
    $result = $dbc->exec_statement($query);
    echo "<table cellspacing=0 cellpadding=4 border=1>
        <tr><th>&nbsp;</th><th>Member</th><th>E-mail</th><th>Months Remaining</th></tr>";
    while($row = $dbc->fetch_row($result)){
        printf("<tr><td>%s</td>
            <td>%d - %s</td><td>%s</td><td align=center>%d</td></tr>",
            (empty($row[2])?'&nbsp;':"<input type=checkbox name=cardno[] value=$row[0] />"),
            $row[0],$row[1],$row[2],$row[3]);
    }
    echo "</table>";
    echo "<input type=submit value=\"Preview Emails\" />";
    echo "</form>";
}

include('../../src/footer.html');
?>
