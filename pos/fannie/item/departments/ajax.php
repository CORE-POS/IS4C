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

if (isset($_REQUEST['action'])){
	switch($_REQUEST['action']){
	case 'deptsInSuper':
		deptsInSuper($_REQUEST['sid']);
		break;
	case 'deptsNotInSuper':
		deptsNotInSuper($_REQUEST['sid']);
		break;
	case 'save':
		saveData($_REQUEST['sid'],$_REQUEST['name'],$_REQUEST['depts']);
		break;
	case 'deptDisplay':
		deptDisplay($_REQUEST['did']);
		break;
	case 'deptSave':
		deptSave($_POST);
		break;
	case 'addSub':
		addSub($_REQUEST['name'],$_REQUEST['did']);
		showSubs($_REQUEST['did']);
		break;
	case 'deleteSub':
		deleteSub($_REQUEST['sid']);
		showSubs($_REQUEST['did']);
		break;
	case 'showSubsForDept':
		showSubs($_REQUEST['did']);
		break;
	}
}

function deleteSub($sid){
	global $dbc;
	foreach($sid as $s)
		$dbc->query(sprintf("DELETE FROM subdepts WHERE subdept_no=%d",$s));
}

function addSub($name,$did){
	global $dbc;
	
	$res = $dbc->query("SELECT max(subdept_no) FROM subdepts");
	$sid = 1;
	if ($dbc->num_rows($res) > 0){
		$tmp = array_pop($dbc->Fetch_row($res));
		if (is_numeric($tmp)) $sid = $tmp+1;
	}

	$insQ = sprintf("INSERT INTO subdepts VALUES (%d,'%s',%d)",
			$sid,$dbc->escape($name),$did);
	$dbc->query($insQ);
}

