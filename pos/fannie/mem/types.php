<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

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

/* ajax callbacks to save changes */
if (isset($_REQUEST['saveMem'])){
	$q = sprintf("UPDATE memdefaults SET cd_type=%s
		WHERE memtype=%d",
		$dbc->escape($_REQUEST['saveMem']),
		$_REQUEST['t_id']);
	$r = $dbc->query($q);
	exit;
}
elseif (isset($_REQUEST['saveStaff'])){
	$q = sprintf("UPDATE memdefaults SET staff=%d
		WHERE memtype=%d",
		$_REQUEST['saveStaff'],
		$_REQUEST['t_id']);
	$r = $dbc->query($q);
	exit;
}
elseif (isset($_REQUEST['saveSSI'])){
	$q = sprintf("UPDATE memdefaults SET SSI=%d
		WHERE memtype=%d",
		$_REQUEST['saveSSI'],
		$_REQUEST['t_id']);
	$r = $dbc->query($q);
	exit;
}
elseif (isset($_REQUEST['saveDisc'])){
	$q = sprintf("UPDATE memdefaults SET discount=%d
		WHERE memtype=%d",
		$_REQUEST['saveDisc'],
		$_REQUEST['t_id']);
	$r = $dbc->query($q);
	exit;
}
elseif (isset($_REQUEST['saveType'])){
	$q = sprintf("UPDATE memtype SET memDesc=%s
		WHERE memtype=%d",
		$dbc->escape($_REQUEST['saveDisc']),
		$_REQUEST['t_id']);
	$r = $dbc->query($q);
	exit;
}

function getTypeTable(){
	global $dbc;

	$ret = '<table cellspacing="0" cellpadding="4" border="1">
		<tr><th>ID#</th><th>Description</th>
		<th>Member</th><th>Discount</th>
		<th>Staff</th><th>SSI</th>
		</tr>';

	$q = "SELECT m.memtype,m.memDesc,d.cd_type,d.discount,d.staff,d.SSI
		FROM memtype AS m LEFT JOIN memdefaults AS d
		ON m.memtype=d.memtype
		ORDER BY m.memtype";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r)){
		$ret .= sprintf('<tr><td>%d</td>
				<td><input value="%s" onchange="saveType(this.value,%d);" /></td>
				<td><input type="checkbox" %s onclick="saveMem(this.checked,%d);" /></td>
				<td><input value="%d" size="4" onchange="saveDisc(this.value,%d);" /></td>
				<td><input type="checkbox" %s onclick="saveStaff(this.checked,%d);" /></td>
				<td><input type="checkbox" %s onclick="saveSSI(this.checked,%d);" /></td>
				</tr>',$w['memtype'],
				$w['memDesc'],$w['memtype'],
				($w['cd_type']=='PC'?'checked':''),$w['memtype'],
				$w['discount'],$w['memtype'],
				($w['staff']=='1'?'checked':''),$w['memtype'],
				($w['SSI']=='1'?'checked':''),$w['memtype']
			);
	}
	$ret .= "</table>";
	return $ret;
}

$page_title = "Fannie :: Member Types";
$header = "Member Types";
include($FANNIE_ROOT.'src/header.html');
?>
<script type="text/javascript">
function saveMem(st,t_id){
	var cd_type = 'REG';
	if (st == true) cd_type='PC';
	$.ajax({url:'types.php',
		cache: false,
		dataType: 'post',
		data: 't_id='+t_id+'&saveMem='+cd_type,
		success: function(data){

		}
	});
}

function saveStaff(st,t_id){
	var staff = 0;
	if (st == true) staff=1;
	$.ajax({url:'types.php',
		cache: false,
		dataType: 'post',
		data: 't_id='+t_id+'&saveStaff='+staff,
		success: function(data){

		}
	});
}

function saveSSI(st,t_id){
	var ssi = 0;
	if (st == true) ssi=1;
	$.ajax({url:'types.php',
		cache: false,
		dataType: 'post',
		data: 't_id='+t_id+'&saveSSI='+ssi,
		success: function(data){

		}
	});
}

function saveDisc(disc,t_id){
	$.ajax({url:'types.php',
		cache: false,
		dataType: 'post',
		data: 't_id='+t_id+'&saveDisc='+disc,
		success: function(data){

		}
	});
}

function saveType(typedesc,t_id){
	$.ajax({url:'types.php',
		cache: false,
		dataType: 'post',
		data: 't_id='+t_id+'&saveType='+typedesc,
		success: function(data){

		}
	});
}
</script>
<?php
echo getTypeTable();

include($FANNIE_ROOT.'src/footer.html');

?>
