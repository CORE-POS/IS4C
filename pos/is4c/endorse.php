<body bgcolor='#ffffff'>
<?
include_once("ini/ini.php");
include_once("session.php");
include_once("printLib.php");
include_once("printReceipt.php");
include_once("connect.php");
include_once("prehkeys.php");


$endorseType = $_SESSION["endorseType"];

if (strlen($endorseType) > 0) {
	$_SESSION["endorseType"] = "";

	switch ($endorseType) {

		case "check":
			frank();
			break;

		case "giftcert":
			frankgiftcert();
			break;

		case "stock":
			frankstock();
			break;

		case "classreg":
			frankclassreg();
			break;

		default:
			break;
	}
}
?>
</body>