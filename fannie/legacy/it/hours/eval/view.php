<?php
include('../../../../config.php');

require($FANNIE_ROOT.'auth/login.php');
include('../db.php');

$name = checkLogin();
$perm = validateUserQuiet('evals');
if ($name === false && $perm === false){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/eval/list.php");
	exit;
}
else if ($perm === false){
	echo "Error";
	exit;
}

if (isset($_REQUEST['getAddForm'])){
	echo addForm();
	exit;
}
else if (isset($_REQUEST['getCommentForm'])){
	echo commentForm();
	exit;
}
elseif (isset($_REQUEST['addEntry'])){
	$db = hours_dbconnect();
	$empID = $_REQUEST['id'];
	$month = $_REQUEST['month'];
	$year = $_REQUEST['year'];
	$type = $_REQUEST['type'];
	$pos = mysql_real_escape_string($_REQUEST['pos']);
	$score = $_REQUEST['score'];	
	$score = sprintf("%.2f",$score);
	$score = str_replace(".","",$score);	

	$q = $db->prepare("INSERT INTO evalScores (empID,evalType,evalScore,month,year,pos)
		VALUES (?,?,?,?,?,?)");
	$r = $db->execute($q, array($empID, $type, $score, $month, $year, $pos));

	echo getHistory($empID);
	exit;
}
elseif (isset($_REQUEST['addComment'])){
	$db = hours_dbconnect();
	$empID = $_REQUEST['id'];
	$user = $_REQUEST['user'];
	$comment = $_REQUEST['comment'];

	$q = $db->prepare("INSERT INTO evalComments(empID,comment,stamp,user,deleted) VALUES
		(?,?,now(),?,0)");
	$r = $db->execute($q, array($empID, $comment, $user));

	echo getComments($empID);
	exit;
}
elseif (isset($_REQUEST['deleteComment'])){
	$db = hours_dbconnect();
	$q = $db->prepare("UPDATE evalComments SET deleted=1 WHERE id=?");
	$r = $db->execute($q, array($_REQUEST['deleteComment']));
	echo getComments($_REQUEST['empID']);
	exit;
}
elseif (isset($_REQUEST['delEntry'])){
	$entryID = sprintf("%d",$_REQUEST['delEntry']);
	$empID = sprintf("%d",$_REQUEST['empID']);
	$db = hours_dbconnect();
	$q = $db->prepare("DELETE FROM evalScores WHERE id=? AND empID=?");
	$db->execute($q, array($entryID, $empID));

	echo getHistory($empID);
	exit;
}
elseif (isset($_REQUEST['saveInfo'])){
	$id = $_REQUEST['id'];
	$month = isset($_REQUEST['month'])?$_REQUEST['month']:'';
	$year = isset($_REQUEST['year'])?$_REQUEST['year']:'';
	$pos = $_REQUEST['pos'];
	$date = "null";
	if (!empty($month) && !empty($year)){
		$date = $year."-".str_pad($month,2,'0',STR_PAD_LEFT)."-01";
	}
	$db = hours_dbconnect();
	$hire = isset($_REQUEST['hire'])?$_REQUEST['hire']:'';
	if (strstr($hire,"/") !== False){
		$tmp = explode("/",$hire);
		if (count($tmp)==3)
			$hire = $tmp[2]."-".$tmp[0]."-".$tmp[1];
		else
			$hire = '';
	}
	$etype = $_REQUEST['etype'];
	
	$delQ = $db->prepare("DELETE FROM evalInfo WHERE empID=?");
	$insQ = $db->prepare("INSERT evalInfo VALUES (?,?,?,?,?)");
	$db->execute($delQ, array($id));
	$db->execute($insQ, array($id, $pos, $date, $hire, $etype));
	echo "Info saved\nPositions: $pos\nNext Eval: ".trim($date,"'")."\nHire: ".trim($hire,"'");
	exit;
}

if (!isset($_REQUEST['id'])){
	echo "Error: no employee selected";
	exit;
}

$empID = sprintf("%d",$_REQUEST['id']);

function getHistory($id){
	$db = hours_dbconnect();

	$q = $db->prepare("SELECT e.month,e.year,t.title,e.evalScore,e.pos,e.id
		FROM evalScores AS e LEFT JOIN EvalTypes AS t
		ON e.evalType = t.id
		WHERE e.empID=?
		ORDER BY e.year DESC, e.month DESC");
	$r = $db->execute($q, array($id));
	$ret = "<table cellspacing=0 cellpadding=4 border=1>";
	$ret .= "<tr><th>Date</th><th>Type</th><th>Score</th><th>Position</th></tr>";
	while($w = $db->fetch_row($r)){
		$score = str_pad($w[3],3,'0');
		$score = substr($score,0,strlen($score)-2).".".substr($score,-2);
		$ret .= sprintf("<tr><td>%s</td><td>%s</td><td>%.2f</td><td>%s</td>
				<td><a href=\"\" onclick=\"return delEntry(%d);\">[ X ]</a></tr>",
			date("F Y",mktime(0,0,0,$w[0],1,$w[1])),
			$w[2],
			$score,
			$w[4],$w[5]);
	}
	$ret .= "</table>";
	return $ret;
}

function getComments($id){
	$ret = "";

	$db = hours_dbconnect();
	$q = $db->prepare("SELECT stamp,user,comment,id FROM evalComments WHERE empID=? AND deleted=0 ORDER BY stamp DESC");
	$r = $db->execute($q, array($id));
	while($w = $db->fetch_row($r)){
		$ret .= sprintf('<div class="cHeader">%s - %s
				<a href="" onclick="deleteComment(%d);return false;">[delete]</a></div>
				<div class="cBody">%s</div>',
				$w['stamp'],$w['user'],$w['id'],
				str_replace("\n","<br />",$w['comment']));
	}
	return $ret;
}

function empInfo($id){
	$db = hours_dbconnect();
	$ret = "<table cellspacing=0 cellpadding=4 border=1>";
	$q = $db->prepare("SELECT e.name,i.positions,i.nextEval,i.hireDate,i.nextTypeID FROM employees as e
		left join evalInfo as i on e.empID=i.empID
		WHERE e.empID=?");
	$r = $db->execute($q, array($id));
	$w = $db->fetch_row($r);
	$ret .= "<tr><th>Name</th><td colspan=2>$w[0]</td></tr>";
	$ret .= "<tr><th>Position(s)</th><td colspan=2><input type=text id=\"empPositions\" value=\"$w[1]\" /></td></tr>";
	$tmp = explode("-",$w[3]);
	if (count($tmp) == 3)
		$w[3] = $tmp[1]."/".$tmp[2]."/".$tmp[0];
	$ret .= "<tr><th>Hire Date</th><td colspan=2><input type=text id=\"hireDate\" value=\"$w[3]\" 
			onclick=\"\" /></td></tr>";
	$ret .= "<tr><th>Next Eval</th><td><select id=nextMonth><option value=\"\"></option>";
	$tmp = explode("-",$w[2]);
	$month = "";
	$year = "";
	if (is_array($tmp) && count($tmp) == 3){
		$month = $tmp[1];
		$year = $tmp[0];
	}
	for($i=1;$i<=12;$i++){
		$ret .= sprintf("<option value=%d %s>%s</option>",
			$i,($i==$month?'selected':''),
			date("F",mktime(0,0,0,$i,1,2000)));
	}
	$ret .= "</select>";
	$ret .= "<input type=text id=nextYear size=4 value=\"$year\" /></td>";
	$ret .= "<td><select name=etype id=etype><option value=\"\"></option>";
	$et = $w[4];
	$q = "SELECT id,title FROM EvalTypes ORDER BY id";
	$r = $db->query($q);
	while($w = $db->fetch_row($r))
		$ret .= sprintf("<option %s value=\"%d\">%s</option>",($w[0]==$et?'selected':''),$w[0],$w[1]);
	$ret .= "</select></td>";
	$ret .= "</tr>";
	$ret .= "</table>";
	$ret .= "<div style=\"margin-top:5px;\"><input type=submit id=saveButton value=\"Save Changes\" /></div>";
	return $ret;
}

function addForm(){
	$ret = "<table>";
	$ret .= "<tr>";
	$ret .= "<td><select id=addmonth>";
	for($i=1;$i<=12;$i++){
		$ret .= sprintf("<option value=%d %s>%s</option>",
			$i,(date('n')==$i?'selected':''),
			date('F',mktime(0,0,0,$i,1,2000)));
	}
	$ret .= "</select></td>";
	$ret .= "<td><input type=text size=4 id=addyear value=";
	if (date('n')==12) $ret .= (date('Y')+1);	
	else $ret .= date('Y');
	$ret .= " /></td>";
	$ret .= "<td><select id=addtype>";
	$db = hours_dbconnect();
	$q = "SELECT id,title FROM EvalTypes";
	$r = $db->query($q);
	while($w = $db->fetch_row($r)){
		$ret .= "<option value=$w[0]>$w[1]</option>";
	}
	$ret .= "</select></td>";

	$ret .= "<th>Score</th>";
	$ret .= "<td><input type=text size=3 id=addscore /></td>";

	$ret .= "<th>Pos.</th>";
	$ret .= "<td><input type=text size=18 id=addpos value=Primary /></td>";

	$ret .= "<td><input type=submit id=addsub value=Add /></td>";

	$ret .= "</tr></table>";
	return $ret;
}

function commentForm(){
	$ret = "<textarea id=newcomment rows=10 cols=50></textarea>";
	$ret .= "<p />";
	$ret .= "<input type=submit value=\"Save Comment\" onclick=\"saveComment();\" />";
	return $ret;
}

?>
<html>
<head>
<link href="<?php echo $FANNIE_URL; ?>src/style.css" rel="stylesheet" type="text/css">
<link href="<?php echo $FANNIE_URL; ?>src/javascript/jquery-ui.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="<?php echo $FANNIE_URL; ?>src/javascript/jquery.js"></script>
<script type="text/javascript" src="<?php echo $FANNIE_URL; ?>src/javascript/jquery-ui.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	$('#addbutton').click(showAddForm);
	$('#saveButton').click(saveEmpInfo);
	$('#commentbutton').click(showCommentForm);
});

function saveEmpInfo(){
	var dstr = "saveInfo=save&id="+$('#empID').val();
	dstr += "&pos="+$('#empPositions').val();
	dstr += "&month="+$('#nextMonth').val();
	dstr += "&year="+$('#nextYear').val();
	dstr += "&hire="+$('#hireDate').val();
	dstr += "&etype="+$('#etype').val();
	$.ajax({url: 'view.php',
		type: 'post',
		data: dstr,
		success: function(data){
			alert(data);
		}
	});
}

function showAddForm(){
	$.ajax({url: 'view.php',
		type: 'post',
		data: 'getAddForm=yes',
		success: function(data){
			$('#workspace').html(data);
			$('#addsub').click(addEntry);	
			$('#addpos').val($('#empPositions').val());
			$('#addmonth').focus();
		}
	});	
}

function showCommentForm(){
	$.ajax({url: 'view.php',
		type: 'post',
		data: 'getCommentForm=yes',
		success: function(data){
			$('#cform').html(data);
			$('#newcomment').focus();
		}
	});
}

function saveComment(){
	var dstr = "addComment=yes&id="+$('#empID').val();
	dstr += "&user="+$('#username').val();
	dstr += "&comment="+escape($('#newcomment').val());
	
	$.ajax({url:'view.php',
		type: 'post',
		data: dstr,
		success: function(data){
			$('#cform').html('');
			$('#commentfs').html(data);
		}
	});
}

function addEntry(){
	var dstr = "addEntry=add&id="+$('#empID').val();
	dstr += "&month="+$('#addmonth').val();	
	dstr += "&year="+$('#addyear').val();	
	dstr += "&type="+$('#addtype').val();	
	dstr += "&score="+$('#addscore').val();	
	dstr += "&pos="+$('#addpos').val();	

	$.ajax({url: 'view.php',
		type: 'post',
		data: dstr,
		success: function(data){
			$('#historyfs').html(data);
			$('#workspace').html('');
		}
	});
}

function delEntry(id){
	if (!confirm("Delete this eval score")) return false;

	$.ajax({url: 'view.php',
		type: 'post',
		data: 'delEntry='+id+'&empID='+$('#empID').val(),
		success: function(data){
			$('#historyfs').html(data);
		}
	});

	return false;
}
function deleteComment(id){
	if (!confirm("Delete this comment")) return false;

	$.ajax({url: 'view.php',
		type: 'post',
		data: 'deleteComment='+id+'&empID='+$('#empID').val(),
		success: function(data){
			$('#commentfs').html(data);
		}
	});
	return false;
}
</script>
<style type="text/css">
.cHeader {
	font-style: italic;
	font-weight: bold;
	font-size: 110%;
}
.cHeader a {
	font-style: normal;
	font-weight: normal;
	font-size: 90%;
}
.cBody {
	margin-left:2em;
	border-bottom: dashed 1px black;
	margin-bottom: 1em;
	padding: .5em;
}
</style>
</head>
<body>
<input type=submit onclick="location='list.php';" value="Back To Employee List" />

<fieldset><legend>Employee</legend>
<div id="empfs">
<?php echo empInfo($empID); ?>
</div>
</fieldset>
<hr />

<input type=submit value="Add Eval" id="addbutton" />
<div id="workspace"> 
</div>

<fieldset><legend>History</legend>
<div id="historyfs">
<?php echo getHistory($empID); ?>
</div>
</fieldset>

<hr />
<input type=submit value="Add Comment" id="commentbutton" />
<div id="cform"> 
</div>
<fieldset><legend>Comments</legend>
<div id="commentfs">
<?php echo getComments($empID); ?>
</div>
</fieldset>

<input type=hidden id=empID value=<?php echo $empID; ?> />
<input type=hidden id=username value="<?php echo $name; ?>" />

<input type=submit onclick="location='list.php';" value="Back To Employee List" />

</body>
</html>
