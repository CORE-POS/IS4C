<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include('../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

if (isset($_REQUEST['saveDesc'])){
	$q = sprintf("UPDATE batchType
		SET typeDesc=%s WHERE batchTypeID=%d",
		$dbc->escape($_REQUEST['saveDesc']),
		$_REQUEST['bid']);
	$r = $dbc->query($q);
	echo "Desc saved";
	exit; // ajax call
}
if (isset($_REQUEST['saveType'])){
	$q = sprintf("UPDATE batchType
		SET discType=%d WHERE batchTypeID=%d",
		$_REQUEST['saveType'],
		$_REQUEST['bid']);
	$r = $dbc->query($q);
	echo "Desc saved";
	exit; // ajax call
}

if (isset($_REQUEST['addtype'])){
	$q = "SELECT MAX(batchTypeID) FROM batchType";
	$r = $dbc->query($q);
	$id = array_pop($dbc->fetch_row($r));
	$id = (empty($id)) ? 1 : $id + 1;

	$ins = "INSERT INTO batchType (batchTypeID,typeDesc,discType)
		VALUES ($id,'New Type',1)";
	$dbc->query($ins);
}
else if (isset($_REQUEST['deltype'])){
	$q = sprintf("DELETE FROM batchType WHERE batchTypeID=%d",$_REQUEST['bid']);
	$dbc->query($q);
}

$price_methods = array(
	0 => "None (Change regular price)",
	1 => "Sale for Everyone",
	2 => "Sale for Members"
);

$page_title = 'Fannie - Batch Module';
$header = 'Sales Batches';
include('../src/header.html');
?>
<script type="text/javascript">
function saveDesc(val,bid){
	$.ajax({
		url: 'types.php',
		cache: false,
		type: 'post',
		data: 'saveDesc='+val+'&bid='+bid,
		success: function(data){
		}
	});
}
function saveType(val,bid){
	$.ajax({
		url: 'types.php',
		cache: false,
		type: 'post',
		data: 'saveType='+val+'&bid='+bid,
		success: function(data){
		}
	});
}
</script>
<?php
$q = "SELECT batchTypeID,typeDesc,discType FROM batchType ORDER BY batchTypeID";
$r = $dbc->query($q);

echo '<table cellspacing="0" cellpadding="4" border="1">';
echo '<tr><th>ID#</th><th>Description</th><th>Discount Type</th><th>&nbsp;</td></tr>';
while($w = $dbc->fetch_row($r)){
	printf('<tr><td>%d</td>
		<td><input type="text" onchange="saveDesc(this.value,%d)" value="%s" /></td>
		<td><select onchange="saveType($(this).val(),%d);">',
		$w['batchTypeID'],$w['batchTypeID'],$w['typeDesc'],$w['batchTypeID']);
	$found = False;
	foreach($price_methods as $id=>$desc){
		if ($id == $w['discType']){
			$found = True;
			printf('<option value="%d" selected>%d %s</option>',$id,$id,$desc);
		}
		else
			printf('<option value="%d">%d %s</option>',$id,$id,$desc);
	}
	if (!$found)
		printf('<option value="%d" selected>%d (Custom)</option>',$w['discType'],$w['discType']);
	echo '</select></td>';
	printf('<td><a href="types.php?deltype=yes&bid=%d"
			onclick="return confirm(\'Are you sure?\');">Delete</a>
		</td></tr>',$w['batchTypeID']);
}
echo '</table>';

echo '<br /><a href="types.php?addtype=yes">Create New Type</a>';

include('../src/footer.html');
?>
