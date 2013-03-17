<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_REQUEST['submit'])){
	$d1 = $_REQUEST['date1'];
	$d2 = $_REQUEST['date2'];

	$dlog = select_dlog($d1,$d2);

	if (isset($_REQUEST['excel'])){
		header("Content-Disposition: inline; filename=discounts{$d1}_{$d2}.xls");
		header("Content-type: application/vnd.ms-excel; name='excel'");
	}
	else{
		printf("<a href=index.php?date1=%s&date2=%s&submit=yes&excel=yes>Save to Excel</a>",
			$d1,$d2);
	}

	$placeholder = isset($_REQUEST['excel'])?'':'&nbsp;';

	$query = $dbc->prepare_statement("SELECT m.memDesc,sum(total) as total FROM $dlog AS d
		LEFT JOIN custdata AS c ON d.card_no=c.CardNo
		AND c.personNum=1 LEFT JOIN memtype AS m ON
		c.memType=m.memtype
		WHERE d.upc='DISCOUNT'
		GROUP BY m.memDesc
		ORDER BY m.memDesc");
	$result = $dbc->exec_statement($query);

	echo '<table cellspacing="0" cellpadding="4" border="1">';
	echo '<tr><th>Type</th><th>Total</th>';
	while($row = $dbc->fetch_row($result)){
		printf('<tr><td>%s</td><td>%.2f</td></tr>',
			$row['memDesc'],$row['total']);
	}
	echo '</table>';

			
}
else {

$page_title = "Fannie : Discount Report";
$header = "Discount Report";
include($FANNIE_ROOT.'src/header.html');
$lastMonday = "";
$lastSunday = "";

$ts = mktime(0,0,0,date("n"),date("j")-1,date("Y"));
while($lastMonday == "" || $lastSunday == ""){
	if (date("w",$ts) == 1 && $lastSunday != "")
		$lastMonday = date("Y-m-d",$ts);
	elseif(date("w",$ts) == 0)
		$lastSunday = date("Y-m-d",$ts);
	$ts = mktime(0,0,0,date("n",$ts),date("j",$ts)-1,date("Y",$ts));	
}
?>
<script type="text/javascript"
	src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js">
</script>
<form action=index.php method=get>
<table cellspacing=4 cellpadding=4>
<tr>
<th>Start Date</th>
<td><input type=text name=date1 onclick="showCalendarControl(this);" value="<?php echo $lastMonday; ?>" /></td>
</tr><tr>
<th>End Date</th>
<td><input type=text name=date2 onclick="showCalendarControl(this);" value="<?php echo $lastSunday; ?>" /></td>
</tr><tr>
<td>Excel <input type=checkbox name=excel /></td>
<td><input type=submit name=submit value="Submit" /></td>
</tr>
</table>
</form>
<?php
include($FANNIE_ROOT.'src/footer.html');
}
?>
