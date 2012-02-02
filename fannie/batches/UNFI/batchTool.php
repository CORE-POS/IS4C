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

$vendorID = $_REQUEST['vid'];
$superID = $_REQUEST['super'];
$filter = (isset($_REQUEST['filter'])&&$_REQUEST['filter']=='Yes')?True:False;

$sn = "All";
if ($superID != 99){
	$sn = $dbc->query("SELECT super_name FROM superDeptNames WHERE superID=$superID");
	$sn = array_pop($dbc->fetch_row($sn));
}
$vn = $dbc->query("SELECT vendorName FROM vendors WHERE vendorID=$vendorID");
$vn = array_pop($dbc->fetch_row($vn));

$batchName = $sn." ".$vn." PC ".date('m/d/y');
echo "<b>Batch</b>: ".$batchName;

$bidQ = "SELECT batchID FROM batches WHERE batchName='$batchName' AND batchType=4 AND discounttype=0
	ORDER BY batchID DESC";
$bidR = $dbc->query($bidQ);
if ($dbc->num_rows($bidR) == 0){
	$insQ = "INSERT INTO batches (batchName,startDate,endDate,batchType,discounttype,priority) VALUES
		('$batchName','1900-01-01','1900-01-01',4,0,0)";
	$insR = $dbc->query($insQ);
	$bidR = $dbc->query($bidQ);
}
$batchID = array_pop($dbc->fetch_row($bidR));

printf("<input type=hidden id=vendorID value=%d />
	<input type=hidden id=batchID value=%d />
	<input type=hidden id=superID value=%d />",
	$vendorID,$batchID,$superID);

$batchUPCs = array();
$bq = "SELECT upc FROM batchList WHERE batchID=$batchID";
$br = $dbc->query($bq);
while($bw = $dbc->fetch_row($br)) $batchUPCs[$bw[0]] = True;

$query = "SELECT p.upc,p.description,v.cost,p.normal_price,
	(p.normal_price - v.cost)/p.normal_price AS current_margin,
	s.srp,
	(s.srp - v.cost)/s.srp AS desired_margin,
	v.vendorDept,x.variable_pricing
	FROM products AS p INNER JOIN vendorItems AS v
	ON p.upc=v.upc AND v.vendorID=$vendorID
	INNER JOIN vendorSRPs AS s ON
	v.upc=s.upc AND v.vendorID=s.vendorID
	INNER JOIN vendors as b ON v.vendorID=b.vendorID
	LEFT JOIN prodExtra AS x on p.upc=x.upc ";
if ($superID != 99){
	$query .= " LEFT JOIN MasterSuperDepts AS m
		ON p.department=m.dept_ID ";
}
$query .= "WHERE v.cost > 0 ";
if ($superID != 99)
	$query .= " AND m.superID=$superID ";
if ($filter === False)
	$query .= " AND p.normal_price <> s.srp ";

// use distributor field from price change page
// as "preferred vendor". default is UNFI
if ($vn != "UNFI")
	$query .= " AND x.distributor = '$vn' ";
else 
	$query .= " AND (x.distributor='UNFI' or x.distributor <> b.vendorName) ";

$query .= " ORDER BY p.upc";
//echo $query;

$result = $dbc->query($query);

?>
<style type="text/css">
tr.green td.sub {
	background:#ccffcc;
}
tr.red td.sub {
	background:#ff6677;
}
tr.white td.sub {
	background:#ffffff;
}
td.srp {
	text-decoration: underline;
}
</style>
<script type="text/javascript" src="<?php echo $FANNIE_URL; ?>src/jquery/js/jquery-1.4.2.min.js">
</script>
<script type="text/javascript">
var vid = null;
var bid = null;
var sid = null;
$(document).ready(function(){
	vid = $('#vendorID').val();
	bid = $('#batchID').val();
	sid = $('#superID').val();
});

