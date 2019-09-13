<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<script type="text/javascript" src="jquery.min.js"></script>
</head>
<body>
</body>
<iframe id="myiframe" src="getReceiptText.php" height="500px;" width="500px" style="border: 0px solid transparent;"></iframe>
</html>
<script type="text/javascript">
$(document).ready(function() {
    var interval = setInterval('reloadIframe()', 1000);
});
function reloadIframe()
{
    $('#myiframe').attr('src', 'getReceiptText.php');
}
</script>
