<?php
/*
require('../../auth/login.php');

if (!validateUserQuiet('delimenu')){
  header('Location: /auth/ui/loginform.php?redirect=/it/delimenu/');
  return;
}
*/

include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");

include($FANNIE_ROOT.'src/Credentials/OutsideDB.data1.wfc.php');

if (isset($_POST["dayname1"])){

for ($i = 1; $i <= 14; $i++){
	$dayname = $_POST["dayname".$i];
	$menu = preg_replace("/\n/","<br />",$_POST["menu".$i]);
	$menu = preg_replace("/\r/","",$menu);
	$menu = addslashes($menu);
 
	//echo "<pre>".$menu."</pre><br />";
	
	$upQ = "update delimenu set dayname='$dayname', menu='$menu' where day=$i";
	//echo $upQ."<br />";
	$upR = $sql->query($upQ);
}

}

?>

<html>
<head><title>Edit the menu page</title></head>
<body bgcolor="#CCCCCC">
<b>This week</b>:

<?php
$ts = time();
while(date('w',$ts) != 0)
	$ts -= 86400;

$fetchQ = "select menu from delimenu order by day";
$fetchR = $sql->query($fetchQ);

echo "<form action=index.php method=post>";
echo "<table border=1 cellspacing=0 cellpadding=3>";
echo "<tr>";
for ($i = 0; $i < 7; $i++){
	echo "<td width=90>".date('M j',$ts)."</td>";
	$id = $i+1;
	echo "<input type=hidden name=dayname$id value=\"".date('M j',$ts)."\" />";
	$ts += 86400;
}
echo "</tr>";
echo "<tr>";
for ($i = 0; $i < 7; $i++){
	echo "<td width=90>";
	$id = $i+1;
	echo "<textarea rows=10 cols=9 name=menu$id>";
	echo preg_replace("/<br \/>/","\n",stripslashes(array_pop($sql->fetch_array($fetchR))));
	echo "</textarea>";
	echo "</td>";
}
echo "</tr>";
echo "</table><br />";

echo "<b>Next week</b>:";
echo "<table border=1 cellspacing=0 cellpadding=3>";
echo "<tr>";
for ($i = 0; $i < 7; $i++){
	echo "<td width=90>".date('M j',$ts)."</td>";
	$id = $i+8;
	echo "<input type=hidden name=dayname$id value=\"".date('M j',$ts)."\" />";
	$ts += 86400;
}
echo "</tr>";
echo "<tr>";
for ($i = 0; $i < 7; $i++){
	echo "<td width=90>";
	$id = $i+8;
	echo "<textarea rows=10 cols=9 name=menu$id>";
	echo preg_replace("/<br \/>/","\n",stripslashes(array_pop($sql->fetch_array($fetchR))));
	echo "</textarea>";
	echo "</td>";
}
echo "</tr>";
echo "</table><br />";
echo "<input type=Submit value=Save />";
echo "</form>";
?>
</body>

</html>
