<?php
include('../../../config.php');
?>
<html>
<head>
<title>Custom Coupons</title>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
        language="javascript"></script>
</head>
<body>
<a href=explainify.html>Help!</a><br /><br />
<?php

require($FANNIE_ROOT.'src/SQLManager.php');
include('../../db.php');

$depts = array();
$query = "SELECT dept_no,dept_name FROM departments ORDER BY dept_no";
$result = $sql->query($query);
while($row = $sql->fetch_row($result)){
	$depts[$row[0]] = $row[1];
}

if (isset($_REQUEST['new'])){
	$maxQ = "SELECT max(coupID) from houseCoupons";
	$max = array_pop($sql->fetch_row($sql->query($maxQ)));
	$cid = $max+1;
	
	$insQ = "INSERT INTO houseCoupons (coupID) values ($cid)";
	$sql->query($insQ);
	$_REQUEST['cid'] = $cid;
}

if (isset($_REQUEST['cid'])){
	$cid = $_REQUEST['cid'];
	if (isset($_REQUEST['submitsave']) || isset($_REQUEST['submitadd'])
		|| isset($_REQUEST['submitdelete'])){
		$expires = isset($_REQUEST['expires'])?$_REQUEST['expires']:'';
		if ($expires == '') $expires = "NULL";
		else $expires = "'$expires'";
		$limit = isset($_REQUEST['limit'])?$_REQUEST['limit']:1;
		$mem = isset($_REQUEST['memberonly'])?1:0;
		$dept = isset($_REQUEST['dept'])?$_REQUEST['dept']:800;
		$dtype = isset($_REQUEST['dtype'])?$_REQUEST['dtype']:'Q';
		$dval = isset($_REQUEST['dval'])?$_REQUEST['dval']:0;
		$mtype = isset($_REQUEST['mtype'])?$_REQUEST['mtype']:'Q';
		$mval = isset($_REQUEST['mval'])?$_REQUEST['mval']:0;

		$query =sprintf("UPDATE houseCoupons SET endDate=%s,
			limit=%d,memberOnly=%d,discountType='%s',
			discountValue=%f,minType='%s',minValue=%f,
			department=%d WHERE coupID=%d",
			$expires,$limit,$mem,$dtype,$dval,$mtype,
			$mval,$dept,$cid);
		$sql->query($query);
	}

	if (isset($_REQUEST['upc']) && $_REQUEST['upc'] != 0){
		$upc = $_REQUEST['upc'];
		if (!isset($_REQUEST['upcIsDept']))
			$upc = str_pad($upc,13,'0',STR_PAD_LEFT);
		$type = $_REQUEST['newtype'];
		$check = "SELECT upc FROM houseCouponItems WHERE
			upc='$upc' and coupID=$cid";
		$check = $sql->query($check);
		if ($sql->num_rows($check) == 0){
			$query = sprintf("INSERT INTO houseCouponItems VALUES (
				%s,'%s','%s')",$cid,$upc,$type);
			$sql->query($query);
		}
		else {
			$query = sprintf("UPDATE houseCouponItems SET type='%s'
				WHERE upc='%s' AND coupID=%s",$type,$upc,$cid);
			$sql->query($query);
		}
	}

	if (isset($_REQUEST['del'])){
		foreach($_REQUEST['del'] as $upc){
			$query = sprintf("DELETE FROM houseCouponItems
				WHERE upc='%s' AND coupID=%s",$upc,$cid);
			$sql->query($query);
		}
	}

	displayCoupon($_REQUEST['cid']);
}
else {
	displayDefault();
}
?>
</body>

</html>
<?php

function displayDefault(){
	global $sql;

	echo "<a href=\"index.php?new=yes\">Make a new coupon</a><p />";

	$query = "SELECT coupID,endDate FROM houseCoupons ORDER BY coupID DESC";
	$result = $sql->query($query);
	while($row = $sql->fetch_row($result)){
		printf("<a href=\"index.php?cid=%d\">Coupon #%d</a> (Expires: %s)<br />",
			$row[0],$row[0],($row[1]==""?'never':$row[1]));
	}
}

function displayCoupon($cid){
	global $sql,$depts;

	$q1 = "SELECT * FROM HouseCoupons WHERE coupID=$cid";
	$r1 = $sql->query($q1);
	$row = $sql->fetch_row($r1);

	$expires = $row['endDate'];
	$limit = $row['limit'];
	$mem = $row['memberOnly'];
	$dType = $row['discountType'];
	$dVal = $row['discountValue'];
	$mType = $row['minType'];
	$mVal = $row['minValue'];
	$dept = $row['department'];

	echo "<form action=index.php method=post>";
	echo "<input type=hidden name=cid value=\"$cid\" />";

	printf("<table cellspacing=0 cellpadding=4><tr>
		<th>Coupon ID#</th><td>%s</td><th>UPC</th>
		<td>%s</td></tr><tr><th>Expires</th>
		<td><input type=text name=expires value=\"%s\" size=8 
		onclick=\"showCalendarControl(this);\" />
		</td><th>Limit</th><td><input type=text name=limit size=3
		value=\"%s\" /></td></tr><tr><th>Member-only</th><td>
		<input type=checkbox name=memberonly %s /></td><th>
		Department</th><td><select name=dept>",
		$cid,"00499999".str_pad($cid,5,'0',STR_PAD_LEFT),
		$expires,$limit,($mem==1?'checked':'') );
	foreach($depts as $k=>$v){
		echo "<option value=\"$k\"";
		if ($k == $dept) echo " selected";
		echo ">$k $v</option>";
	}
	echo "</select></td></tr>";

	$dts = array('Q'=>'Quantity Discount',
		'P'=>'Set Price Discount',
		'FI'=>'Scaling Discount (Item)',
		'FD'=>'Scaling Discount (Department)',
		'F'=>'Flat Discount',
		'%'=>'Percent Discount (Transaction)'
	);
	echo "<tr><th>Discount Type</th><td>
		<select name=dtype>";
	foreach($dts as $k=>$v){
		echo "<option value=\"$k\"";
		if ($k == $dType) echo " selected";
		echo ">$v</option>";
	}
	echo "</select></td><th>Discount value</th>
		<td><input type=text name=dval value=\"$dVal\"
		size=5 /></td></tr>";


	$mts = array(
		'Q'=>'Quantity (at least)',
		'Q+'=>'Quantity (more than)',
		'D'=>'Department (at least $)',
		'D+'=>'Department (more than $)',
		'M'=>'Mixed',
		'$'=>'Total (at least $)',
		'$+'=>'Total (more than $)',
		''=>'No minimum'
	);
	echo "<tr><th>Minimum Type</th><td>
		<select name=mtype>";
	foreach($mts as $k=>$v){
		echo "<option value=\"$k\"";
		if ($k == $mType) echo " selected";
		echo ">$v</option>";
	}
	echo "</select></td><th>Minimum value</th>
		<td><input type=text name=mval value=\"$mVal\"
		size=5 /></td></tr>";

	echo "</table>";
	echo "<br /><input type=submit name=submitsave value=Save />";

	if ($mType == "Q" || $mType == "Q+" || $mType == "M"){
		echo "<hr />";
		echo "<b>Add UPC</b>: <input type=text size=13 name=upc />
		<select name=newtype><option>BOTH</option><option>QUALIFIER</option>
		<option>DISCOUNT</option></select>
		<input type=submit name=submitadd value=Add />";
		echo "<br /><br />";
		echo "<table cellspacing=0 cellpadding=4 border=1>
		<tr><th colspan=4>Items</th></tr>";
		$query = "SELECT h.upc,p.description,h.type FROM
			houseCouponItems as h LEFT JOIN Products AS
			p ON h.upc = p.upc WHERE coupID=$cid";
		$result = $sql->query($query);
		while($row = $sql->fetch_row($result)){
			printf("<tr><td>%s</td><td>%s</td><td>%s</td>
				<td><input type=checkbox name=del[] 
				value=\"%s\" /></tr>",
				$row[0],$row[1],$row[2],$row[0]);
		}
		echo "</table>";
		echo "<br />";
		echo "<input type=submit name=submitdelete value=\"Delete Selected Items\" />";
	}
	else if ($mType == "D" || $mType == "D+"){
		echo "<hr />";
		echo "<input type=hidden name=upcIsDept value=yes />";
		echo "<b>Add Dept</b>: <select name=upc>";
		foreach($depts as $k=>$v){
			echo "<option value=\"$k\"";
			echo ">$k $v</option>";
		}	
		echo "</select> ";
		echo "<select name=newtype><option>BOTH</option>
		</select>
		<input type=submitadd value=Add />";
		echo "<br /><br />";
		echo "<table cellspacing=0 cellpadding=4 border=1>
		<tr><th colspan=4>Items</th></tr>";
		$query = "SELECT h.upc,d.dept_name,h.type FROM
			houseCouponItems as h LEFT JOIN Departments as d
			ON h.upc = d.dept_no WHERE coupID=$cid";
		$result = $sql->query($query);
		while($row = $sql->fetch_row($result)){
			printf("<tr><td>%s</td><td>%s</td><td>%s</td>
				<td><input type=checkbox name=del[] 
				value=\"%s\" /></tr>",
				$row[0],$row[1],$row[2],$row[0]);
		}
		echo "</table>";
		echo "<br />";
		echo "<input type=submit name=submitdelete value=\"Delete Selected Delete\" />";
	}

	echo "</form>";
}

?>
