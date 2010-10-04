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

require('../../config.php');
require($FANNIE_ROOT.'auth/login.php');
require($FANNIE_ROOT.'src/mysql_connect.php');
require('scan_layouts.php');

$layouts = scan_layouts();

if (!validateUserQuiet('barcodes')){
	header("Location: ../../auth/ui/loginform.php?redirect={$FANNIE_URL}admin/labels/");
	return;
}

$page_title = 'Fannie - Shelf Tags';
$header = 'Shelf Tags';
include($FANNIE_ROOT.'src/header.html');
?>
<script type="text/javascript">
function goToPage(the_id){
	var offset = document.getElementById('offset').value;
	var str = "0";
	if (!isNaN(parseInt(offset)))
		str = parseInt(offset);

	var url = 'genLabels.php?id='+the_id;
	url += '&offset='+offset;

	var sel = document.getElementById('layoutselector');
	var pdf = sel.options[sel.selectedIndex].text;
	url += '&layout='+pdf;

	window.top.location = url;
}
</script>
Regular shelf tags
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="batchtags.php">Batch shelf tags</a>
<p />
<table cellspacing=0 cellpadding=4 border=1>
<tr><td>
Offset: <input type=text size=2 id=offset value=0 />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<select id=layoutselector>
<?php
foreach($layouts as $l){
	if ($l == $FANNIE_DEFAULT_PDF)
		echo "<option selected>".$l."</option>";
	else
		echo "<option>".$l."</option>";
}
?>
</select>
</td></tr>
</table>
<p />
<table cellspacing=0 cellpadding=4 border=1>
<?php
$query = "SELECT superID,super_name FROM superDeptNames
	GROUP BY superID,super_name
	ORDER BY superID";
$result = $dbc->query($query);
while($row = $dbc->fetch_row($result)){
	printf("<tr><td>%s barcodes</td><td><a href=\"\" onclick=\"goToPage('%d');return false;\">
		Print</a></td><td><a href=\"dumpBarcodes.php?id=%d\">Clear</a></td>
		<td><a href=\"edit.php?id=%d\"><img src=\"{$FANNIE_URL}src/img/buttons/b_edit.png\"
		alt=\"Edit\" border=0 /></td></tr>",$row[1],$row[0],$row[0],$row[0]);
}
echo "</table>";

include($FANNIE_ROOT.'src/footer.html');
?>
