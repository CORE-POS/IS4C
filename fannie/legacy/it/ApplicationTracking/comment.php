<?php

include('../../../config.php');
require($FANNIE_ROOT.'auth/login.php');
require('db.php');
$sql = db_connect();

if (isset($_POST['submit'])){
	$appID = $_POST['appID'];
	$noteID = $_POST['noteID'];
	$note_date = $_POST['note_date'];
	$username = $_POST['username'];
	$note_text = $_POST['note_text'];

	$note_text = str_replace("\r","",$note_text);
	$note_text = str_replace("\n","<br />",$note_text);
	$note_text = str_replace("'","\\'",$note_text);

	if ($noteID == -1){
		$insQ = "INSERT INTO notes (appID,note_date,note_text,username) VALUES
			($appID,'$note_date','$note_text','$username')";
		$sql->query($insQ);
	}
	else {
		$upQ = "UPDATE notes SET note_text='$note_text' WHERE noteID=$noteID";
		$sql->query($upQ);
	}

	header("Location: /it/ApplicationTracking/view.php?appID=$appID");
	return;
}

$id = $_GET['id'];
$username = validateUserQuiet('apptracking',0);
if (!$username){
	header("Location: {$FANNIE_RUL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/ApplicationTracking/comment.php?id=$id");
	return;
}
refreshSession();

$temp = explode(":",$id);
$noteID = $temp[0];
$appID = $temp[1];

$note_text = "";
$note_date = date("Y-m-d");

if ($noteID != -1){
	$dataQ = "SELECT note_text,note_date FROM notes WHERE noteID=$noteID";
	$dataR = $sql->query($dataQ);
	$dataW = $sql->fetch_row($dataR);
	
	$note_date = $dataW[1];
	$note_text = str_replace("\\'","'",$dataW[0]);
	$note_text = str_replace("<br />","\n",$note_text);
}

?>
<html>
<head>
	<title>Comment</title>
</head>
<body>

<form action=comment.php method=post>
<span style="font-size: 125%">Comment by <?php echo $username ?></span><br />
Date: <?php echo $note_date ?><br />
<textarea name=note_text rows=20 cols=60><?php echo $note_text ?></textarea><br />
<input type=hidden name=appID value="<?php echo $appID ?>" />
<input type=hidden name=noteID value="<?php echo $noteID ?>" />
<input type=hidden name=username value="<?php echo $username ?>" />
<input type=hidden name=note_date value="<?php echo $note_date ?>" />
<input type=submit name=submit value="<?php echo ($noteID==-1)?'Add a comment':'Edit your comment'; ?>" />
<input type=submit value="Cancel &amp; Go Back" onclick="top.location='view.php?appID=<?php echo $appID ?>'; return false;" />
</body>
</html>
