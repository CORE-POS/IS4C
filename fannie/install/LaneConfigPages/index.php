<!doctype html>
<html>
<head>
    <title>Edit Lane Parameters</title>
    <script type="text/javascript" src="../../src/javascript/jquery.js">
    </script>
</head>
<body onload="console.log($(this));">
<p>
You are editing a global lane configuration. Errors about writable ini.php files, or writable files in general,
can safely be ignored. Settings are saved in the parameters table which can be synced with lanes like any
other operational database table.
</p>
<iframe width="100%" 
    style="width:100%;" src="extra_directory_layer/index.php"
    onload="$(this).height($(this).contents().height());console.log($(this).height());" >
</iframe>
</body>
</html>
