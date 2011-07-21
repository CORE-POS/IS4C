<?php
include('../../../config.php');

require($FANNIE_ROOT.'src/csv_parser.php');

$fp = fopen('unfi.csv','r');
$fp2 = fopen('tmp/fixed.csv','w');

$lc = 0;
while(!feof($fp)){
	$line = fgets($fp);
	if ($lc > 0){
		$data = csv_parser($line);
		$data[5] = str_pad($data[5],13,'0',STR_PAD_LEFT);
		if ($data[1][0] != "\"")
			$data[1] = "\"".$data[1]."\"";
		if ($data[2][0] != "\"")
			$data[2] = "\"".$data[2]."\"";
		while (!is_numeric($data[6][0]))
			$data[6] = substr($data[6],1,strlen($data[6]));
		$outstr = "";
		foreach($data as $d)
			$outstr .= $d.",";
		$outstr = substr($outstr,0,strlen($outstr)-1);
		echo $outstr."<br />";	
		fputs($fp2,$outstr);
	}
	$lc++;
}
fclose($fp);
fclose($fp2);

?>
