<?php
$header = "SPINs Log Reader";
$page_title = "Fannie - Admin";
include("../src/header.html");

$filename = "/var/log/spins.log";
$handle = fopen($filename, "r");
echo "<div style=\"overflow:auto; height:350px; width:500px; align:center;\">";	// Scrolling div

while ($theData = fgets($handle)) {
	if (strpbrk($theData,"*") == true) {
		echo "<font color=red>" . $theData . "</font><br>\n";
	} elseif (strpbrk($theData,"+") == true) {
		echo "<font color=green>" . $theData . "</font><br>\n";
	} else {
		echo $theData . "<br>\n";
	}
}

echo "</div>\n";

fclose($handle);

include("../src/footer.html");
?>