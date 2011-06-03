<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

if (isset($_REQUEST['saveCode'])){
	$code = $dbc->escape($_REQUEST['saveCode']);
	$id = sprintf("%d",$_REQUEST['id']);

	$chk = $dbc->query("SELECT TenderID FROM tenders WHERE
		TenderCode=$code AND TenderID<>$id");
	if ($dbc->num_rows($chk) > 0)
		echo "Error: Code $code is already in use";
	else{
		$dbc->query("UPDATE tenders SET TenderCode=$code
			WHERE TenderID=$id");
	}
}
elseif(isset($_REQUEST['saveName'])){
	$name = $dbc->escape($_REQUEST['saveName']);
	$id = sprintf("%d",$_REQUEST['id']);
	$dbc->query("UPDATE tenders SET TenderName=$name
		WHERE TenderID=$id");
}
elseif(isset($_REQUEST['saveType'])){
	$type = $dbc->escape($_REQUEST['saveType']);
	$id = sprintf("%d",$_REQUEST['id']);
	$dbc->query("UPDATE tenders SET TenderType=$type
		WHERE TenderID=$id");
}
elseif(isset($_REQUEST['saveCMsg'])){
	$msg = $dbc->escape($_REQUEST['saveCMsg']);
	$id = sprintf("%d",$_REQUEST['id']);
	$dbc->query("UPDATE tenders SET ChangeMessage=$msg
		WHERE TenderID=$id");
}
elseif(isset($_REQUEST['saveMin'])){
	$min = $_REQUEST['saveMin'];
	$id = sprintf("%d",$_REQUEST['id']);
	if (!is_numeric($min))
		echo "Error: Minimum must be a number";
	else {
		$dbc->query("UPDATE tenders SET MinAmount=$min
			WHERE TenderID=$id");
	}
}
elseif(isset($_REQUEST['saveMax'])){
	$max = $_REQUEST['saveMax'];
	$id = sprintf("%d",$_REQUEST['id']);
	if (!is_numeric($max))
		echo "Error: Maximum must be a number";
	else {
		$dbc->query("UPDATE tenders SET MaxAmount=$max
			WHERE TenderID=$id");
	}
}
elseif(isset($_REQUEST['saveRLimit'])){
	$limit = $_REQUEST['saveRLimit'];
	$id = sprintf("%d",$_REQUEST['id']);
	if (!is_numeric($limit))
		echo "Error: Refund limit must be a number";
	else {
		$dbc->query("UPDATE tenders SET MaxRefund=$limit
			WHERE TenderID=$id");
	}
}
elseif(isset($_REQUEST['newTender'])){
	$newID=1;
	$idR = $dbc->query("SELECT MAX(TenderID) FROM tenders");
	if ($dbc->num_rows($idR) > 0){
		$idW = $dbc->fetch_row($idR);
		if (!empty($idW[0])) $newID = $idW[0] + 1;
	}
	
	$vals = array(
		'TenderID'=>$newID,
		'TenderCode'=>"''",
		'TenderName'=>"'NEW TENDER'",
		'TenderType'=>"'CA'",
		'ChangeMessage'=>"''",
		'MinAmount'=>0,
		'MaxAmount'=>500,
		'MaxRefund'=>0
	);
	$dbc->smart_insert('tenders',$vals);

	echo "GOT TO HERE";
	echo getTenderTable();
}

function getTenderTable(){
	global $dbc;
	
	$ret = '<table cellpadding="4" cellspacing="0" border="1">
		<tr><th>Code</th><th>Name</th><th>Type</th>
		<th>Change Msg</th><th>Min</th><th>Max</th>
		<th>Refund Limit</th></tr>';

	$q = "SELECT TenderID,TenderCode,TenderName,TenderType,
		ChangeMessage,MinAmount,MaxAmount,MaxRefund
		FROM tenders ORDER BY TenderID";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r)){
		$ret .= sprintf('<tr>
			<td><input size="2" maxlength="2" value="%s"
				onchange="saveCode(this.value,%d);" /></td>
			<td><input size="10" maxlength="255" value="%s"
				onchange="saveName(this.value,%d);" /></td>
			<td><input size="2" maxlength="2" value="%s"
				onchange="saveType(this.value,%d);" /></td>
			<td><input size="10" maxlength="255" value="%s"
				onchange="saveCMsg(this.value,%d);" /></td>
			<td><input size="6" maxlength="10" value="%.2f"
				onchange="saveMin(this.value,%d);" /></td>
			<td><input size="6" maxlength="10" value="%.2f"
				onchange="saveMax(this.value,%d);" /></td>
			<td><input size="6" maxlength="10" value="%.2f"
				onchange="saveRLimit(this.value,%d);" /></td>
			</tr>',
			$w['TenderCode'],$w['TenderID'],
			$w['TenderName'],$w['TenderID'],
			$w['TenderType'],$w['TenderID'],
			$w['ChangeMessage'],$w['TenderID'],
			$w['MinAmount'],$w['TenderID'],
			$w['MaxAmount'],$w['TenderID'],
			$w['MaxRefund'],$w['TenderID']
		);
	}
	$ret .= "</table>";
	$ret .= "<br /><br />";
	$ret .= '<a href="" onclick="addTender();return false;">Add a new tender</a>';
	$ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$ret .= '<a href="delete.php">Delete a tender</a>';
	return $ret;
}

?>
