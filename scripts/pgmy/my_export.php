<?php

include(__DIR__ . '/../../fannie/config.php');
include(__DIR__ . '/../../fannie/classlib2.0/FannieAPI.php');

if (count($argv) != 6) {
    echo "Export MySQL table to PostgreSQL compatible text file" . PHP_EOL;
    echo "\tUsage: my_export.php [host] [username] [password] [database] [table]" . PHP_EOL;
    exit(1);
}

$dbc = new SQLManager($argv[1], 'mysqli', $argv[4], $argv[2], $argv[3]);
$res = $dbc->query("SELECT * FROM " . $dbc->identifierEscape($argv[5]));
$fp = fopen(__DIR__ . '/' . $argv[5] . '.txt', 'w');
$rows = $dbc->numRows($res);
$count = 1;
while ($row = $dbc->fetchRow($res)) {
    $num = $dbc->numFields($res);
    for ($i=0; $i<$num; $i++) {
        if ($row[$i] === null) {
            fwrite($fp, "\\N");
        } elseif ($row[$i] === '0000-00-00 00:00:00') {
            fwrite($fp, "\\N");
        } else {
            $out = str_replace("\t", "\\t", $row[$i]);
            $out = str_replace("\r", "\\r", $out);
            $out = str_replace("\n", "\\n", $out);
            fwrite($fp, $out);
        }
        if ($i < $num - 1) {
            fwrite($fp, "\t");
        } else {
            fwrite($fp, "\n");
        }
    }
    echo "{$count}/{$rows}\r";
    $count++;
}
fclose($fp);

echo PHP_EOL;
echo "Exported as {$argv[5]}" . PHP_EOL;

