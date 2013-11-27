<?php
include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

require($FANNIE_ROOT.'auth/login.php');
$user = validateUserQuiet('overshorts');

/*
 * check isset too in case 20 minute login expired while data
 * was being entered.
 */
if (!$user && !isset($_POST['action'])){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}admin/Cashiers/overshort.php");
	return;
}

$sql = $dbc;

/* actions via POST are AJAX requests */
if (isset($_POST['action'])){
  switch($_POST['action']){
  case 'save':
	$date = $_POST['curDate'];
	$data = $_POST['data'];
	$user = $_POST['user'];
	$resolved = $_POST['resolved'];
	$notes = $_POST['notes'];
	
	$checkQ = $sql->prepare_statement("select username from overshortsLog where date=?");
	$checkR = $sql->exec_statement($checkQ,array($date));
	if ($sql->num_rows($checkR) == 0){
		$insQ = $sql->prepare_statement("insert into overshortsLog values (?,?,?)");
		$insR = $sql->exec_statement($insQ,array($date,$user,$resolved));
	}
	else {
		$upQ = $sql->prepare_statement("update overshortsLog set username=?,resolved=? where date=?");
		$upR = $sql->exec_statement($upQ,array($user,$resolved,$date));
	}
	
	save($date,$data);
	saveNotes($date,$notes);
	echo "saved";
	break;
	case 'date':
		$date = $_POST['arg'];
		$dlog = DTransactionsModel::selectDlog($date);
		/* determine who worked that day (and their first names) */
		$empsQ = $sql->prepare_statement("select e.firstname,d.emp_no,-1*sum(total) as total,trans_subtype,t.TenderName
			from $dlog as d,employees as e, tenders as t where
		      ".$sql->datediff('d.tdate',"?")." = 0 and trans_type='T' and d.emp_no = e.emp_no
			AND d.trans_subtype=t.TenderCode	
		      group by d.emp_no,e.firstname,trans_subtype,TenderName order by e.firstname,trans_subtype");
		$empsR=$sql->exec_statement($empsQ,array($date));
		$output = "<h3 id=currentdate>$date</h3>";
		//$output .= "<form onsubmit=\"excel(); return false;\" >";
		$output .= "<form onsubmit=\"save(); return false;\">";
		$output .= "<table border=1 cellspacing=2 cellpadding=2><tr>";
		$output .= "<th>Name</th><th>&nbsp;</th><th>Total</th><th>Counted Amt</th><th>Over/Short</th></tr>";

		$tenderTTL = array();
		$countTTL = array();
		$osTTL = array();
		$empTTL = array();
    
		$overallTotal = 0;
		$overallCountTotal = 0;
		$overallOSTotal = 0;    
		$overallnote = '';

		$tnames = array();

		// gather POS totals
		while ($row = $sql->fetch_array($empsR)){
			$id = $row['emp_no'];
			if (!isset($empTTL[$id])){
				$empTTL[$id] = array();
				$empTTL[$id]['name'] = $row['firstname'];
				$empTTL[$id]['pos_tenders'] = array();
				$empTTL[$id]['count_tenders'] = array();
				$empTTL[$id]['note'] = '';
			}

			$tender = strtoupper($row['trans_subtype']);
			$empTTL[$id]['pos_tenders'][$tender] = $row['total'];
			if (!isset($tnames[$tender])) $tnames[$tender] = $row['TenderName'];
		}

		// gather counted totals
		$query = $sql->prepare_statement("SELECT emp_no,amt,tender_type FROM dailyCounts WHERE date=?");
		$result = $sql->exec_statement($query,array($date));
		while($row = $sql->fetch_row($result)){
			$id = $row['emp_no'];
			$tender = strtoupper($row['tender_type']);
			$empTTL[$id]['count_tenders'][$tender] = $row['amt'];
		}

		// gather notes
		$noteQ = $sql->prepare_statement("select emp_no,note from dailyNotes where date=?");
		$noteR = $sql->exec_statement($noteQ,array($date));
		while($noteW = $sql->fetch_array($noteR)){
			$id = $noteW['emp_no'];
			$note = stripslashes($noteW['note']);
			if ($id == -1) $overallnote = $note;
			else $empTTL[$id]['note'] = $note;
		}

		foreach($empTTL as $empID=>$data){
			$output .= "<input type=hidden name=cashier value=\"$empID\" />";
     			
			$empPosTTL = 0;
			$empCountTTL = 0;
			$empOSTTL = 0;
			if (!isset($countTTL['CA'])) $countTTL['CA'] = 0;
			if (!isset($osTTL['CA'])) $osTTL['CA'] = 0;
			if (!isset($tenderTTL['CA'])) $tenderTTL['CA'] = 0;
			
			if (!isset($data['name'])) var_dump($data);

			$output .= "<tr><td><a href=overshortSingleEmp.php?date=$date&emp_no=$empID target={$date}_{$empID}>{$data['name']}</a></td>";
			$output .= "<td>Starting cash</td><td>n/a</td>";
			$startcash = isset($data['count_tenders']['SCA']) ? $data['count_tenders']['SCA'] : 0;
			$output .= "<td><input type=text id=startingCash$empID value=\"$startcash\"
				class=startingCash onchange=\"calcOS('Cash',$empID);\" /></td><td>n/a</td></tr>";
			$empCountTTL -= $startcash;
			$countTTL['CA'] -= $startcash;
      
			if (!isset($data['pos_tenders']['CA']))
				$data['pos_tenders']['CA'] = 0;
			$output .= "<tr><td>&nbsp;</td><td>Cash</td><td class=dlogCash 
				id=dlogCash$empID>{$data['pos_tenders']['CA']}</td>";
			$cash = isset($data['count_tenders']['CA']) ? $data['count_tenders']['CA'] : 0;
			$output .= "<td><input type=text id=countCash$empID class=countCash 
				onchange=\"calcOS('Cash',$empID);\" value=\"$cash\"/></td>";
			$os = round($cash - $data['pos_tenders']['CA'] - $startcash,2);
			$output .= "<td id=osCash$empID>$os</td></tr>";
			$output .= "<input type=hidden class=osCashHidden id=osCash{$empID}Hidden value=\"$os\" />";
		
			$countTTL['CA'] += $cash;
			$osTTL['CA'] += $os;
			$tenderTTL['CA'] += $data['pos_tenders']['CA'];
			
			$empPosTTL += $data['pos_tenders']['CA'];
			$empCountTTL += $cash;	
			$empOSTTL += $os;
			
			foreach($data['pos_tenders'] as $tID => $amt){
				if ($tID == 'CA') continue;
				$output .= "<tr><td>&nbsp;</td><td>{$tnames[$tID]}</td><td id=dlog{$tID}{$empID}>$amt</td>";
				$counted = isset($data['count_tenders'][$tID]) ? $data['count_tenders'][$tID] : 0;
				$output .= "<td><input type=text id=count{$tID}{$empID} class=count$tID 
					onchange=\"calcOS('$tID',$empID);\" value=\"$counted\" /></td>";
				$os = round($counted - $amt,2);
				$output .= "<td id=os{$tID}{$empID}>$os</td></tr>";
				$output .= "<input type=hidden name=os{$tID}Hidden id=os{$tID}{$empID}Hidden value=\"$os\" />";

				if (!isset($countTTL[$tID])) $countTTL[$tID] = 0;
				if (!isset($osTTL[$tID])) $osTTL[$tID] = 0;
				if (!isset($tenderTTL[$tID])) $tenderTTL[$tID] = 0;
		
				$countTTL[$tID] += $counted;
				$osTTL[$tID] += $os;
				$tenderTTL[$tID] += $amt;

				$empCountTTL += $counted;
				$empOSTTL += $os;
				$empPosTTL += $amt;
			}
      
			$output .= "<tr><td>&nbsp;</td><td>Cashier totals</td>";
			$output .= sprintf("<td>%.2f</td>",round($empPosTTL,2));
			$output .= sprintf("<td id=countTotal$empID>%.2f</td>",round($empCountTTL,2));
			$output .= sprintf("<td id=osTotal$empID>%.2f</td>",round($empOSTTL,2));
			$output .= "<tr><td>&nbsp;</td><td>Notes</td><td colspan=3</td>";
			$output .= "<textarea rows=5 cols=35 id=note$empID>{$data['note']}</textarea></td></tr>";
			$output .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
		}

		/* add overall totals */
		$output .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
		foreach($tenderTTL as $tID => $amt){
			$output .= "<tr><td><b>Totals</b></td><td>Cash</td><td id={$tID}total>$amt</td>";
			$output .= "<td id=count{$tID}Total>{$countTTL[$tID]}</td>";
			$output .= "<td id=os{$tID}Total>{$osTTL[$tID]}</td></tr>";
			$overallTotal += $amt;
			$overallCountTotal += $countTTL[$tID];
			$overallOSTotal += $osTTL[$tID];
		}

		$overallTotal = round($overallTotal,2);
		$overallCountTotal = round($overallCountTotal,2);
		$overallOSTotal = round($overallOSTotal,2);
		$output .= "<tr><td><b>Grand totals</td><td>&nbsp;</td>";
		$output .= "<td id=overallTotal>$overallTotal</td>";
		$output .= "<td id=overallCountTotal>$overallCountTotal</td>";
		$output .= "<td id=overallOSTotal>$overallOSTotal</td></tr>";

		$output .= "<tr><td>&nbsp;</td><td>Notes</td><td colspan=3</td>";
		$output .= "<textarea rows=5 cols=35 id=totalsnote>$overallnote</textarea></td></tr>";

		$output .= "</table>";
    
		$extraQ = $sql->prepare_statement("select username, resolved from overshortsLog where date=?");
		$extraR = $sql->exec_statement($extraQ,array($date));
		$extraW = $sql->fetch_array($extraR);
		$output .= "This date last edited by: <span id=lastEditedBy><b>$extraW[0]</b></span><br />";
		$output .= "<input type=submit value=Save />";
		$output .= "<input type=checkbox id=resolved ";
		if ($extraW[1] == 1)
			$output .= "checked";
		$output .= " /> Resolved";
		$output .= "</form>";

		/* "send" output back */
		echo $output;
		break;
	}
  
	return;
}

function save($date,$data){
	global $sql;
	$bycashier = explode(',',$data);

	foreach ($bycashier as $c){
		$temp = explode(':',$c);
		if (count($temp) != 2) continue;
		$cashier = $temp[0];
		$tenders = explode(';',$temp[1]);
		$checkQ = $sql->prepare_statement("select emp_no from dailyCounts
				  where date=? and emp_no=? and tender_type=?");
		$insQ = $sql->prepare_statement("insert into dailyCounts values (?,?,?,?)");
		$upQ = $sql->prepare_statement("update dailyCounts set amt=? where date=? and emp_no=? and tender_type=?");
		foreach($tenders as $t){
			$temp = explode('|',$t);
			$tender_type = $temp[0];
			$amt = rtrim($temp[1]);
			if ($amt != ''){
				$checkR = $sql->exec_statement($checkQ,array($date,$cashier,$tender_type));
				if ($sql->num_rows($checkR) == 0){
					$insR = $sql->exec_statement($insQ,array($date,$cashier,$tender_type,$amt));
				}
				else {
					$upR = $sql->exec_statement($upQ,array($amt,$date,$cashier,$tender_type));
				}
			}
		}
	}	
}

function saveNotes($date,$notes){
	global $sql;
	$noteIDs = explode('`',$notes);
	$checkQ = $sql->prepare_statement("select emp_no from dailyNotes where date=? and emp_no=?");
	$insQ = $sql->prepare_statement("insert into dailyNotes values (?,?,?)");
	$upQ = $sql->prepare_statement("update dailyNotes set note=? where date=? and emp_no=?");
	foreach ($noteIDs as $n){
		$temp = explode('|',$n);
		$emp = $temp[0];
		$note = str_replace("'","''",urldecode($temp[1]));
		
		$checkR = $sql->exec_statement($checkQ,array($date,$emp));
		if ($sql->num_rows($checkR) == 0){
			$insR = $sql->exec_statement($insQ,array($date,$emp,$note));
		}
		else {
			$upR = $sql->exec_statement($upQ,array($note,$date,$emp));
		}
	}
}

?>
<html>
<head><title>Overshorts</title>
<script type="text/javascript" src="<?php echo $FANNIE_URL; ?>src/jquery/js/jquery.js">
</script>
<script>

var loading = 0;                    // signal that loading should be shown
var lock = 0;                       // lock (for synchronization)
var formstext = "";                 // reponse text stored globally
                                    // makes pseudo-threading easier
var lastaction;			    // the last action send

/* sends a request for the given action
   designed to call this page with arguments as HTTP GETs */
function sndReq(action) {
	var actions = action.split('&'); 
	lastaction = actions[0];
    
	$.ajax({
		url: 'overshort.php',
		type: 'post',
		cache: false,
		data: 'action='+action,
		success: handleResponse
	});
}

/* handler function to catch AJAX responses
   turns off loading and store the reponse text globally
   so that the setFormsText function can set the response
   text as soon as the loading animation stops */
function handleResponse(response) {
	switch(lastaction){
        case 'date':
		loading = 0;
		formstext = response;
		setFormsText();
		break;
        case 'save':
		if (response == "saved")
			alert('Data saved successfully');
		else
			alert(response);
		  break;
        }
}

/* waits for the loading function to release the lock,
   then sets the reponse text in place */
function setFormsText(){
  if (!lock) 
    $("#forms").html(formstext);
  else
    setTimeout('setFormsText()',50)
}

/* the 'main' function, essentially
   this is called when a date is submitted
   the datefield is cleared (so the calendar script will work again correctly)
   the Loading display is initialized, loading flag set, and lock taken
   the global response text is also cleared
   both the loading animation and request are started */
function setdate(){
	var date = $('#date').val();
	$('#date').val('');
 
	$("#forms").html("<span id=\"loading\">Loading</span>");
	loading = 1;
	lock = 1;
	formstext = "";
	sndReq('date&arg='+date); // additonal args added HTTP GET style
	loadingBar();
}

/* the loading animation
   appends periods to the Loading display
   releases the lock when loading stops */
function loadingBar(){
	if (loading){
		var text = $("#loading").html();
		if (text == "Loading.......")
			text = "Loading";
		else
			text = text+".";
		$("#loading").html(text);
		setTimeout('loadingBar()',100);
	}
	else {
		lock = 0;
	}
}

function calcOS(type,empID){
	var dlogAmt = document.getElementById('dlog'+type+empID).innerHTML;
	var countAmt = document.getElementById('count'+type+empID).value;
	
	if (countAmt.indexOf('+') != -1){
		var temp = countAmt.split('+');
		var countAmt = 0;
		for (var i = 0; i < temp.length; i++){
			countAmt += Number(temp[i]);
		}
		document.getElementById('count'+type+empID).value = Math.round(countAmt*100)/100;
	}
	
	var extraAmt = 0;
	if (type == 'Cash'){
		extraAmt = document.getElementById('startingCash'+empID).value;

		if (extraAmt.indexOf('+') != -1){
			var temp = extraAmt.split('+');
			var extraAmt = 0;
			for (var i = 0; i < temp.length; i++){
				extraAmt += Number(temp[i]);
			}
			document.getElementById('startingCash'+empID).value = Math.round(extraAmt*100)/100;
		}
	}
	
	var diff = Math.round((countAmt - dlogAmt - extraAmt)*100)/100;
	
	document.getElementById('os'+type+empID).innerHTML = diff;
	document.getElementById('os'+type+empID+'Hidden').value = diff;
	
	resum(type);
	cashierResum(empID);
}

function resum(type){
	var counts = document.getElementsByName('count'+type);
	var countSum = 0;
	for (var i = 0; i < counts.length; i++)
		countSum += Number(counts.item(i).value);

	if (type == 'Cash'){
		var startAmts = document.getElementsByName('startingCash');
		for (var i = 0; i < startAmts.length; i++)
			countSum -= Number(startAmts.item(i).value);
	}
	
	var osSum = 0;
	var oses = document.getElementsByName('os'+type+'Hidden');
	for (var i = 0; i < oses.length; i++)
		osSum += Number(oses.item(i).value);
		
	var oldcount = Number(document.getElementById('count'+type+'Total').innerHTML);
	var oldOS = Number(document.getElementById('os'+type+'Total').innerHTML);
	var newcount = Math.round(countSum*100)/100;
	var newOS = Math.round(osSum*100)/100;

	document.getElementById('count'+type+'Total').innerHTML = newcount;
	document.getElementById('os'+type+'Total').innerHTML = newOS;

	var overallCount = Number(document.getElementById('overallCountTotal').innerHTML);
	var overallOS = Number(document.getElementById('overallOSTotal').innerHTML);

	var newOverallCount = overallCount + (newcount - oldcount);
	var newOverallOS = overallOS + (newOS - oldOS);

	document.getElementById('overallCountTotal').innerHTML = Math.round(newOverallCount*100)/100;
	document.getElementById('overallOSTotal').innerHTML = Math.round(newOverallOS*100)/100;

}

function cashierResum(empID){
	var countSum = 0;
	countSum -= Number(document.getElementById('startingCash'+empID).value);
	var osSum = 0;
	var types = Array('Cash','Check','Credit','MI','TC','GD','EF','EC','CP','IC','SC'); 
	for (var i = 0; i < types.length; i++){
		//alert(types[i]+empID);
		countSum += Number(document.getElementById('count'+types[i]+empID).value);
		osSum += Number(document.getElementById('os'+types[i]+empID+'Hidden').value);
	}
	document.getElementById('countTotal'+empID).innerHTML = Math.round(countSum*100)/100;
	document.getElementById('osTotal'+empID).innerHTML = Math.round(osSum*100)/100;
}

function save(){
	var outstr = '';
	var notes = '';
	var emp_nos = document.getElementsByName('cashier');
	for (var i = 0; i < emp_nos.length; i++){
		var emp_no = emp_nos.item(i).value;
		outstr += emp_no+":";
		
		var startcash = document.getElementById('startingCash'+emp_no).value;
		outstr += "SCA|"+startcash+";";
		
		var cash = document.getElementById('countCash'+emp_no).value;
		outstr += "CA|"+cash+";";
		
		var check = document.getElementById('countCheck'+emp_no).value;
		outstr += "CK|"+check+";";
		
		var credit = document.getElementById('countCredit'+emp_no).value;
		outstr += "CC|"+credit+";";
		
		var mi = document.getElementById('countMI'+emp_no).value;
		outstr += "MI|"+mi+";";
		
		var tc = document.getElementById('countTC'+emp_no).value;
		outstr += "TC|"+tc+";";
		
		var gd = document.getElementById('countGD'+emp_no).value;
		outstr += "GD|"+gd+";";

		var ef = document.getElementById('countEF'+emp_no).value;
		outstr += "EF|"+ef+";";

		var ec = document.getElementById('countEC'+emp_no).value;
		outstr += "EC|"+ec+";";

		var cp = document.getElementById('countCP'+emp_no).value;
		outstr += "CP|"+cp+";";
		
		var ic = document.getElementById('countIC'+emp_no).value;
		outstr += "IC|"+ic+";";

		var sc = document.getElementById('countSC'+emp_no).value;
		outstr += "SC|"+sc;

		var note = document.getElementById('note'+emp_no).value;
		notes += emp_no + "|" + escape(note);
		outstr += ",";
		notes += "`";
	}
	var note = document.getElementById('totalsnote').value;
	notes += "-1|"+escape(note);
	
	var curDate = document.getElementById('currentdate').innerHTML;
	var user = document.getElementById('user').value;
	var resolved = 0;
	if (document.getElementById('resolved').checked)
		resolved = 1;

	document.getElementById('lastEditedBy').innerHTML="<b>"+user+"</b>";

	sndReq('save&curDate='+curDate+'&data='+outstr+'&user='+user+'&resolved='+resolved+'&notes='+notes);
}

</script>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
        type="text/javascript"></script>
<style>
#forms {

}

#loading {
  font-size: 125%;
  text-align: center;
}

a {
  color: blue;
}
</style>
</head>

<body>
<form onsubmit="setdate(); return false;" >
<b>Date</b>:<input type=text id=date onfocus="showCalendarControl(this);" />
<input type=submit value="Set" />
<input type=hidden id=user value="<?php echo $user ?>" />
</form>

<div id="forms">

</div>
</body>
</html>
