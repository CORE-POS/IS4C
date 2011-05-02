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
		$dbc->escape($_REQUEST['saveType']),
		$_REQUEST['t_id']);
	$r = $dbc->query($q);
	exit;
}
elseif (isset($_REQUEST['newMemForm'])){
	$q = "SELECT MAX(memtype) FROM memtype";
	$r = $dbc->query($q);
	$sug = 0;
	if($dbc->num_rows($r)>0){
		$w = $dbc->fetch_row($r);
		if(!empty($w)) $sug = $w[0]+1;
	}
	echo "Give the new memtype an ID number. The one
		provided is only a suggestion. ID numbers
		must be unique.";
	printf('<br /><br /><b>New ID</b>: <input size="4" value="%d"
		id="newTypeID" />',$sug);
	echo ' <input type="submit" value="Create New Type"
		onclick="finishMemType();return false;" />';
	echo ' <input type="submit" value="Cancel"
		onclick="cancelMemType();return false;" />';
	exit;
}
elseif (isset($_REQUEST['new_t_id'])){
	/* do some extra sanity checks
	   on a new member type
	*/
	$id = $_REQUEST['new_t_id'];
	if (!is_numeric($id)){
		echo 'ID '.$id.' is not a number';
		echo '<br /><br />';
		echo '<a href="" onclick="newMemType();return false;">Try Again</a>';
	}
	else {
		$q = sprintf("SELECT memtype FROM memtype WHERE
			memtype=%d",$id);
		$r = $dbc->query($q);
		if ($dbc->num_rows($r) > 0){
			echo 'ID is already in use';
			echo '<br /><br />';
			echo '<a href="" onclick="newMemType();return false;">Try Again</a>';
		}
		else {
			$mt = array(
				'memtype'=>$id,
				'memDesc'=>"''"
			);
			
			$md = array(
				'memtype'=>$id,
				'cd_type'=>"'REG'",
				'discount'=>0,
				'staff'=>0,
				'SSI'=>0			
			);	

			$dbc->smart_insert('memtype',$mt);
			$dbc->smart_insert('memdefaults',$md);

			echo getTypeTable();
		}
	}
	exit;
}
elseif(isset($_REQUEST['goHome'])){
	echo getTypeTable();
	exit;
}
/* end ajax callbacks */

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
	$ret .= '<br /><a href="" onclick="newMemType();return false;">New Member Type</a>';
	return $ret;
}

include($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('editmembers')){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}mem/types.php");
	exit;
}

$page_title = "Fannie :: Member Types";
$header = "Member Types";
include($FANNIE_ROOT.'src/header.html');
?>
<script type="text/javascript">
function newMemType(){
	$.ajax({url:'types.php',
		cache: false,
		type: 'post',
		data: 'newMemForm=yes',
		success: function(data){
			$('#mainDisplay').html(data);
		}
	});
}

function finishMemType(){
	var t_id = $('#newTypeID').val();
	$.ajax({url:'types.php',
		cache: false,
		type: 'post',
		data: 'new_t_id='+t_id,
		success: function(data){
			$('#mainDisplay').html(data);
		}
	});
}

function cancelMemType(){
	$.ajax({url:'types.php',
		cache: false,
		type: 'post',
		data: 'goHome=yes',
		success: function(data){
			$('#mainDisplay').html(data);
		}
	});
}

function saveMem(st,t_id){
	var cd_type = 'REG';
	if (st == true) cd_type='PC';
	$.ajax({url:'types.php',
		cache: false,
		type: 'post',
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
		type: 'post',
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
		type: 'post',
		data: 't_id='+t_id+'&saveSSI='+ssi,
		success: function(data){

		}
	});
}

function saveDisc(disc,t_id){
	$.ajax({url:'types.php',
		cache: false,
		type: 'post',
		data: 't_id='+t_id+'&saveDisc='+disc,
		success: function(data){

		}
	});
}

function saveType(typedesc,t_id){
	$.ajax({url:'types.php',
		cache: false,
		type: 'post',
		data: 't_id='+t_id+'&saveType='+typedesc,
		success: function(data){

		}
	});
}
</script>
<?php
echo '<div id="mainDisplay">';
echo getTypeTable();
echo '</div>';

include($FANNIE_ROOT.'src/footer.html');

?>
