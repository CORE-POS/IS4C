<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto

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

/* A version of generic.mysql.php for the Coop Cred plugin.
 * Creates the Coop Cred database on lanes if needed.
 * Does no transfer to FANNIE_SERVER if the lane is there
 *  because the db is the same.
 * Like the original, this is designed to be include()ed;
 *  it needs $table and uses $outputFormat, if it exists,
 *  from the include()ing script.
 * Use of the .inc extension protects it from API scanning.
*/

if (!isset($FANNIE_ROOT))
	include(dirname(__FILE__).'/../../../../../config.php');
// function sys_get_temp_dir doesn't exist in PHP < 5.2.1
if ( !function_exists('sys_get_temp_dir')) {
  function sys_get_temp_dir() {
      if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
      if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
      if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
      $tempfile=tempnam(__FILE__,'');
      if (file_exists($tempfile)) {
          unlink($tempfile);
          return realpath(dirname($tempfile));
      }
      return null;
      }
}


$ret = 0;
$output = array();
if (isset($outputFormat) && $outputFormat == 'plain') {
	$itemStart = '';
	$itemEnd = "\n";
	$lineBreak = "\n";
}
else {
	$outputFormat = 'html';
	$itemStart = '<li>';
	$itemEnd = '</li>';
	$lineBreak = '<br />';
}

if (empty($lane_db)) {
	echo "{$itemStart}No target database named. Cannot run.{$itemEnd}";
	return;
}
if (empty($table)) {
	echo "{$itemStart}No table named. Cannot run.{$itemEnd}";
	return;
}
$tempfile = tempnam(sys_get_temp_dir(),$table.".sql");

// Make a mysqldump of the table.
$fannieConnect = "-u $FANNIE_SERVER_USER -p$FANNIE_SERVER_PW -h $FANNIE_SERVER";
exec("mysqldump $fannieConnect $FANNIE_COOP_CRED_DB $table > $tempfile",
        $output, $ret);
if ( $ret > 0 ) {
	$report = implode("$lineBreak", $output);
	if ( strlen($report) > 0 )
		$report = "{$lineBreak}$report";
	echo "{$itemStart}mysqldump failed, returned: $ret {$report}{$itemEnd}";
}
else {
	// Load the mysqldump from Fannie to each lane.
    //$laneCoopCredDb = $lane_db;
    //$laneCoopCredDb = $FANNIE_COOP_CRED_DB;
	$laneNumber=0;
	foreach($FANNIE_LANES as $lane){
        $laneNumber++;
echo "inc: one_lane: $one_lane";
        if (isset($one_lane) && $one_lane != 0 && $one_lane != $laneNumber) {
            continue;
        }
        /* Since lane and server db may not be the same this isn't needed.
        if ($lane['host'] == $FANNIE_SERVER) {
            // Message during development only.
            echo "Skipped lane on Fannie host: $FANNIE_SERVER{$lineBreak}";
            continue;
        }
         */
        $lane['op'] = $lane_db;
		if ( strpos($lane['host'], ':') > 0 ) {
			list($host, $port) = explode(":", $lane['host']);
            $laneConnect = "-u {$lane['user']} -p{$lane['pw']} -h {$host} -P {$port}";
		}
		else {
            $laneConnect = "-u {$lane['user']} -p{$lane['pw']} -h {$lane['host']}";
		}

        // Create the database if needed.
        // When more certain of this, perhaps just use "CREATE IF NOT EXISTS",
        //  but two-step is more informative for new installations.
		$ret = 0;
		$output = array();
        $statement = "SELECT schema_name FROM information_schema.schemata " .
                        "WHERE schema_name = '{$lane_db}'";
        exec("mysql $laneConnect -s -N -e \"{$statement}\" information_schema",
            $output, $ret);
		if ( $ret == 0 ) {
            if (count($output)>0) {
                $noop = 0;
                //echo "{$itemStart}Lane $laneNumber ({$lane['host']}) " .
                //       "database {$lane_db} already exists{$itemEnd}";
            } else {
                echo "{$itemStart}Lane $laneNumber ({$lane['host']}) " .
                        "database {$lane_db} does not (yet) exist{$itemEnd}";
                $ret = 0;
                $output = array();
                $statement = "CREATE DATABASE IF NOT EXISTS {$lane_db}";
                exec("mysql $laneConnect -s -N -e \"{$statement}\"",
                        $output, $ret);
                if ( $ret == 0 ) {
                    echo "{$itemStart}Lane $laneNumber ({$lane['host']}) " .
                            "created database {$lane_db}{$itemEnd}";
                } else {
                    $report = "$statement{$lineBreak}";
                    $report .= implode("$lineBreak", $output);
                    if ( strlen($report) > 0 ) {
                        $report = "{$lineBreak}$report";
                    }
                    echo "{$itemStart}Lane $laneNumber ({$lane['host']}) " .
                        "database creation failed, returned: " .
                        "$ret {$report}{$itemEnd}";
                }
            }
		} else {
			$report = "$statement{$lineBreak}";
			$report .= implode("$lineBreak", $output);
			if ( strlen($report) > 0 ) {
				$report = "{$lineBreak}$report";
            }
            echo "{$itemStart}Lane $laneNumber ({$lane['host']}) " .
                    "check for database failed, returned: " .
                    "$ret {$report}{$itemEnd}";
		}

        // The table
		$ret = 0;
		$output = array();
        exec("mysql $laneConnect {$lane['op']} < $tempfile", $output, $ret);
		if ( $ret == 0 ) {
            echo "{$itemStart}Lane $laneNumber ({$lane['host']}) " .
                    "$table completed successfully{$itemEnd}";
		} else {
			$report = implode("$lineBreak", $output);
			if ( strlen($report) > 0 )
				$report = "{$lineBreak}$report";
            echo "{$itemStart}Lane $laneNumber ({$lane['host']}) " .
                    "$table failed, returned: $ret {$report}{$itemEnd}";
		}
		unset($output);
		//formerly here $laneNumber++;
	// each lane
	}
// mysqldump ok
}

unlink($tempfile);

?>
