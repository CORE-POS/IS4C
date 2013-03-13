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
if (!class_exists('FannieDB'))
	include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
if (!class_exists('FormLib'))
	include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');

$dbc = FannieDB::get($FANNIE_OP_DB);

$id = FormLib::get_form_value('id',0);
if (FormLib::get_form_value('saveCode',False) !== False){
	$code = FormLib::get_form_value('saveCode');

	$chkP = $dbc->prepare_statement("SELECT TenderID FROM tenders WHERE
		TenderCode=? AND TenderID<>?");
	$chk = $dbc->exec_statement($chkP,array($code,$id));
	if ($dbc->num_rows($chk) > 0)
		echo "Error: Code $code is already in use";
	else{
		$p = $dbc->prepare_statement("UPDATE tenders SET TenderCode=?
			WHERE TenderID=?");
		$dbc->exec_statement($p, array($code,$id));
	}
}
elseif(FormLib::get_form_value('saveName',False) !== False){
	$name = FormLib::get_form_value('saveName');
	$p = $dbc->prepare_statement("UPDATE tenders SET TenderName=?
		WHERE TenderID=?");
	$dbc->exec_statement($p, array($name,$id));
}
elseif(FormLib::get_form_value('saveType',False) !== False){
	$type = FormLib::get_form_value('saveType');
	$p = $dbc->prepare_statement("UPDATE tenders SET TenderType=?
		WHERE TenderID=?");
	$dbc->exec_statement($p, array($type,$id));
}
elseif(FormLib::get_form_value('saveCMsg',False) !== False){
	$msg = FormLib::get_form_value('saveCMsg');
	$p = $dbc->prepare_statement("UPDATE tenders SET ChangeMessage=?
		WHERE TenderID=?");
	$dbc->exec_statement($p, array($msg,$id));
}
elseif(FormLib::get_form_value('saveMin',False) !== False){
	$min = FormLib::get_form_value('saveMin');
	if (!is_numeric($min))
		echo "Error: Minimum must be a number";
	else {
		$p = $dbc->prepare_statement("UPDATE tenders SET MinAmount=?
			WHERE TenderID=?");
		$dbc->exec_statement($p, array($min,$id));
	}
}
elseif(FormLib::get_form_value('saveMax',False) !== False){
	$min = FormLib::get_form_value('saveMax');
	if (!is_numeric($max))
		echo "Error: Maximum must be a number";
	else {
		$p = $dbc->prepare_statement("UPDATE tenders SET MaxAmount=?
			WHERE TenderID=?");
		$dbc->exec_statement($p, array($max,$id));
	}
}
elseif(FormLib::get_form_value('saveRLimit',False) !== False){
	$limit = FormLib::get_form_value('saveRLimit');
	if (!is_numeric($limit))
		echo "Error: Refund limit must be a number";
	else {
		$p = $dbc->prepare_statement("UPDATE tenders SET MaxRefund=?
			WHERE TenderID=?");
		$dbc->exec_statement($p, array($limit,$id));
	}
}
elseif(FormLib::get_form_value('newTender',False) !== False){
	$newID=1;
	$idQ = $dbc->prepare_statement("SELECT MAX(TenderID) FROM tenders");
	$idR = $dbc->exec_statement($idQ);
	if ($dbc->num_rows($idR) > 0){
		$idW = $dbc->fetch_row($idR);
		if (!empty($idW[0])) $newID = $idW[0] + 1;
	}
	
	$prep = $dbc->prepare_statement("INSERT INTO tenders (TenderID, TenderCode,
		TenderName, TenderType, ChangeMessage, MinAmount, MaxAmount,
		MaxRefund) VALUES (?, '', 'NEW TENDER', 'CA', '', 0, 500, 0)"); 
	$dbc->exec_statement($prep, array($newID));

	echo getTenderTable();
}

function getTenderTable(){
	global $dbc;
	
	$ret = '<table cellpadding="4" cellspacing="0" border="1">
		<tr><th>Code</th><th>Name</th><th>Type</th>
		<th>Change Msg</th><th>Min</th><th>Max</th>
		<th>Refund Limit</th></tr>';

	$q = $dbc->prepare_statement("SELECT TenderID,TenderCode,TenderName,TenderType,
		ChangeMessage,MinAmount,MaxAmount,MaxRefund
		FROM tenders ORDER BY TenderID");
	$r = $dbc->exec_statement($q);
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
	$ret .= '<a href="DeleteTenderPage.php">Delete a tender</a>';
	return $ret;
}

?>
