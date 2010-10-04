<?php
require_once("../src/mysql_connect.php");

/////////  O P T I O N S 	//////////////////
//	Pick a year	
//	(leave blank for current)
$year = "";								
if (!$year) {$year = date('Y');}				
//	Options for reporting:
//		## == enter a week tag number
//		YY == output entire year (slow!)
//		(leave blank for current output)
$week_tag = "05";	
//	format datetime	
$timestamp = date('Y-m-d H:i:s');
//	specify a log file to direct stdout
$log_file = '/var/log/spins.log';
//	Which SPINS table will we use?
$SPINS = "SPINS_" . $year;
//	Which dlog archive will we use?	
$table = "dlog_" . $year;
//	Directory to put .csv files into
//	(make sure this already exists)	
$outpath = "/pos/SPINS/exp/" . $year . "/";
//	filename prefix (incl _wk)
$prefix = "pfcp_wk";	
///////////////////////////////////////////////

if (is_numeric($week_tag) || !$week_tag) {

	//	Get start_ and end_date info
	if (!$week_tag) {
		$query = "SELECT * FROM is4c_log.$SPINS
			WHERE end_date < CURDATE()
			ORDER BY week_tag DESC LIMIT 1";
	} else {
		$query = "SELECT * FROM is4c_log.$SPINS
			WHERE week_tag = $week_tag";	
	}

	$result = mysql_query($query);
	$row = mysql_fetch_assoc($result);
	//	fill vars to use in main query
	$start_date = $row['start_date'];
	$end_date = $row['end_date'];
	$tag = str_pad($row['week_tag'], 2, "0", STR_PAD_LEFT);

	//	Echo the matched week data
	error_log("[$timestamp] -- Week tag #$tag selected.  \$start_date = $start_date. \$end_date = $end_date\n",3,$log_file);

	//	Specify /path/to/file and filename
	$outfile = $outpath . $prefix . $tag . ".csv";
	error_log("[$timestamp] -- File path and name set.  \$outfile = $outfile\n",3,$log_file);

	//	free result resources
	mysql_free_result($result);

	//	The main query
	$query = "SELECT upc, description, SUM(quantity) AS qty, SUM(total) AS total
		FROM is4c_log.$table
		WHERE DATE(datetime) BETWEEN '$start_date' AND '$end_date'
		AND upc > 99999 AND scale = 0 
		AND emp_no <> 9999 AND trans_status <> 'X'
		GROUP BY upc HAVING qty > 0";
	// echo $query;
	$result = mysql_query($query);
	$num = mysql_num_rows($result);

	if ($num == 0) {
		error_log("[$timestamp] ** Error: Your query returned no results.  Exiting\n",3,$log_file);
		exit;
	} elseif (!$write = fopen($outfile,"w")) {
		error_log("[$timestamp] ** Error: Cannot open file $outfile.  Exiting\n",3,$log_file);
	    exit;
	} else {
		while ($row = mysql_fetch_assoc($result)) {
			$output .= $row['upc'] . "|\"" . $row['description'] . "\"|" . $row['qty'] . "|" . $row['total'] . "\n";
		}	

		if (fwrite($write, $output) === FALSE) {
	    	error_log("[$timestamp] ** Error: Cannot write to file $outfile.  Exiting\n",3,$log_file);
	    	exit;
		}
	}
	error_log("[$timestamp] ++ Success, wrote $num rows to file $outfile\n",3,$log_file);

	fclose($write);

	//	free result resources
	mysql_free_result($result);

} elseif ($week_tag == "YY") {
	
	$query = "SELECT * FROM is4c_log.$SPINS";
	$result = mysql_query($query);
	$num = mysql_num_rows($result);

	while ($row = mysql_fetch_array ($result, MYSQL_ASSOC)) {
		$start_date = $row['start_date'];
		$end_date = $row['end_date'];
		$tag = str_pad($row['week_tag'], 2, "0", STR_PAD_LEFT);

		//	Echo the matched week data
		error_log("[$timestamp] -- Week tag #$tag selected.  \$start_date = $start_date. \$end_date = $end_date\n",3,$log_file);

		//	Specify /path/to/file and filename
		$outfile = $outpath . $prefix . $tag . ".csv";
		error_log("[$timestamp] -- File path and name set.  \$outfile = $outfile\n",3,$log_file);

		//	The main query
		$dataQ = "SELECT upc, description, SUM(quantity) AS qty, SUM(total) AS total
			FROM is4c_log.$table
			WHERE DATE(datetime) BETWEEN '$start_date' AND '$end_date'
			AND upc > 99999 AND scale = 0 
			AND emp_no <> 9999 AND trans_status <> 'X'
			GROUP BY upc HAVING qty > 0";
		// echo $query;
		$dataR = mysql_query($dataQ);
		$num = mysql_num_rows($dataR);

		if ($num == 0) {
			error_log("[$timestamp] ** Error: Your query returned no results.  Exiting\n",3,$log_file);
			exit;
		} elseif (!$write = fopen($outfile,"w")) {
			error_log("[$timestamp] ** Error: Cannot open file $outfile.  Exiting\n",3,$log_file);
		    exit;
		} else {
			while ($row = mysql_fetch_assoc($result)) {
				$output .= $row['upc'] . "|\"" . $row['description'] . "\"|" . $row['qty'] . "|" . $row['total'] . "\n";
			}
			if (fwrite($write, $output) === FALSE) {
		    	error_log("[$timestamp] ** Error: Cannot write to file $outfile.  Exiting\n",3,$log_file);
		    	exit;
			}
		}
		error_log("[$timestamp] ++ Success, wrote $num rows to file $outfile\n",3,$log_file);
	}
	fclose($write);		
} else {
	echo "sumthings broked.  check your settings and tries it again.";
	exit;
}
?>