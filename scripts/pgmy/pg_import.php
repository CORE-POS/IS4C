<?php

include(__DIR__ . '/../../fannie/config.php');
include(__DIR__ . '/../../fannie/classlib2.0/FannieAPI.php');

if (count($argv) != 6) {
    echo "Import table into PostgreSQL from text file" . PHP_EOL;
    echo "\tUsage: pg_import.php [host] [username] [password] [schema] [table]" . PHP_EOL;
    exit(1);
}

if (!file_exists($argv[5] . '.txt')) {
    echo "Missing file {$argv[5]}.txt" . PHP_EOL;
    exit(1);
}

$cmd = 
    'PGPASSWORD=' . escapeshellarg($argv[3])
    . ' psql -U ' . escapeshellarg($argv[2])
    . ' -h ' . escapeshellarg($argv[1])
    . ' -c "\copy ' . $argv[4] . '.' . $argv[5] . ' from \'' . $argv[5] . '.txt\'"';

echo $cmd . PHP_EOL;
passthru($cmd);

