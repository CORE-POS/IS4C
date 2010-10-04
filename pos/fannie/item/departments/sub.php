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

include('../../config.php');
$page_title = "Fannie : Manage Subdepartments";
$header = "Manage Subdepartments";
include($FANNIE_ROOT.'src/header.html');
include($FANNIE_ROOT.'src/mysql_connect.php');
include('ajax.php');

$superQ = "SELECT d.dept_no,dept_name FROM departments as d
	ORDER BY d.dept_no";
$superR = $dbc->query($superQ);
$opts = "";
$firstID = False;
$firstName = "";
while($superW = $dbc->fetch_row($superR)){
	$opts .= "<option value=$superW[0]>$superW[0] $superW[1]</option>";
	if ($firstID === False){
		$firstID = $superW[0];
		$firstName = $superW[1];
	}
}
?>
<script src="<?php echo $FANNIE_URL; ?>src/jquery-1.2.6.min.js"
	type="text/javascript"></script>
<script src="sub.js" type="text/javascript"></script>
Choose a department: <select id=deptselect onchange="showSubsForDept(this.value);">
<?php echo $opts ?>
</select>
<hr />
<div>
<div style="float:left; display:none; padding-right:10px; border-right:solid 1px #999999;" id="subdiv">
<span id=subname></span><br />
<select id=subselect size=12 style="min-width:100px;" multiple></select>
</div>
<div style="float:left; margin-left:10px; display:none;" id="formdiv">
<span>Add/Remove</span><br />
<input type=text size=7 id=newname /> 
<input type=submit value=Add onclick="addSub(); return false;" />
<p />
<input type=submit value="Delete Selected" onclick="deleteSub(); return false;" />
</div>
</div>
<script type="text/javascript">
showSubsForDept(<?php echo $firstID; ?>);
</script>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>