function showSubs($did){
	global $dbc;
	$ret = "";
	$res = $dbc->query("SELECT subdept_no,subdept_name FROM subdepts
			WHERE dept_ID=$did ORDER BY subdept_name");
	while($row = $dbc->fetch_row($res)){
		$ret .= "<option value=$row[0]>$row[1]</option>";
	}

	echo $ret;
}

function deptSave($array){
	global $dbc;

	$id = $array['did'];
	$name = $dbc->escape($array['name']);
	$tax = $array['tax'];
	$fs = $array['fs'];
	$disc = $array['disc'];
	$min= $array['min'];
	$max = $array['max'];
	$margin = ((float)$array['margin']) / 100;
	$pcode = $array['pcode'];
	$new = $array['new'];

	if ($new == 1){
		$check = $dbc->query("SELECT * FROM departments WHERE dept_no=$id");
		if ($dbc->num_rows($check) > 0){
			echo "Error: Dept # $id is already in use";
			return;
		}

		$insQ = sprintf("INSERT INTO departments VALUES (%d,%s,%d,%d,%f,%f,%d,"
			.$dbc->now().",1)",$id,$name,$tax,$fs,$max,$min,$disc);
		$dbc->query($insQ);

		$insQ = "INSERT INTO superdepts VALUES (0,$id)";
		$dbc->query($insQ);
	}
	else {
		$query = "UPDATE departments SET
			dept_name=$name,
			dept_tax=$tax,
			dept_fs=$fs,
			dept_discount=$disc,	
			dept_limit=$max,
			dept_minimum=$min,
			modified=".$dbc->now()."
			WHERE dept_no=$id";
		$dbc->query($query);
	}

	$checkM = $dbc->query("SELECT * FROM deptMargin WHERE dept_ID=$id");
	if ($dbc->num_rows($checkM) > 0){
		$dbc->query("UPDATE deptMargin SET margin=$margin WHERE dept_ID=$id");
	}
	else {
		$dbc->query("INSERT INTO deptMargin VALUES ($id,$margin)");
	}

	$checkS = $dbc->query("SELECT * FROM deptSalesCodes WHERE dept_ID=$id");
	if (is_numeric($pcode) && $dbc->num_rows($checkS) > 0){
		$dbc->query("UPDATE deptSalesCodes SET salesCode=$pcode WHERE dept_ID=$id");
	}
	else if (is_numeric($pcode)){
		$dbc->query("INSERT INTO deptSalesCodes VALUES ($id,$pcode)");
	}
	else if ($dbc->num_rows($checkS) == 0 && !is_numeric($pcode)){
		$dbc->query("INSERT INTO deptSalesCodes VALUES ($id,$id)");
	}

	if ($new == 1){
		echo "Department $id - $name Created";
	}
	else {
		echo "Department $id - $name Saved";
	}
}

function saveData($id,$name,$depts){
	global $dbc;
	if ($id == -1){
		$resp = $dbc->query("SELECT max(superID)+1 FROM superdepts");
		$id = array_pop($dbc->fetch_row($resp));
	}
	else {
		$dbc->query("DELETE FROM superdepts WHERE superID=$id");
	}

	foreach($depts as $d){
		$dbc->query("INSERT INTO superdepts VALUES ($id,$d)");
	}
	
	$dbc->query("DELETE FROM superDeptNames WHERE superID=$id");
	$dbc->query("INSERT INTO superDeptNames VALUES ($id,'$name')");

	echo "Saved Settings for $name";
}

function deptsInSuper($id){
	global $dbc;

	$query = "SELECT superID,dept_ID,dept_name FROM
		superdepts AS s LEFT JOIN
		departments AS d ON s.dept_ID = d.dept_no
		WHERE superID=$id
		GROUP BY superID,dept_ID,dept_name
		ORDER BY superID,dept_ID";
	$result = $dbc->query($query);

	$ret = "";
	$lastDID = False;	
	while($row = $dbc->fetch_row($result)){
		if ($lastDID !== $row[1]){
			$ret .= "<option value=$row[1]";
			$ret .= ">$row[1] $row[2]</option>";	
		}
		$lastDID = $row[1];
	}

	echo $ret;
}

function deptsNotInSuper($id){
	global $dbc;

	$query = "SELECT superID,dept_no,dept_name FROM
		departments AS d LEFT JOIN
		superdepts AS s ON s.dept_ID = d.dept_no
		WHERE dept_no NOT IN
		(SELECT dept_ID FROM superdepts
		WHERE superID=$id)
		GROUP BY superID,dept_no,dept_name
		ORDER BY superID,dept_no";
	$result = $dbc->query($query);

	$ret = "";
	$lastDID = False;	
	while($row = $dbc->fetch_row($result)){
		if ($lastDID !== $row[1]){
			$ret .= "<option value=$row[1]";
			$ret .= ">$row[1] $row[2]</option>";	
		}
		$lastDID = $row[1];
	}

	echo $ret;

}

function deptDisplay($id){
	global $dbc;

	$name="";
	$tax="";
	$fs=0;
	$disc=1;
	$max=50;
	$min=0.01;
	$margin=0.00;
	$pcode="";

	if ($id != -1){
		$resp = $dbc->query("SELECT dept_name,dept_tax,dept_fs,dept_limit,
				dept_minimum,dept_discount,margin,salesCode
				FROM departments AS d LEFT JOIN
				deptMargin AS m ON d.dept_no=m.dept_ID
				LEFT JOIN deptSalesCodes as c
				ON d.dept_no=c.dept_ID
				WHERE dept_no=$id");
		$row = $dbc->fetch_row($resp);
		$name = $row[0];
		$tax = $row[1];
		$fs = $row[2];
		$max = $row[3];
		$min=$row[4];
		$disc = $row[5];
		$margin = $row[6];
		$pcode = $row[7];
	}
	$taxes = array();
	$taxes[0] = "NoTax";
	$resp = $dbc->query("SELECT id,description FROM taxrates ORDER BY id");
	while($row = $dbc->fetch_row($resp)){
		$taxes[$row[0]] = $row[1];
	}

	$ret = "<table cellspacing=0 cellpadding=4 border=1><tr>";
	$ret .= "<th>Dept #</th><th colspan=2>Name</th><th>Tax</th><th>FS</th></tr>";
	$ret .= "<tr><td>";
	if ($id == -1){
		$ret .= "<input type=text size=4 id=deptno />";
	}
	else {
		$ret .= $id;
	}
	$ret .= "</td>";
	$ret .= "<td colspan=2><input type=text maxlength=30 id=deptname value=\"$name\" /></td>";
	$ret .= "<td><select id=depttax>";
	foreach($taxes as $k=>$v){
		if ($k == $tax)
			$ret .= "<option value=$k selected>$v</option>";
		else
			$ret .= "<option value=$k>$v</option>";
	}
	$ret .= "</td>";
	$ret .= "<td><input type=checkbox id=deptfs ".($fs==1?'checked':'')." /></td>";
	$ret .= "</tr><tr>";
	$ret .= "<th>Discount</th><th>Min</th><th>Max</th><th>Margin</th><th>Sales Code</th></tr>";
	$ret .= "<td><input type=checkbox id=deptdisc ".($disc>0?'checked':'')." /></td>";
	$ret .= sprintf("<td>\$<input type=text size=5 id=deptmin value=\"%.2f\" /></td>",$min,0);	
	$ret .= sprintf("<td>\$<input type=text size=5 id=deptmax value=\"%.2f\" /></td>",$max,0);	
	$ret .= sprintf("<td><input type=text size=5 id=deptmargin value=\"%.2f\" />%%</td>",$margin*100);
	$ret .= "<td><input type=text size=5 id=deptsalescode value=\"$pcode\" /></td>";
	$ret .= "</tr></table>";
	if ($id != -1){
		$ret .= "<input type=hidden id=deptno value=\"$id\" />";
		$ret .= "<input type=hidden id=isNew value=0 />";
	}
	else
		$ret .= "<input type=hidden id=isNew value=1 />";

	$ret .= "<p /><input type=submit value=Save onclick=\"deptSave(); return false;\" />";

	echo $ret;
}

?>
