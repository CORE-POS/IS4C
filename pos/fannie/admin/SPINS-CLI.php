<?php
require_once("../src/mysql_connect.php");
DEFINE(FTP_SERVER, 'ftp.spins.com');
DEFINE(FTP_USER, 'pfc_prt');
DEFINE(FTP_PASS, 'pfcp540*');

/////////  O P T I O N S 	//////////////////
//	Pick a year	
//	(leave blank for current)
// $year = "";								
if (!$year) {$year = date('Y');}				
//	Options for reporting:
//		## == enter a week tag number
//		YY == output entire year (slow!)
//		(leave blank for current output)
//$week_tag = "12";
if (!$week_tag) $week_tag = date('W') - 1;

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
$outpath = "/pos/fannie/SPINS/" . $year . "/";
//	filename prefix (incl _wk)
$prefix = "pfcp_wk";	
///////////////////////////////////////////////


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

$infile = $outfile;
$ftpPath = '/data/';
$outfile = $prefix . $tag . ".csv";

$size = filesize($infile);

if ( ($ftp = ftp_connect(FTP_SERVER)) && (ftp_login($ftp, FTP_USER, FTP_PASS)) && (ftp_pasv($ftp, TRUE)) && (ftp_put($ftp, $ftpPath . $outfile, $infile, FTP_BINARY)) ) {
    $dir = ftp_rawlist($ftp, "/data");
    ftp_close($ftp);
    
    $items=array();
    
    foreach($dir as $_) {
        preg_replace(
        
        '`^(.{10}+)(\s*)(\d{1})(\s*)(\d*|\w*)'.
        '(\s*)(\d*|\w*)(\s*)(\d*)\s'.
        '([a-zA-Z]{3}+)(\s*)([0-9]{1,2}+)'.
        '(\s*)([0-9]{2}+):([0-9]{2}+)(\s*)(.*)$`Ue',
        
        '$items["$17"]="$9"',
        
        $_) ; # :p
    }
    $ftpSize = $items[" $outfile"];
    if ($ftpSize == $size)
        error_log("[$timestamp] ++ Success, uploaded file $outfile to " . FTP_SERVER . "\n",3,$log_file);
    else
        error_log("[$timestamp] ++ File uploaded, but $outfile ($ftpSize) size does not match $infile ($size).\n",3,$log_file);

} else {
    error_log("[$timestamp] ++ FTP error, could not upload file $outfile to " . FTP_SERVER . "\n",3,$log_file);
}


?>
