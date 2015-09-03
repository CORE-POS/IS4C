<?php

include('headerTest.php');

$memID = isset($_GET['memID'])?$_GET['memID']:"0";

?>

<iframe width="90%" height="300"
    src="http://key/cgi-bin/docfile/index.cgi?memID=<?php echo $memID; ?>"
    style="border: 0px;"
></iframe>
