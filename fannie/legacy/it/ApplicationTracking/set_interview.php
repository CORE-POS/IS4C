<?php

include('../../../config.php');
require('db.php');
require($FANNIE_ROOT.'auth/login.php');
$sql = db_connect();

// handle a bit of interview update/processing for
// other pages
if (isset($_GET['action'])){
	$id = $_GET['id'];
	$prep = $sql->prepare("select appID from interviews where interviewID=?");
	$appID = array_pop($sql->fetch_row($sql->execute($prep, array($id))));
	switch($_GET['action']){
	case 'fin':
		$prep = $sql->prepare("UPDATE interviews SET took_place=1 WHERE interviewID=?");
        $sql->execute($prep, $id);
		break;
	case 'reg':
		$sql->prepare("UPDATE interviews SET sent_regret=1 WHERE interviewID=?");
        $sql->execute($prep, $id);
		break;
	case 'del':
		$sql->prepare("DELETE FROM interviews WHERE interviewID=?");
        $sql->execute($prep, $id);
		break;
	}
	header("Location: {$FANNIE_URL}legacy/it/ApplicationTracking/view.php?appID=$appID");
	return;
}

if (isset($_POST['submit'])){

	$appID = $_POST['appID'];
	$username = $_POST['username'];
	$date = $_POST['date'];
	
	$insQ = $sql->prepare("INSERT INTO interviews (scheduled, appID, sent_regret, username,took_place) VALUES
		(?,?,0,?,0)");
	$insR = $sql->execute($insQ, array($date, $appID, $username));

	header("Location: {$FANNIE_URL}legacy/it/ApplicationTracking/view.php?appID=$appID");
	return;

}
else {

$appID = $_GET['appID'];
$nameQ = $sql->prepare("select concat(first_name,' ',last_name) from applicants where appID=?");
$name = array_pop($sql->fetch_row($sql->execute($nameQ, array($appID))));
$username = validateUserQuiet('apptracking',0);
if (!$username){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/ApplicationTracking/set_interview.php?appID=$appID");
	return;
}
refreshSession();

?>

<html>
<head>
	<title>Schedule an interview</title>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL; ?>src/javascript/jquery.js"
        language="javascript"></script>
<script src="<?php echo $FANNIE_URL; ?>src/javascript/jquery-ui.js"
        language="javascript"></script>
<link href="<?php echo $FANNIE_URL; ?>src/javascript/jquery-ui.css"
      rel="stylesheet" type="text/css">
<script type="text/javascript">
$(document).ready(function(){
    $('#date').datepicker();
});
</script>
</head>
<body>
<form action=set_interview.php method=post>
<span style="font-size: 125%;">
<?php echo $username ?> will interview <?php echo $name ?> on 
<input type=text name=date id="date" /><br />
<input type=hidden name=appID value="<?php echo $appID ?>" />
<input type=hidden name=username value="<?php echo $username ?>" />
<input type=submit name='submit' value="Schedule Interview" /> 
<input type=submit value="Cancel &amp; Go Back" onclick="top.location='view.php?appID=<?php echo $appID ?>'; return false;" />
</span> 
</form>
</body>
</html>

<?php
}
?>
