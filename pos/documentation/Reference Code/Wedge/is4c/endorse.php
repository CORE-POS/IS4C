<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head></head>
    <body bgcolor='#ffffff'>
        <?php
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
</html>

