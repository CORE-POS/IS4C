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

include('../../config.php');
$page_title = "Fannie : Manage Vendors";
$header = "Manage Vendors";
include($FANNIE_ROOT.'src/header.html');
include($FANNIE_ROOT.'src/mysql_connect.php');
include('ajax.php');

if (!isset($_REQUEST['vid'])){
	echo "<i>Error: no vendor selected</i>";
	include($FANNIE_ROOT.'src/footer.html');
	return;	
}
$vid = $_REQUEST['vid'];

?>
<script src="<?php echo $FANNIE_URL; ?>src/jquery-1.2.6.min.js"
	type="text/javascript"></script>
<script src="vdepts.js" type="text/javascript"></script>
<div id="newdeptdiv">
<a href="" onclick="$('#newform').show(); return false;">New vendor department</a>
<div id="newform" style="display:none;">
<p />
<b>No.</b> <input type=text size=4 id=newno />
&nbsp;&nbsp;&nbsp;
<b>Name</b> <input type=text id=newname />
<p />
<input onclick="newdept();" type=submit value="Add department" />
&nbsp;&nbsp;&nbsp;
<a href="" onclick="$('#newform').hide(); return false;">Cancel</a>
</div>
</div>
<hr />
<div id="contentarea">
<?php vendorDeptDisplay($vid); ?>
</div>
<?php
echo "<input type=hidden id=vendorID value=$vid />";
echo "<input type=hidden id=urlpath value=\"$FANNIE_URL\" />";
include($FANNIE_ROOT.'src/footer.html');
?>
