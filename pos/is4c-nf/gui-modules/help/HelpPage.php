<?php
use COREPOS\pos\lib\MiscLib;
include_once(__DIR__ . '/../../lib/AutoLoader.php');
$jquery = MiscLib::jqueryFile();

/**
 * Utility function to download a page from a URL
 * Automatically caches the result to avoid repeat
 * downloads.
 * @param $url [string] URL to download
 * @param $opts [array] array of additional cURL
 *      options [ option_constant => value ].
 * @param $curl [resource] custom, pre-initialized
 *      cURL handle
 *
 * The two optional arguments are intended to give 
 * some flexibility around URLs that require
 * authentication. Which is more useful depends what
 * authentication option(s) the site supports.
 */
function downloadPage($url, $opts=array(), $curl=false)
{
    $fsURL = str_replace('/', '_', $url);
    if (file_exists(__DIR__ . '/.cachedURL' . $fsURL)) {
        return file_get_contents(__DIR__ . '/.cachedURL' . $fsURL);
    }
    if (!function_exists('curl_init')) {
        return '';
    }

    if ($curl) {
        $curlObj = $curl;
    } else {
        $curlObj = curl_init($url);
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curlObj, CURLOPT_FOLLOWLOCATION, true);
        foreach ($opts as $opt => $val) {
            curl_setopt($curlObj, $opt, $val);
        }
    }
    $response = curl_exec($curlObj);

    return $response ? $response : '';
}


?>
<!doctype html>
<html>
<head>
<title>Help Page</title>
<link rel="stylesheet" type="text/css" href="../../css/pos.css">
<script type="text/javascript" src="../../js/<?php echo $jquery; ?>"></script>
<script type="text/javascript">
$(document).ready(function () {
    var prevKey = 0;
    var prevPrevKey = 0;
    $(document).keyup(function (e) {
        var jsKey = e.which ? e.which : e.keyCode;
        if (jsKey == 13) {
            if ( (prevPrevKey === 99 || prevPrevKey === 67) && (prevKey === 108 || prevKey === 76) ) {
                window.location = '../../gui-modules/pos2.php';
            }
        }
        prevPrevKey = prevKey;
        prevKey = jsKey;
    });
});
</script>
</head>
<body>
<div style="margin-left: auto; margin-right: auto; text-align: center;">Press [clear] to exit</div>
<?php
if (file_exists(__DIR__ . '/defaultHelp.php')) {
    echo "<h3>Standard Information</h3>";
    include(__DIR__ . '/defaultHelp.php');
}
if (file_exists(__DIR__ . '/customHelp.php')) {
    echo "<h3>Site-Specific Information</h3>";
    include(__DIR__ . '/customHelp.php');
}
?>
</body>
</html>