function toggleB(upc){
	var elem = $('#row'+upc).find('.addrem');
	
	var dstr = "upc="+upc+"&vendorID="+vid+"&superID="+sid+"&batchID="+bid;
	if (elem.html() == "Add"){
		elem.html('Del');
		var price = $('#row'+upc).find('.srp').html();
		$.ajax({
			url: 'batchAjax.php',
			data: dstr + '&action=batchAdd&price='+price,
			success: function(data){

			}
		});
	}
	else {
		elem.html('Add');
		$.ajax({
			url: 'batchAjax.php',
			data: dstr + '&action=batchDel',
			success: function(data){

			}
		});
	}
}
function toggleV(upc){
	var val = $('#row'+upc).find('.varp').attr('checked');
	if (val){
		$('#row'+upc).attr('class','white');
		$.ajax({
			url: 'batchAjax.php',
			data: 'action=addVarPricing&upc='+upc,
			success: function(data){

			}
		});
	}
	else {
		var m1 = $('#row'+upc).find('.cmargin').html();
		var m2 = $('#row'+upc).find('.dmargin').html();
		if (m1 >= m2)
			$('#row'+upc).attr('class','green');
		else
			$('#row'+upc).attr('class','red');
		$.ajax({
			url: 'batchAjax.php',
			data: 'action=delVarPricing&upc='+upc,
			success: function(data){

			}
		});
	}
}

function reprice(upc){
	if ($('#newprice'+upc).length > 0) return;

	var elem = $('#row'+upc).find('.srp');
	var srp = elem.html();

	var content = "<input type=\"text\" id=\"newprice"+upc+"\" value=\""+srp+"\" size=\"4\" />";
	var content2 = "<input type=\"submit\" value=\"Save\" onclick=\"saveprice('"+upc+"');\" />";
	elem.html(content);
	$('#row'+upc).find('.dmargin').html(content2);
	$('#newprice'+upc).focus();
}

function saveprice(upc){
	var srp = parseFloat($('#newprice'+upc).val());
	var cost = parseFloat($('#row'+upc).find('.cost').html());
	var newmargin = ((srp - cost) / srp) * 100;
	newmargin = Math.round(newmargin*100)/100;

	$('#row'+upc).find('.srp').html(srp);
	$('#row'+upc).find('.dmargin').html(newmargin+'%');

	var dstr = "upc="+upc+"&vendorID="+vid+"&superID="+sid+"&batchID="+bid;
	$.ajax({
		url: 'batchAjax.php',
		data: dstr+'&action=newPrice&price='+srp,
		cache: false,
		success: function(data){}
	});
}
</script>
<?php

echo "<table cellspacing=0 cellpadding=4 border=0>";
echo "<tr><td colspan=3>&nbsp;</td><th colspan=2>Current</th>
	<th colspan=2>Vendor</th></tr>";
echo "<tr><th>UPC</th><th>Our Description</th><th>Cost</th>
	<th>Price</th><th>Margin</th><th>SRP</th>
	<th>Margin</th><th>Cat</th><th>Var</th>
	<th>Batch</th></tr>";
while($row = $dbc->fetch_row($result)){
	$bg = "white";
	if ($row['variable_pricing'] != 1)
		$bg = ($row['normal_price']<$row['srp'])?'red':'green';
	printf("<tr id=row%s class=%s>
		<td class=\"sub\">%s</td>
		<td class=\"sub\">%s</td>
		<td class=\"sub cost\">%.3f</td>
		<td class=\"sub price\">%.2f</td>
		<td class=\"sub cmargin\">%.2f%%</td>
		<td onclick=\"reprice('%s');\" class=\"sub srp\">%.2f</td>
		<td class=\"sub dmargin\">%.2f%%</td>
		<td class=\"sub\">%d</td>
		<td><input class=varp type=checkbox onclick=\"toggleV('%s');\" %s /></td>
		<td class=white><a class=addrem href=\"\" onclick=\"toggleB('%s');return false;\">%s</a></td>
		</tr>",
		$row['upc'],
		$bg,
		$row['upc'],
		$row['description'],
		$row['cost'],
		$row['normal_price'],
		100*$row['current_margin'],
		$row['upc'],
		$row['srp'],
		100*$row['desired_margin'],
		$row['vendorDept'],
		$row['upc'],
		($row['variable_pricing']==1?'checked':''),
		$row['upc'],
		(isset($batchUPCs[$row['upc']])?'Del':'Add')
	);
}
echo "</table>";

?>
