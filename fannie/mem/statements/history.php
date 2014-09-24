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
$header='Statement History';
include('../../src/header.html');

$month = date('n');
$year = date('Y');
if (isset($_REQUEST['month'])) $month = $_REQUEST['month'];
if (isset($_REQUEST['year'])) $year = $_REQUEST['year'];

echo "<form action=history.php name=myform method=get>";
echo "<select name=month onchange=document.myform.submit()>";
for($i=1;$i<12;$i++){
    if ($i==$month)
        echo "<option selected value=$i>".date('F',mktime(0,0,0,$i,1,2000))."</option>";
    else
        echo "<option value=$i>".date('F',mktime(0,0,0,$i,1,2000))."</option>";
}
echo "</select>&nbsp;&nbsp;&nbsp;";
echo "<select name=year onchange=document.myform.submit()>";
for($i=2010;$i<=date('Y');$i++)
    echo "<option>$i</option>";
echo "</select>";
echo "</form>";
echo "<hr />";
echo "<table cellspacing=0 cellpadding=4 border=1>
    <tr><th>Date</th><th>Mem #</th><th>E-mail</th><th>Type</th></tr>";
$q = $dbc->prepare_statement("SELECT * FROM emailLog WHERE month(tdate)=? AND year(tdate)=? ORDER BY tdate");
$r = $dbc->exec_statement($q,array($month,$year));
while($w = $dbc->fetch_row($r)){
    printf("<tr><td>%s</td><td>%d</td><td>%s</td><td>%s</td></tr>",
        array_shift(explode(" ",$w[0])),$w[1],$w[2],$w[3]);
}
echo "</table>";

include('../../src/footer.html');
?>
