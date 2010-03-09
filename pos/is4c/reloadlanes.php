<?




function synctable($table) {

$table = strtolower($table);

$aLane = array("192.168.123.101", "192.168.123.102", "192.168.123.103");

$server = "192.168.123.100";
$serveruser = "root";
$serverpass = "";

$laneuser = "root";
$lanepass = "";

$outfile = "/pos/is4c/download/".$table.".out";


$makeoutfile = "select * into outfile '".$outfile."' from ".$table;
$load = "load data infile '".$outfile."' into table ".$table;
$truncatelocal = "truncate table ".$table;
$insertlocal = "insert into ".table." select * from is4c_op.".$table;


// establish connect to server

$conn = mysql_connect($server, $serveruser, $serverpass)) or die("Unable to connect to server database");
mysql_select_db("is4c_op", $conn) or die("unsable to connect to database is4c_op on server");



if (file_exists($outfile)) exec("rm ".$outfile);


mysql_query($makeoutfile, $conn) or die ("Failed to create outfile from server table ".$table);

if (file_exists($outfile)) {

	$i = 1;

	foreach($aLane as $lane) {

		$lanenum = "lane ".$i;
		$i++;
		$lane_conn = mysql_connect($lane, $laneuser, $lanepass) or die ("Failed to connect to ".$lanenum);
		mysql_select_db("is4c_op", $lane_conn) or die ("Failed to connect to database is4c_op on ".$lanenum);
		mysql_query($load, $lane_conn) or die ("Failed to load data into is4c_op.".$table." on ".$lanenum);

		mysql_query($truncatelocal, $lane_conn) or die ("Failed to truncate old table ".$table." on ".$lanenum);
		mysql_query($insertlocal, $lane_conn) or die ("Failed to insert new data from is4c_op.".$table." on ".$lanenum);

		echo $lanenum." successfully synchronized";
	}
} 
else {
	echo "<p>Outfile from server not found";
}

}


?>