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


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	 7Sep2012 Eric Lee In getVendorInfo() display VendorID on successful lookup.

*/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

if (isset($_REQUEST['action'])){
	switch($_REQUEST['action']){
	case 'vendorDisplay':
		getVendorInfo($_REQUEST['vid']);
		break;
	case 'newVendor':
		newVendor($_REQUEST['name']);
		break;
	case 'deleteCat':
		$q = $dbc->prepare_statement("DELETE FROM vendorDepartments
			WHERE vendorID=%d AND deptID=%d");
		$dbc->exec_statement($q,array($_REQUEST['vid'],$_REQUEST['deptID']));
		echo "Department deleted";
		break;
	case 'createCat':
		createVendorDepartment($_REQUEST['vid'],
			$_REQUEST['deptID'],$_REQUEST['name']);
		break;
	case 'updateCat':
		$q = $dbc->prepare_statement("UPDATE vendorDepartments
			SET name=?, margin=?
			WHERE vendorID=? AND deptID=?");
		$dbc->exec_statement($q,array($_REQUEST['name'],
			trim($_REQUEST['margin'],'%')/100,
			$_REQUEST['vid'],$_REQUEST['deptID']));
		break;
	case 'showCategoryItems':
		showCategoryItems($_REQUEST['vid'],$_REQUEST['deptID'],$_REQUEST['brand']);
		break;
	case 'getCategoryBrands':
		getCategoryBrands($_REQUEST['vid'],$_REQUEST['deptID']);
		break;
	case 'addPosItem':
		addPosItem($_REQUEST['upc'],$_REQUEST['vid'],$_REQUEST['price'],$_REQUEST['dept']);
		break;
	case 'saveScript':
		$q1 = $dbc->prepare_statement("DELETE FROM vendorLoadScripts WHERE vendorID=?");
		$dbc->exec_statement($q1,array($_REQUEST['vid']));
		$q2 = $dbc->prepare_statement("INSERT INTO vendorLoadScripts (vendorID,loadScript) VALUES (?,?)");
		$dbc->exec_statement($q2,array($_REQUEST['vid'],$_REQUEST['script']));
		break;
	}
}

function addPosItem($upc,$vid,$price,$dept){
	global $dbc;

	$p = $dbc->prepare_statement("SELECT i.*,v.vendorName FROM vendorItems AS i
		LEFT JOIN vendors AS v ON v.vendorID=i.vendorID
		WHERE i.vendorID=? AND upc=?");
	$vinfo = $dbc->exec_statement($p, array($vid,$upc));
	$vinfo = $dbc->fetch_row($vinfo);
	$p = $dbc->prepare_statement("SELECT * FROM departments WHERE dept_no=?");
	$dinfo = $dbc->exec_statement($p,array($dept));
	$dinfo = $dbc->fetch_row($dinfo);

	$query99 = $dbc->prepare_statement("INSERT INTO products (upc,description,normal_price,pricemethod,groupprice,quantity,
	special_price,specialpricemethod,specialgroupprice,specialquantity,
	department,size,tax,foodstamp,scale,scaleprice,mixmatchcode,modified,advertised,tareweight,discount,
	discounttype,unitofmeasure,wicable,qttyEnforced,idEnforced,cost,inUse,subdept,deposit,local,
	start_date,end_date,numflag) VALUES
	(?,?,?,0,0.00,0,0.00,0,0.00,0,?,'',?,?,0,0,0,{$dbc->now()},
	1,0,1,0,'',0,0,0,?,1,0,0.00,0,'1900-01-01','1900-01-01',0)");
	$args99 = array($upc,$vinfo['description'],
		$price,$dept,$dinfo['dept_tax'],$dinfo['dept_fs'],
		($vinfo['cost']/$vinfo['units']));

	$xInsQ = $dbc->prepare_statement("INSERT INTO prodExtra (upc,distributor,manufacturer,cost,margin,variable_pricing,location,
			case_quantity,case_cost,case_info) VALUES
			(?,?,?,?,0.00,0,'','',0.00,'')");
	$args = array($upc,$vinfo['brand'],
			$vinfo['vendorName'],($vinfo['cost']/$vinfo['units']));

	$dbc->exec_statement($query99,$args99);
	$dbc->exec_statement($xInsQ,$args);

	echo "Item added";
}

function getCategoryBrands($vid,$did){
	global $dbc;

	$query = "SELECT brand FROM vendorItems AS v
		LEFT JOIN vendorDepartments AS d ON
		v.vendorDept=d.deptID WHERE v.vendorID=?";
	$args = array($vid);
	if($did != 'All'){
		$query .= ' AND vendorDept=? ';
		$args[] = $did;
	}
	$query .= "GROUP BY brand ORDER BY brand";
	$ret = "<option value=\"\">Select a brand...</option>";
	$p = $dbc->prepare_statement($query);
	$result = $dbc->exec_statement($p,$args);
	while($row=$dbc->fetch_row($result))
		$ret .= "<option>$row[0]</option>";

	echo $ret;
}

function showCategoryItems($vid,$did,$brand){
	global $dbc;

	$depts = "";
	$p = $dbc->prepare_statement("SELECT dept_no,dept_name FROM departments ORDER BY dept_no");
	$rp = $dbc->exec_statement($p);
	while($rw = $dbc->fetch_row($rp))
		$depts .= "<option value=$rw[0]>$rw[0] $rw[1]</option>";

	$query = "SELECT v.upc,v.brand,v.description,v.size,
		v.cost/v.units as cost,
		CASE WHEN d.margin IS NULL THEN 0 ELSE d.margin END as margin,
		CASE WHEN p.upc IS NULL THEN 0 ELSE 1 END as inPOS
		FROM vendorItems AS v LEFT JOIN products AS p
		ON v.upc=p.upc LEFT JOIN vendorDepartments AS d
		ON d.deptID=v.vendorDept
		WHERE v.vendorID=? AND brand=?";
	$args = array($vid,$brand);
	if ($did != 'All'){
		$query .= ' AND vendorDept=? ';
		$args[] = $dept;
	}
	$query .= "ORDER BY v.upc";
	
	$ret = "<table cellspacing=0 cellpadding=4 border=1>";
	$ret .= "<tr><th>UPC</th><th>Brand</th><th>Description</th>";
	$ret .= "<th>Size</th><th>Cost</th><th colspan=3>&nbsp;</th></tr>";
	$p = $dbc->prepare_statement($query);
	$result = $dbc->exec_statement($p,$args);
	while($row = $dbc->fetch_row($result)){
		if ($row['inPOS'] == 1){
			$ret .= sprintf("<tr style=\"background:#ffffcc;\">
				<td>%s</td><td>%s</td><td>%s</td>
				<td>%s</td><td>\$%.2f</td><td colspan=3>&nbsp;
				</td></tr>",$row['upc'],$row['brand'],
				$row['description'],$row['size'],$row['cost']);
		}
		else {
			$srp = getSRP($row['cost'],$row['margin']);
			$ret .= sprintf("<tr id=row%s><td>%s</td><td>%s</td><td>%s</td>
				<td>%s</td><td>\$%.2f</td><td>
				<input type=text size=5 value=%.2f id=price%s />
				</td><td><select id=\"dept%s\">%s</select></td>
				<td id=button%s>
				<input type=submit value=\"Add to POS\"
				onclick=\"addToPos('%s');\" /></td></tr>",$row['upc'],
				$row['upc'],$row['brand'],$row['description'],
				$row['size'],$row['cost'],$srp,$row['upc'],
				$row['upc'],$depts,$row['upc'],$row['upc']);
		}
	}
	$ret .= "</table>";

	echo $ret;
}

function getSRP($cost,$margin){
	$srp = sprintf("%.2f",$cost/(1-$margin));
	while (substr($srp,strlen($srp)-1,strlen($srp)) != "5" &&
	       substr($srp,strlen($srp)-1,strlen($srp)) != "9")
		$srp += 0.01;
	return $srp;
}

function createVendorDepartment($vid,$did,$name){
	global $dbc;
	
	$chkQ = $dbc->prepare_statement("SELECT * FROM vendorDepartments WHERE
			vendorID=? AND deptID=?");
	$chkR = $dbc->exec_statement($chkQ,array($vid,$did));
	if ($dbc->num_rows($chkR) > 0){
		echo "Number #$did is already in use!";
		return;
	}

	$insQ = $dbc->prepare_statement("INSERT INTO vendorDepartments (vendorID,deptID,
		name,margin,testing,posDeptID) VALUES (?,?,
		?,0.00,0.00,0)");
	$insR = $dbc->exec_statement($insQ,array($vid,$did,$name));
	
	echo "Department created";
}

function newVendor($name){
	global $dbc;

	$name = $dbc->escape($name);
	$id = 1;	
	$p = $dbc->prepare_statement("SELECT max(vendorID) FROM vendors");
	$rp = $dbc->exec_statement($p);
	$rw = $dbc->fetch_row($rp);
	if ($rw[0] != "")
		$id = $rw[0]+1;

	$insQ = $dbc->prepare_statement("INSERT INTO vendors VALUES (?,?)");
	$dbc->exec_statement($insQ,array($id,$name));

	echo $id;
}

function getVendorInfo($id){
	global $dbc,$FANNIE_ROOT;
	$ret = "";

	$nameQ = $dbc->prepare_statement("SELECT vendorName FROM vendors WHERE vendorID=?");
	$nameR = $dbc->exec_statement($nameQ,array($id));
	if ($dbc->num_rows($nameR) < 1)
		$ret .= "<b>Name</b>: Unknown";
	else
		$ret .= "<b>Id</b>: $id &nbsp; <b>Name</b>: ".array_pop($dbc->fetch_row($nameR));
	$ret .= "<p />";

	$scriptQ = $dbc->prepare_statement("SELECT loadScript FROM vendorLoadScripts WHERE vendorID=?");
	$scriptR = $dbc->exec_statement($scriptQ,array($id));
	$ls = "";
	if ($scriptR && $dbc->num_rows($scriptR) > 0)
		$ls = array_pop($dbc->fetch_row($scriptR));
	/*
	$ret .= sprintf('<b>Load script</b>: <input type="text" value="%s" id="vscript" />
		<input type="submit" value="Save" onclick="saveScript(%d); return false;" />
		<p />',$ls,$id);
	*/
	$ret .= "<b>Load script</b>: <select id=\"vscript\" onchange=\"saveScript($id);\">";
	$dh = opendir($FANNIE_ROOT.'batches/UNFI/load-scripts/');
	while( ($file=readdir($dh)) !== False){
		if ($file[0]==".") continue;
		if (substr($file,-4) != ".php") continue;
		$ret .= sprintf("<option %s>%s</option>",($ls==$file?'selected':''),$file);
	}
	$ret .= '</select><p />';

	$itemQ = $dbc->prepare_statement("SELECT COUNT(*) FROM vendorItems WHERE vendorID=?");
	$itemR = $dbc->exec_statement($itemQ,array($id));
	$num = array_pop($dbc->fetch_row($itemR));
	if ($num == 0)
		$ret .= "This vendor contains 0 items";
	else {
		$ret .= "This vendor contains $num items";
		$ret .= "<br />";
		$ret .= "<a href=\"browse.php?vid=$id\">Browse vendor catalog</a>";	
	}
	$ret .= "<br />";
	$ret .= "<a href=\"update.php?vid=$id\">Update vendor catalog</a>";
	$ret .= "<p />";

	$itemQ = $dbc->prepare_statement("SELECT COUNT(*) FROM vendorDepartments WHERE vendorID=?");
	$itemR = $dbc->exec_statement($itemQ,array($id));
	$num = array_pop($dbc->fetch_row($itemR));
	if ($num == 0)
		$ret .= "<a href=\"vdepts.php?vid=$id\">This vendor's items are not yet arranged into departments</a>";
	else {
		$ret .= "This vendor's items are divided into ";
		$ret .= $num." departments";
		$ret .= "<br />";
		$ret .= "<a href=\"vdepts.php?vid=$id\">Display/Edit vendor departments</a>";
	}

	echo $ret;
}

function vendorDeptDisplay($id){
	global $dbc, $FANNIE_URL;
	
	$nameQ = $dbc->prepare_statement("SELECT vendorName FROM vendors WHERE vendorID=?");
	$nameR = $dbc->exec_statement($nameQ,array($id));
	$name = array_pop($dbc->fetch_row($nameR));

	$ret = "<b>Departments in $name</b><br />";
	$ret .= "<table cellspacing=0 cellpadding=4 border=1>";
	$ret .= "<tr><th>No.</th><th>Name</th><th>Margin</th>
		<th>&nbsp;</th><th>&nbsp;</th></tr>";

	$deptQ = $dbc->prepare_statement("SELECT * FROM vendorDepartments WHERE vendorID=?
		ORDER BY deptID");;
	$deptR = $dbc->exec_statement($deptQ,array($id));
	while($row = $dbc->fetch_row($deptR)){
		$ret .= sprintf("<tr>
			<td>%d</td>
			<td id=nametd%d>%s</td>
			<td id=margintd%d>%.2f%%</td>
			<td id=button%d><a href=\"\" onclick=\"edit(%d);return false;\">
			<img src=\"%s\" alt=\"Edit\" border=0 /></a></td>
			<td><a href=\"\" onclick=\"deleteCat(%d,'%s');return false\">
			<img src=\"%s\" alt=\"Delete\" border=0 /></a></td>
			</tr>",
			$row[1],$row[1],$row[2],$row[1],
			$row[3]*100,
			$row[1],$row[1],
			$FANNIE_URL.'src/img/buttons/b_edit.png',
			$row[1],$row[2],
			$FANNIE_URL.'src/img/buttons/b_drop.png');
	}
	$ret .= "</table>";
	echo $ret;
}
