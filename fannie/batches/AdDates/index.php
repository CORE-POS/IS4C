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

/* configuration for your module - Important */
include("../../config.php");
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

/* html header, including navbar */
$page_title = "Fannie - Sale Dates";
$header = "Advertised Sale Dates";
include($FANNIE_ROOT.'src/header.html');

if (isset($_REQUEST['sn'])){
	if (!empty($_REQUEST['sn']) && !empty($_REQUEST['sd']) && !empty($_REQUEST['ed'])){
		$q = $dbc->prepare_statement("INSERT INTO AdSaleDates (sale_name,start_date,end_date)
			VALUES (?,?,?)");
		$r = $dbc->exec_statement($q,array($_REQUEST['sn'],$_REQUEST['sd'],$_REQUEST['ed']));
	}
}

printf('<script type="text/javascript" src="%s"></script>',
	$FANNIE_URL.'src/CalendarControl.js');

echo '<form action="index.php" method="post">';
echo '<input type="text" value="New Sale" onfocus="$(this).val(\'\');" name="sn" size="15" />';
echo '&nbsp;&nbsp;&nbsp;';
echo '<input type="text" value="Start" onfocus="showCalendarControl(this);" name="sd" size="10" />';
echo '&nbsp;&nbsp;&nbsp;';
echo '<input type="text" value="End" onfocus="showCalendarControl(this);" name="ed" size="10" />';
echo '&nbsp;&nbsp;&nbsp;';
echo '<input type="submit" value="Add Sale" />';
echo '</form>';

echo '<hr />';
$q = $dbc->prepare_statement("SELECT sale_name,start_date,end_date FROM AdSaleDates
	ORDER BY start_date,sale_name");
$r = $dbc->exec_statement($q);
echo '<table cellspacing="0" cellpadding="4" border="1">';
while($w = $dbc->fetch_row($r)){
	printf('<tr><th>%s</th><td>%s</td><td>%s</td></tr>',
		$w['sale_name'],
		(array_shift(explode(' ',$w['start_date']))),
		(array_shift(explode(' ',$w['end_date'])))
	);
}
echo '</table>';

/* html footer */
include($FANNIE_ROOT.'src/footer.html');
