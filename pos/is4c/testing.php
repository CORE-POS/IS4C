<?
$conn = mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]) or die(mysql_error());
$db = mysql_select_db($_SESSION["mDatabase"], $conn) or die(mysql_error());
?>