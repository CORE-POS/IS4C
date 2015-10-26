<?php

if (basename(__FILE__) != basename($_SERVER['PHP_SELF']) || php_sapi_name() != 'cli') {
    return;
}

$pos = false;
$val = false;
$file = false;
for ($i=1; $i<count($argv); $i+=2) {
    if ($argv[$i] == '--position' && isset($argv[$i+1])) {
        $pos = $argv[$i+1];
    } elseif ($argv[$i] == '--value' && isset($argv[$i+1])) {
        $val = $argv[$i+1];
    } elseif ($argv[$i] == '--file' && isset($argv[$i+1])) {
        $file = $argv[$i+1];
    }
}

if ($pos === false || $val === false || $file === false) {
    echo "Utility to insert column into csv\n";
    echo "Usage: csv-rewrite.php --position [index] --value [value] --file [csv file]\n";
    echo "--position, --file,  and --value required\n";
    return;
}

if (!file_exists($file)) {
    echo $file . " does not exist\n";
    return;
}

printf("Add value %s at index %d\n", $val, $pos);

$fp = fopen($file, 'r');
$buffer = array();
while ( ($line=fgetcsv($fp)) !== false) {
    $rewrite = '';
    for ($i=0; $i<count($line); $i++) {
        if ($i == $pos) {
            $rewrite .= '"' . $val . '",';
        }
        $rewrite .= '"' . $line[$i] . '",';
    }
    $rewrite = substr($rewrite, 0, strlen($rewrite)-1) . "\r\n";
    $buffer[] = $rewrite;
}
fclose($fp);

$fp = fopen($file . '.2', 'w');
foreach ($buffer as $line) {
    fwrite($fp, $line);
}
fclose($fp);
