<!doctype html>
<html>
<head>
    <title>CORE-POS: Checksums</title>
<script type="text/javascript">
// avoid adding jQuery dependency
function toggleDiv(id)
{
    var elem = document.getElementById(id);
    if (elem.style.display == 'none') {
        elem.style.display = 'block';
    } else {
        elem.style.display = 'none';
    }
}
</script>
</head>
<body>
<p>
The checksums presented here are provided to compare CORE installations
and spot discrepancies. There are no defined "correct" checksums for
a given version since local edits may be present.
</p>
<?php

$common = realpath(dirname(__FILE__) . '/../common/');
$out = checksumDirectory($common, array('php'));
ksort($out);

echo '<p><a href="" onclick="toggleDiv(\'common-div\'); return false;"><b>common</b></a> - ' . $out[$common] . '</p>';
echo '<div id="common-div" style="display: none;">
    <ul>';
foreach ($out as $k => $v) {
    echo '<li>' . $k . ' - ' . $v . '</li>';
}
echo '</ul></div>';

$pos = realpath(dirname(__FILE__) . '/../pos/is4c-nf/');
$out = checksumDirectory($pos, array('php','js'));
ksort($out);

echo '<p><a href="" onclick="toggleDiv(\'pos-div\'); return false;"><b>POS</b></a> - ' . $out[$pos] . '</p>';
echo '<div id="pos-div" style="display: none;">
    <ul>';
foreach ($out as $k => $v) {
    echo '<li>' . $k . ' - ' . $v . '</li>';
}
echo '</ul></div>';

$office = realpath(dirname(__FILE__) . '/../fannie/');
$out = checksumDirectory($office, array('php','js'));
ksort($out);

echo '<p><a href="" onclick="toggleDiv(\'office-div\'); return false;"><b>Office</b></a> - ' . $out[$office] . '</p>';
echo '<div id="office-div" style="display: none;">
    <ul>';
foreach ($out as $k => $v) {
    echo '<li>' . $k . ' - ' . $v . '</li>';
}
echo '</ul></div>';

/**
  Calculate checksum recursively.
  File checksum = MD5 of file contents
  Directory checksum = MD5 of all its children's checksum 
    concatenated together
  @param $dir [string] directory path
  @param $ext [array] of extension to *include*
  @param $ret [keyed array] of paths and checksums
*/
function checksumDirectory($dir, $ext=array(), $ret=array())
{
    /**
      Read list of files and sort them alphabetically
      to ensure consistent checksums rather than
      relying on filesystem order
    */
    $dh = opendir($dir);
    $files = array();
    while (($file = readdir($dh)) !== false) {
        if ($file[0] == '.') {
            continue;
        }
        if ($file === 'noauto') {
            continue;
        }
        if ($file === 'ini.php') {
            continue;
        }
        if ($file === 'config.php') {
            continue;
        }
        $files[] = $file;
    }
    sort($files);

    $str = '';
    $sep = DIRECTORY_SEPARATOR;
    foreach ($files as $file) {
        if (is_file($dir . $sep . $file)) {
            $info = pathinfo($dir . $sep . $file);
            if (!isset($info['extension'])) {
                continue;
            } elseif (!in_array($info['extension'], $ext)) {
                continue;
            }
            $ck = checksumFile($dir . $sep . $file);
            $ret[$dir . $sep . $file] = $ck;
            $str .= $ck;
        } else {
            $ret = checksumDirectory($dir . $sep . $file, $ext, $ret);
            if (isset($ret[$dir . $sep . $file])) {
                $str .= $ret[$dir . $sep . $file];
            }
        }
    }
    if ($str !== '') {
        $ret[$dir] = md5($str);
    }
    closedir($dh);

    return $ret;
}

function checksumFile($file)
{
    return md5_file($file);
}

?>
</body>
</html>
