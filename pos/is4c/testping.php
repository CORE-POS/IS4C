<?
$_SESSION["OS"] = "linux";
pinghost("192.168.123.96");

function pinghost($host)
{

	$host = str_replace("[", "", $host);
	$host = str_replace("]", "", $host);

	$intConnected = 0;
	if ($_SESSION["OS"] == "win32") {
		$pingReturn = exec("ping -n 1 $host", $aPingReturn);
		$packetLoss = "(0% loss";
	} else {
		$pingReturn = exec("ping -c 1 -r $host", $aPingReturn);
		$packetLoss = "1 received, 0% packet loss";
	}
	foreach($aPingReturn as $returnLine) {

	echo $returnLine."<br>";
	}
	$pos = strpos($returnLine, $packetLoss);
	if  ($pos) $intConnected = 1; 

	return $intConnected;
}
?>
