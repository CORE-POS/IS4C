<?php

include('print.php');
set_time_limit(0);

include('db.php');
$db = db();

if (isset($_REQUEST['checkin'])){

	$cn = $_REQUEST['cn'];
	$pinfo = array();
	$pinfo['meals'] = array();
	$pinfo['card_no'] = $cn;
	$pinfo['amt'] = $_REQUEST['ttldue'];

	$q = "DELETE FROM regMeals WHERE card_no=".$cn;
	$r = $db->query($q);
	for($i=0;$i<count($_REQUEST['am']);$i++){
		$q = sprintf("INSERT INTO regMeals VALUES (%d,'%s',%d)",
			$cn,($i==0?'OWNER':'GUEST'),$_REQUEST['am'][$i]);
		$r = $db->query($q);
		if ($_REQUEST['am'][$i] == 1)
			$pinfo['meals'][] = 'meat';
		else
			$pinfo['meals'][] = 'veg';
	}
	if (isset($_REQUEST['km'])){
		foreach($_REQUEST['km'] as $km){
			if ($km == 0) continue;
			$q = "INSERT INTO regMeals VALUES ($cn,'CHILD',0)";
			$r = $db->query($q);
			$pinfo['meals'][] = 'kid';
		}
	}
	for($i=0;$i<$_REQUEST['chicken'];$i++){
		$q = "INSERT INTO regMeals VALUES ($cn,'GUEST',1)";
		$r = $db->query($q);
		$pinfo['meals'][] = 'meat';
	}
	for($i=0;$i<$_REQUEST['veg'];$i++){
		$q = "INSERT INTO regMeals VALUES ($cn,'GUEST',2)";
		$r = $db->query($q);
		$pinfo['meals'][] = 'veg';
	}
	for($i=0;$i<$_REQUEST['kids'];$i++){
		$q = "INSERT INtO regMeals VALUES ($cn,'CHILD',0)";
		$r = $db->query($q);
		$pinfo['meals'][] = 'kid';
	}
	$q = "UPDATE registrations SET checked_in=1 WHERE card_no=".$cn;
	$r = $db->query($q);
	print_info($pinfo);
	header("Location: index.php");
	exit;
}
else if (isset($_REQUEST['back'])){
	header("Location: index.php");
	exit;
}

$q = "SELECT typeDesc,pending,arrived,ttl 
	FROM arrivals as a left join mealttl as t
	ON a.typeDesc=t.name";
$r = $db->query($q);
$arr = array();
while($w = $db->fetch_row($r))
	$arr[] = $w;

$cn = (int)$_REQUEST['cn'];

$q = "SELECT name,guest_count,child_count,1 as paid,checked_in
	FROM registrations WHERE card_no=".$cn;
$r = $db->query($q);
$regW = $db->fetch_row($r);

$q = "SELECT t.typeDesc,m.subtype FROM regMeals as m
	LEFT JOIN mealType AS t ON m.subtype=t.id
	WHERE m.card_no=".$cn;
$r = $db->query($q);
$adult = array();
$kids = array();
while($w = $db->fetch_row($r)){
	if ($w[1] > 0)
		$adult[] = $w;
	else
		$kids[] = $w;
}
?>
<script type="text/javascript">
function reCalc(){
	var c = document.getElementById('chicken').value;
	var v = document.getElementById('veg').value;
	var b = document.getElementById('basedue').value;

	var due = (c*20) + (v*20) + (1*b);
	document.getElementById('amtdue').innerHTML='$'+due;
	document.getElementById('ttldue').value = due;
}
</script>
<body onload="document.getElementById('chicken').focus();">
<div>
	<div style="float:left;width:60%;">
	<form method="post" action="edit.php">
	<table>
	<?php if ($regW['checked_in'] != 0){ 
		echo '<tr><th colspan="2" style="color:red;">ALREADY CHECKED IN</th></tr>';
	} ?>
	<tr><th>Name</th><td><?php echo $regW['name']; ?></td></tr>
	<tr><th>Paid</th><td><?php echo ($regW['paid']==1?'Yes':'No'); ?></td></tr>
	<tr><th>Guests</th><td><?php echo $regW['guest_count']; ?></td></tr>
	<tr><th>Children</th><td><?php echo $regW['child_count']; ?></td></tr>
	<tr><td colspan="2" align="center">Registered Meals</td></tr>
	<?php foreach ($adult as $a){
	echo '<tr><th>Adult Meal</th><td><select name="am[]">';
	if ($a[1]==1) 
		echo '<option value="1" selected>Chicken</option><option value="2">Curry</option>';
	else
		echo '<option value="1">Chicken</option><option value="2" selected>Curry</option>';
	echo '</select></td></tr>';
	} ?>
	<?php foreach ($kids as $k){
	echo '<tr><th>Child Meal</th><td><select name="km[]">';
	echo '<option value="1" selected>Spaghetti</option><option value="0">None</option>';
	echo '</select></td></tr>';
	} ?>
	<tr><td colspan="2" align="center">Additional Meals</td></tr>
	<tr><th>Chicken</th><td><input type="text" name="chicken" id="chicken" value="0" onchange="reCalc(); "/></td></tr>
	<tr><th>Curry</th><td><input type="text" name="veg" id="veg" onchange="reCalc();" value="0" /></td></tr>
	<tr><th>Spaghetti</th><td><input type="text" name="kids" value="0" /></td></tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr><th>Amount Due</th><td id="amtdue">$<?php echo ($regW['paid']==1?0:20*$regW['guest_count']); ?></td></tr>
	<input type="hidden" id="basedue" name="basedue" value="<?php echo ($regW['paid']==1?0:20*$regW['guest_count']); ?>" />
	<input type="hidden" id="ttldue" name="ttldue" value="<?php echo ($regW['paid']==1?0:20*$regW['guest_count']); ?>" />
	<input type="hidden" name="cn" value="<?php echo $cn; ?>" />
	<tr><td><input type="submit" name="checkin" value="Check In" /></td>
	<td><input type="submit" name="back" value="Go Back" /></td></tr>
	</table>
	</form>
	</div>

	<div style="float:left;width:35%;">
	<table cellspacing="0" cellpadding="4" border="1">
	<tr><th>&nbsp;</th><th>Remaining</th><th>Claimed</th></tr>
	<?php foreach($arr as $a){
		printf('<tr><td>%s</td><td>%d</td><td>%d</td></tr>',
			$a['typeDesc'],$a['pending'],$a['arrived']);
	} ?>
	</table>
	</div>

	<div style="clear:left;"></div>
</div>
</body>
