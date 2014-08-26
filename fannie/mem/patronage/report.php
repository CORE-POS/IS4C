<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

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
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);

$page_title = "Fannie :: Patronage Tools";
$header = "Working Copy Report";

ob_start();

if (!isset($_REQUEST['excel'])){
    include($FANNIE_ROOT.'src/header.html');
    echo '<a href="index.php">Patronage Menu</a>';
    echo '<br /><br />';
    echo '<a href="report.php?excel=yes">Download Report</a>';
    echo '<br /><br />';
}
else {
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="patronage-draft.csv"');
}

$q = $dbc->prepare_statement("SELECT p.cardno,c.LastName,c.FirstName,c.Type,
    CASE WHEN c.Type IN ('REG','PC') then a.memDesc
    ELSE b.memDesc END as memDesc,
    p.purchase,p.discounts,p.rewards,p.net_purch,
    CASE WHEN s.reasoncode IS NULL then 'No'
        WHEN s.reasoncode & 16 <> 0 then 'Yes'
        ELSE 'No' END as badAddress
    FROM patronage_workingcopy AS p LEFT JOIN
    custdata AS c ON p.cardno=c.CardNo AND c.personNum=1
    LEFT JOIN suspensions AS s ON p.cardno=s.cardno
    LEFT JOIN memtype AS a ON c.memType=a.memtype
    LEFT JOIN memtype AS b ON s.memtype1=b.memtype
    ORDER BY p.cardno");
$r = $dbc->exec_statement($q);
echo '<table cellpadding="4" cellspacing="0" border="1">';
echo '<tr><th>#</th><th>Last</th><th>First</th><th>Status</th>
<th>Type</th><th>Gross</th><th>Discounts</th><th>Rewards</th>
<th>Net</th><th>Bad Address</tr>';
while($w = $dbc->fetch_row($r)){
    printf('<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td>
        <td>%s</td><td>%.2f</td><td>%.2f</td><td>%.2f</td>
        <td>%.2f</td><td>%s</td></tr>',$w['cardno'],$w['LastName'],
        $w['FirstName'],$w['Type'],$w['memDesc'],
        $w['purchase'],-1*$w['discounts'],-1*$w['rewards'],
        $w['net_purch'],$w['badAddress']
    );
}
echo '</table>';

if (!isset($_REQUEST['excel']))
    include($FANNIE_ROOT.'src/footer.html');

$output = ob_get_contents();
ob_end_clean();

if (!isset($_REQUEST['excel'])){
    echo $output;
}
else {
    include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
    //include($FANNIE_ROOT.'src/ReportConvert/ArrayToXls.php');
    include($FANNIE_ROOT.'src/ReportConvert/ArrayToCsv.php');
    $array = HtmlToArray($output);
    //$xls = ArrayToXls($array);
    $xls = ArrayToCsv($array);
    echo $xls;
}
?>
