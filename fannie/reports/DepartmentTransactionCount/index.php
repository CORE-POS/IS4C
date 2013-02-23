<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

$deptsQ = "select dept_no,dept_name from departments order by dept_no";
$deptsR = $dbc->query($deptsQ);
$deptsList = "";

$deptSubQ = "SELECT superID,super_name FROM superDeptNames
		WHERE superID <> 0 
		ORDER BY superID";
$deptSubR = $dbc->query($deptSubQ);

$deptSubList = "";
while($deptSubW = $dbc->fetch_array($deptSubR)){
  $deptSubList .=" <option value=$deptSubW[0]>$deptSubW[1]</option>";
}
while ($deptsW = $dbc->fetch_array($deptsR))
  $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";

$page_title = "Fannie : Department Transactions Report";
$header = "Department Transactions";
include($FANNIE_ROOT.'src/header.html');
?>
<script type="text/javascript">
function swap(src,dst){
	var val = document.getElementById(src).value;
	document.getElementById(dst).value = val;
}
</script>
<script src="../../src/CalendarControl.js"
        language="javascript"></script>
<div id=main>	
<form method = "get" action="report.php">
	<table border="0" cellspacing="0" cellpadding="5">
		<tr>
			<td><b>Select Buyer/Dept</b></td>
			<td><select id=buyer name=buyer>
			   <option value=0 >
			   <?php echo $deptSubList; ?>
			   <option value=-2 >All Retail</option>
			   <option value=-1 >All</option>
			   </select>
 			</td>
			<td><b>Send to Excel</b></td>
			<td><input type=checkbox name=excel id=excel value=1></td>
		</tr>
		<tr>
			<td colspan=5><i>Selecting a Buyer/Dept overrides Department Start/Department End, but not Date Start/End.
			To run reports for a specific department(s) leave Buyer/Dept or set it to 'blank'</i></td>
		</tr>
		<tr> 
			<td> <p><b>Department Start</b></p>
			<p><b>End</b></p></td>
			<td> <p>
 			<select id=deptStartSel onchange="swap('deptStartSel','deptStart');">
			<?php echo $deptsList ?>
			</select>
			<input type=text name=deptStart id=deptStart size=5 value=1 />
			</p>
			<p>
			<select id=deptEndSel onchange="swap('deptEndSel','deptEnd');">
			<?php echo $deptsList ?>
			</select>
			<input type=text name=deptEnd id=deptEnd size=5 value=1 />
			</p></td>

			 <td>
			<p><b>Date Start</b> </p>
		         <p><b>End</b></p>
		       </td>
		            <td>
		             <p>
		               <input type=text size=25 name=date1 onfocus="this.value='';showCalendarControl(this);">
		               </p>
		               <p>
		                <input type=text size=25 name=date2 onfocus="this.value='';showCalendarControl(this);">
		         </p>
		       </td>

		</tr>
		<!--<tr>
			<td> Select Dept/Buyer </td>
			<td colspan=3>
				<table width=100%><tr>
					<td><input type=radio name=buyer value=1>Bulk</td>
				       	<td><input type=radio name=buyer value=3>Cool</td>
				      	<td><input type=radio name=buyer value=4>Deli</td>
				      	<td><input type=radio name=buyer value=4>Grocery</td>
				      	<td><input type=radio name=buyer value=5>HBC</td></tr>
				      	<tr><td><input type=radio name=buyer value=6>Produce</td>
				      	<td><input type=radio name=buyer value=7>Marketing</td>
				      	<td><input type=radio name=buyer value=8>Meat</td>
				      	<td><input type=radio name=buyer value=9>Gen Merch</td>
				</tr></table>
			</td>
		</tr>-->
		<tr> 
			<td colspan=2></td>
			<td colspan=2>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)<!-- Output to CSV?</td>
		            <td><input type="checkbox" name="csv" value="yes">
			                        yes --> </td>
				</tr>
		<tr> 
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>

<?php
include($FANNIE_ROOT.'src/footer.html');
?>
