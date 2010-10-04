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
$page_title = "Fannie : Manage Super Departments";
$header = "Manage Super Departments";
include($FANNIE_ROOT.'src/header.html');
include($FANNIE_ROOT.'src/mysql_connect.php');
include('ajax.php');

$superQ = "SELECT s.superID,super_name FROM superdepts as s
	LEFT JOIN superDeptNames AS n ON s.superID=n.superID
	GROUP BY s.superID,super_name
	ORDER BY super_name";
$superR = $dbc->query($superQ);
$opts = "";
$firstID = False;
$firstName = "";
while($superW = $dbc->fetch_row($superR)){
	$opts .= "<option value=$superW[0]>$superW[1]</option>";
	if ($firstID === False){
		$firstID = $superW[0];
		$firstName = $superW[1];
	}
}
?>
<script src="<?php echo $FANNIE_URL; ?>src/jquery-1.2.6.min.js"
	type="text/javascript"></script>
<script src="super.js" type="text/javascript"></script>
<div id="superdeptdiv">
Select super department: <select id="superselect" onchange="superSelected();">
<?php echo $opts; ?>
<option value=-1>Create a new super department</option>
</select><p />
<span id="namespan" style="display:none;">Name: 
<input type="text" id="newname" value="<?php echo $firstName; ?>" /></span>
</div>
<hr />
<div id="#deptdiv" style="display:block;">
<div style="float: left;">
Members<br />
<select id="deptselect" multiple size=15>
<?php deptsInSuper($firstID); ?>
</select>
</div>
<div style="float: left; margin-left: 20px; margin-top: 50px;">
<input type="submit" value="<<" onclick="addDepts(); return false;" />
<p />
<input type="submit" value=">>" onclick="remDepts(); return false;" />
</div>
<div style="margin-left: 20px; float: left;">
Non-members<br />
<select id="deptselect2" multiple size=15>
<?php deptsNotInSuper($firstID); ?>
</select>
</div>
<div style="clear:left;"></div>
<br />
<input type="submit" value="Save" onclick="saveData(); return false;" />
</div>

<?php
include($FANNIE_ROOT.'src/footer.html');
?>
