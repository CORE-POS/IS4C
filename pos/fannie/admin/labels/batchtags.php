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

include($FANNIE_ROOT.'src/mysql_connect.php');

/* html header, including navbar */
$page_title = "Fannie : Batch Barcodes";
$header = "Batch Barcodes";
include($FANNIE_ROOT."src/header.html");
include('scan_layouts.php');
$layouts = scan_layouts();
?>
<a href="index.php">Regular shelf tags</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Batch shelf tags
<p />
<?php

echo "<form action=genLabels.php method=get>";
//echo "<form action=barcodenew.php method=get>";
echo "<b>Select batch(es*) to be printed</b>:<br />";
$fetchQ = "select b.batchID,b.batchName
	  from batches as b left join
	  batchBarcodes as c on b.batchID = c.batchID
	  where c.upc is not null
		  group by b.batchID,b.batchName
		  order by b.batchID desc";
$fetchR = $dbc->query($fetchQ);
echo "<select name=batchID[] multiple style=\"{width:300px;}\" size=15>";
while($fetchW = $dbc->fetch_array($fetchR))
	echo "<option value=$fetchW[0]>$fetchW[1]</option>";
echo "</select><p />";
echo "<fieldset>";
echo "Offset: <input size=3 type=text name=offset value=0 />";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "<select name=layout>";
foreach($layouts as $l){
	if ($l == $FANNIE_DEFAULT_PDF)
		echo "<option selected>".$l."</option>";
	else
		echo "<option>".$l."</option>";
}	
echo "</select>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "<input type=submit value=Print />";
echo "</fieldset>";
echo "</form>";
echo "<a href={$FANNIE_URL}batches/newbatch/index.php>Back to batch list</a><p />";
echo "* Hold the apple key while clicking to select multiple batches ";
echo "(or the control key if you're not on a Mac)";

/* html footer */
include("../../src/footer.html");
?>
