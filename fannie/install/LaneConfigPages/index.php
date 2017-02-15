<?php
include_once(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}
$includes = '
    <head>
    <link rel="stylesheet" type="text/css" href="../../src/javascript/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../../src/javascript/bootstrap-default/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../../src/javascript/bootstrap-default/css/bootstrap-theme.min.css">
    <script type="text/javascript" src="../../src/javascript/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="../../src/javascript/bootstrap/js/bootstrap.min.js"></script>
    ';
$header = 'Lane Configuration';
ob_start();
include(dirname(__FILE__) . '/../../src/header.bootstrap.html');
$page = ob_get_clean();
echo str_replace('<head>', $includes, $page);
?>
<p>
You are editing a global lane configuration. Errors about writable ini.php files, or writable files in general,
can safely be ignored. Settings are saved in the parameters table which can be synced with lanes like any
other operational database table.
</p>
<p>
<p>
<a href="../../admin/ReceiptText/LaneTextStringPage.php" class="btn btn-default">Edit Text Strings</a> for receipt headers &amp; footers,
on screen messages, charge slips, and check endorsing.
</p>
<p>
<a href="../../admin/LaneParameters/LaneParametersEditor.php" class="btn btn-default">Edit Parameters Directly</a>
</p>
<iframe width="100%" 
    style="width:100%;height:500px;" src="extra_directory_layer/index.php"
    title="Embedded Lane Config"
    onload="$(this).height($(this).contents().height());console.log($(this).height());" >
</iframe>
</div>
</div>
</div>
</body>
</html>
