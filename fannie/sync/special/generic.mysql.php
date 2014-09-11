<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/*
   If all machines are on MySQL, this,
     which uses mysqldump,
   is much faster than SQLManager transfer
*/

if (!isset($FANNIE_ROOT)) {
    include(dirname(__FILE__).'/../../config.php');
}

$ret = 0;
$output = array();
if (isset($outputFormat) && $outputFormat == 'plain') {
    $itemStart = '';
    $itemEnd = '';
    $lineBreak = "\n";
} else {
    $outputFormat = 'html';
    $itemStart = '<li>';
    $itemEnd = '</li>';
    $lineBreak = '<br />';
}

if (empty($table)) {
    echo "{$itemStart}No table named. Cannot run.{$itemEnd}";
    return;
}
$tempfile = tempnam(sys_get_temp_dir(),$table.".sql");

// Make a mysqldump of the table.
$port = 3306;
$host = $FANNIE_SERVER;
if (strpos($FANNIE_SERVER, ':') > 0) {
    list($host, $port) = explode(':', $FANNIE_SERVER, 2);
}
$cmd = 'mysqldump'
    . ' -u ' . escapeshellarg($FANNIE_SERVER_USER)
    . ' -p' . escapeshellarg($FANNIE_SERVER_PW)
    . ' -h ' . escapeshellarg($host)
    . ' -P ' . escapeshellarg($port)
    . ' ' . escapeshellarg($FANNIE_OP_DB)
    . ' ' . escapeshellarg($table)
    . ' > ' . escapeshellarg($tempfile)
    . ' 2>&1';
$cmd_obfusc = 'mysqldump'
    . ' -u ' . escapeshellarg($FANNIE_SERVER_USER)
    . ' -p' . str_repeat('*', 8)
    . ' -h ' . escapeshellarg($host)
    . ' -P ' . escapeshellarg($port)
    . ' ' . escapeshellarg($FANNIE_OP_DB)
    . ' ' . escapeshellarg($table)
    . ' > ' . escapeshellarg($tempfile);
exec($cmd, $output, $ret);
if ($ret > 0) {
    $report = implode($lineBreak, $output);
    if (strlen($report) > 0) {
        $report = "{$lineBreak}$report";
    }
    echo "{$itemStart}<code style='font-weight:bold'>{$cmd_obfusc}</code> failed, returned: $ret {$report}{$itemEnd}";
} else {
    // Load the mysqldump from Fannie to each lane.
    $laneNumber=1;
    foreach ($FANNIE_LANES as $lane) {
        $ret = 0;
        $output = array();
        $lane_host = $lane['host'];
        $lane_port = 3306;
        if (strpos($lane['host'], ':') > 0) {
            list($lane_host, $lane_port) = explode(':', $lane['host'], 2);
        }
        $lane_cmd = 'mysql'
            . ' -u ' . escapeshellarg($lane['user'])
            . ' -p' . escapeshellarg($lane['pw'])
            . ' -h ' . escapeshellarg($lane_host)
            . ' -P ' . escapeshellarg($lane_port)
            . ' ' . escapeshellarg($lane['op'])
            . ' < ' . escapeshellarg($tempfile)
            . ' 2>&1';
        $lane_cmd_obfusc = 'mysql'
            . ' -u ' . escapeshellarg($lane['user'])
            . ' -p' . str_repeat('*', 8)
            . ' -h ' . escapeshellarg($lane_host)
            . ' -P ' . escapeshellarg($lane_port)
            . ' ' . escapeshellarg($lane['op'])
            . ' < ' . escapeshellarg($tempfile);
        exec($lane_cmd, $output, $ret);
        if ( $ret == 0 ) {
            echo "{$itemStart}Lane $laneNumber ({$lane['host']}) $table completed successfully{$itemEnd}";
        } else {
            $report = implode($lineBreak, $output);
            if (strlen($report) > 0) {
                $report = "{$lineBreak}$report";
            }
            echo "{$itemStart}<code style='font-weight:bold'>{$lane_cmd_obfusc}</code>{$lineBreak}Lane $laneNumber ({$lane['host']}) $table failed, returned: $ret {$report}{$itemEnd}";
        }
        unset($output);
        $laneNumber++;
    // each lane
    }
// mysqldump ok
}

unlink($tempfile);

