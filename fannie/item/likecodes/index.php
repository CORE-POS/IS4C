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
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'auth/login.php');

if (!validateUserQuiet('manage_likecodes')){
	$rd = $FANNIE_URL.'item/likecodes/';
	$url = $FANNIE_URL.'auth/ui/loginform.php';
	header("Location: $url?redirect=$rd");
	return;
}

$msgs = "";
if (isset($_REQUEST['submit'])){
	$lc = $_REQUEST['newlc'];
	$name = $dbc->escape($_REQUEST['newlcname']);

	if (!is_numeric($lc))
		$msgs .= "Error: $lc is not a number<br />";
	else {
		$chk = $dbc->query("SELECT * FROM likeCodes WHERE likeCode=$lc");
		if ($dbc->num_rows($chk) > 0){
			$dbc->query("UPDATE likeCodes SET
					likeCodeDesc='$name'
					WHERE likeCode=$lc");
			$msgs .= "LC #$lc renamed \"$name\"<br />";
		}
		else {
			$dbc->query("INSERT INTO likeCodes VALUES ($lc,'$name')");
			$msgs .= "LC #$lc ($name) created<br />";
		}
	}
}
elseif (isset($_REQUEST['submit2'])){
	$lc = $_REQUEST['lcselect'];

	$q1 = "DELETE FROM likeCodes WHERE likeCode=$lc";
	$q2 = "DELETE FROM upcLike WHERE likeCode=$lc";
	$dbc->query($q1);
	$dbc->query($q2);
	
	$msgs .= "LC #$lc has been deleted<br />";
}

$opts = "";
$res = $dbc->query("SELECT likeCode,likeCodeDesc FROM likeCodes ORDER BY likeCode");
while($row = $dbc->fetch_row($res))
	$opts .= "<option value=$row[0]>$row[0] $row[1]</option>";

$page_title = "Fannie : Like Codes";
$header = "Like Codes";
include($FANNIE_ROOT.'src/header.html');
?>
<script src="<?php echo $FANNIE_URL; ?>src/jquery-1.2.6.min.js"
	type="text/javascript"></script>
<script type="text/javascript">
function loadlc(id){
	$.ajax({
		url: 'ajax.php',
		type: 'POST',
		dataType: 'text/html',
		timeout: 1000,
		data: 'lc='+id+'&action=fetch',
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			$('#rightdiv').html(resp);
		}
	});
}
</script>
<?php
if (!empty($msgs)){
	echo "<blockquote><i>$msgs</i></blockquote>";
}
?>
<form action=index.php method=post>
<div style="width: 100%;">
	<div id="leftdiv" style="float: left;">
	<select id="lcselect" name="lcselect" size=15 onchange="loadlc(this.value);">
	<?php echo $opts; ?>
	</select><p />
	<b>#</b>: <input type=text size=2 name=newlc value="" />
	<b>Name</b>: <input type=text size=6 name=newlcname value="" />
	<input type=submit name=submit value="Add/Rename LC" /><p />
	<input type=submit name=submit2 
		onclick="return confirm('Are you sure you want to delete LC #'+$('#lcselect').val()+'?');"
		value="Delete Selected LC" />
	</div>
	<div id="rightdiv" style="float: left; margin-left: 10px; font-size:90%;">
	</div>
</div>
<div style="clear:left;"></div>
</form>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>
