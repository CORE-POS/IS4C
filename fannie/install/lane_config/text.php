<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);
include('../util.php');

// keys are customReceipt.type values.
$TRANSLATE = array(
	'receiptHeader'=>'Receipt Header',
	'receiptFooter'=>'Receipt Footer',
	'ckEndorse'=>'Check Endorsement',
	'welcomeMsg'=>'Welcome On-screen Message',
	'farewellMsg'=>'Goodbye On-screen Message',
	'trainingMsg'=>'Training On-screen Message',
	'chargeSlip'=>'Store Charge Slip'
);

if (isset($_REQUEST['new_submit'])){
	$chkQ = $dbc->prepare_statement("SELECT MAX(seq) FROM customReceipt WHERE type=?");
	$chkR = $dbc->exec_statement($chkQ, array($_REQUEST['new_type']));
	$seq = 0;
	if ($dbc->num_rows($chkR) > 0){
		$max = array_pop($dbc->fetch_row($chkR));
		if ($max != null) $seq=$max+1;
	}
	if (!empty($_REQUEST['new_content'])){
		$insQ = $dbc->prepare_statement("INSERT INTO customReceipt (type,text,seq) VALUES (?,?,?)");
		$dbc->exec_statement($insQ,array($_REQUEST['new_type'],$_REQUEST['new_content'],$seq));
	}
}
else if (isset($_REQUEST['old_submit'])){
	$cont = $_REQUEST['old_content'];
	$type = $_REQUEST['old_type'];
	$seq=0;
	$prev_type='';
	$trun = $dbc->prepare_statement("TRUNCATE TABLE customReceipt");
	$dbc->exec_statement($trun);
	$insP = $dbc->prepare_statement("INSERT INTO customReceipt (type,text,seq) VALUES (?,?,?)");
	for($i=0;$i<count($cont);$i++){
		if ($prev_type != $type[$i])
			$seq = 0; // new type, reset sequence
		if (empty($cont[$i])) 
			continue; // empty means delete that line

		$dbc->exec_statement($insP, array($type[$i],$cont[$i],$seq));

		$prev_type=$type[$i];
		$seq++;
	}
}

?>
<html>
<head>
<title>Lane Global: Text Strings</title>
<link rel="stylesheet" href="../../src/css/install.css" type="text/css" />
<script type="text/javascript" src="../../src/jquery/jquery.js"></script>
</head>
<body>
<?php
echo showLinkToFannie();
echo showInstallTabsLane("Text Strings", '');
?>

<form method="post" action="text.php">
<h1>IT CORE Lane Global Configuration: Text Strings</h1>
<p class="ichunk">Use this utility to enter and edit the lines of text that appear on
receipts, the lane Welcome screen, and elsewhere.
<br />If your receipts have no headers or footers or they are wrong this is the place to fix that.
<br />The upper form is for adding lines.
<br />The lower form is for editing existing lines.
</p>
<hr />
<h3 style="margin-bottom:0.0em;">Add lines</h3>
<p class="ichunk" style="margin-top:0.25em;">Select a type of text string, enter a line, for it, and click "Add".
<br />All types may initially have no lines, i.e. be empty.
</p>
<select name="new_type" size="5">
<?php
$tcount = 0;
foreach($TRANSLATE as $short=>$long){
	$tcount++;
	if (isset($_REQUEST['new_type'])) {
		$selected=($_REQUEST['new_type']==$short)?'selected':'';
	} else {
		$selected = ($tcount==1)?'selected':'';
	}
	printf('<option value="%s" %s>%s</option>',
		$short, $selected, $long);
}
?>
</select>
<input type="text" name="new_content" maxlength="80" />
<input type="submit" name="new_submit" value="Add a line of the selected type" />
</form>
<hr />
<h3 style="margin-bottom:0.0em;">Edit existing lines</h3>
<p class="ichunk" style="margin-top:0.25em;">Existing lines of text of different types are displayed below and can be edited there.
<br />All types may initially have no lines in which case the heading will not appear and no line boxes will appear.
<br />To delete a line erase all the text from it.
</p>
<form method="post" action="text.php">
<?php
$q = $dbc->prepare_statement("SELECT type,text FROM customReceipt ORDER BY type,seq");
$r = $dbc->exec_statement($q);
$header="";
$i=1;
while($w = $dbc->fetch_row($r)){
	if ($header != $w['type']){
		echo '<h3>'.$TRANSLATE[$w['type']].'</h3>';
		$header = $w['type'];	
		$i=1;
	}
	printf('<p>%d:<input type="text" maxlength="80" name="old_content[]" value="%s" />
		<input type="hidden" name="old_type[]" value="%s" /></p>',
		$i++,$w['text'],$w['type']);
}
?>
<input type="submit" name="old_submit" value="Save Changes" />
</form>
