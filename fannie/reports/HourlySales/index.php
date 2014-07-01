<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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
header('Location: HourlySalesReport.php');
return;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
$dbc = FannieDB::get($FANNIE_OP_DB);

$header = "Hourly Sales Report";
$page_title = "Fannie : Hourly Sales";
include($FANNIE_ROOT.'src/header.html');
$options = "<option value=-1 selected>All</option>";
$prep = $dbc->prepare_statement("SELECT superID,super_name FROM superDeptNames 
        WHERE superID > 0");
$res = $dbc->exec_statement($prep);
while($row = $dbc->fetch_row($res))
    $options .= sprintf("<option value=%d>%s</option>",$row[0],$row[1]);
?>

<form name='addBatch' action = 'HourlySalesReport.php' method='get'>
<table><tr><td>Super Department</td><td>Start Date</td><td>End Date</td></tr>
<tr><td><select name=buyer>
    <?php echo $options; ?>
      </select></td>
     <td><input name="date1" type="text"></td>
     <td><input name="date2" type="text"></td>
</tr><tr>
     <td><input type=checkbox name=weekday value=1>Group by weekday?</td>
     <td><input type =submit name=submit value ="Get Report"></td></tr>
</tr><tr>
<td colspan="4">
<a href="HourlySalesReport.php">New One with Charts</a>
</td></tr>
</table>
<!-- <a href=hourlySalesDept.php>Per-department sales by hour</a> -->

</body>
</html>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>
