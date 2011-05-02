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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
$page_title = 'Fannie - Product List';
$header = 'Product List';
include($FANNIE_ROOT.'src/header.html');

$deptQ = "select dept_no,dept_name from departments order by dept_no";
$deptR = $dbc->query($deptQ);
$dept_nos = array();
$dept_names = array();
$count = 0;
while ($deptW = $dbc->fetch_array($deptR)){
	$dept_nos[$count] = $deptW[0];
	$dept_names[$count] = $deptW[1];
	$count++;
}

$deptSubQ = "SELECT superID,super_name FROM superDeptNames WHERE 
	superID > 0 ORDER BY superID";
$deptSubR = $dbc->query($deptSubQ);
$deptSubList = "";

while($deptSubW = $dbc->fetch_array($deptSubR)){
  $deptSubList .="<option value=$deptSubW[0]>$deptSubW[0] $deptSubW[1]</option>";
}

?>

<script type="text/javascript">
function selectChange(selectid,targetid){
	document.getElementById(targetid).value = document.getElementById(selectid).value;
}
function init(){
	selectChange('deptStartSelect','deptStart');
	selectChange('deptEndSelect','deptEnd');
}

function doshow(which){
	if (which == 'dept'){
		document.getElementById('dept1').style.display = 'table-row';
		document.getElementById('dept2').style.display = 'table-row';
		document.getElementById('manu').style.display = 'none';
	}
	else {
		document.getElementById('manu').style.display = 'table-row';
		document.getElementById('dept1').style.display = 'none';
		document.getElementById('dept2').style.display = 'none';
	}
}
</script>
<style type=text/css>
.dept {
	display: table-row;
}
.class {
	display: none;
}
</style>
</head>

<div id=textwlogo> 
	<form method = "get" action="productListCallback.php">
	<b>Report by</b>:
	<input type=radio name=supertype value=dept checked onclick="doshow('dept');" /> Department
	<input type=radio name=supertype value=manu onclick="doshow('manu');" /> Manufacturer
	<table border="0" cellspacing="0" cellpadding="5">
		<!--<tr>
			<td bgcolor="#FFffcc"><a href="lisaCSV.html"><font color="#CC0000">Click 
here to create Excel Report</font></a></td>-->
			<!--<td><b>Send to Excel</b></td>
			<td><input type=checkbox name=excel value=1 id=excel></td>-->
			<!--<td>&nbsp;</td>
		</tr>-->
		<tr class=dept id=dept1>
			<td valign=top><p><b>Buyer</b></p></td>
			<td><p><select name=deptSub>
			<option value=0></option>
			<?php
			echo $deptSubList;	
			?>
			</select></p>
			<i>Selecting a Buyer/Dept overrides Department Start/Department End.
			To run reports for a specific department(s) leave Buyer/Dept or set it to 'blank'</i></td>

		</tr>
		<tr class=dept id=dept2> 
			<td> <p><b>Department Start</b></p>
			<p><b>End</b></p></td>
			<td> <p>
			<select id=deptStartSelect onchange="selectChange('deptStartSelect','deptStart');">
			<?php
			for ($i = 0; $i < $count; $i++)
				echo "<option value=$dept_nos[$i]>$dept_nos[$i] $dept_names[$i]</option>";
			?>
			</select>
			<input type=text size= 5 id=deptStart name=deptStart value=1>
			</p>
			<p>
			<select id=deptEndSelect onchange="selectChange('deptEndSelect','deptEnd');">
			<?php
			for ($i = 0; $i < $count; $i++)
				echo "<option value=$dept_nos[$i]>$dept_nos[$i] $dept_names[$i]</option>";
			?>
			</select>
			<input type=text size= 5 id=deptEnd name=deptEnd value=1>
			</p></td>
		</tr>
		<tr class=manu id=manu style="display:none;">
			<td><p><b>Manufacturer</b></p>
			<p></p></td>
			<td><p>
			<input type=text name=manufacturer />
			</p>
			<p>
			<input type=radio name=mtype value=prefix checked />UPC prefix
			<input type=radio name=mtype value=name />Manufacturer name
			</p></td>
		</tr>
		<tr> 
			<td><b>Sort report by?</b></td>
			<td> <select name="sort" size="1">
					<option value="dept_name">Department</option>
					<option value="i.upc">UPC</option>
					<option value="i.description">Description</option>
			</select> 
			<input type=checkbox name=excel /> <b>Excel</b></td>
			<td>&nbsp;</td>
		        <td>&nbsp; </td>
			</tr>
			<td>&nbsp;</td>
			<td>&nbsp; </td>
		</tr>
		<tr> 
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</table>
<br>
<br>
<br>
<br>
<br>
<br>
</form>
</div>

<?php
include($FANNIE_ROOT.'src/footer.html');
?>



