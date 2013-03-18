<?php

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$memNum = isset($_REQUEST['memNum'])?(int)$_REQUEST['memNum']:0;

$header = "Suspension History for Member $memNum";
$page_title = "Fannie :: Suspension History";
include($FANNIE_ROOT.'src/header.html');

$q = sprintf("select username,postdate,post,textStr
		from suspension_history AS s 
		LEFT JOIN reasoncodes AS r ON
		s.reasoncode & r.mask > 0
		WHERE s.cardno=%d ORDER BY postdate DESC",$memNum);
if ($memNum == 0){
	echo "<i>Error: no member specified</i>";
}
else {
	echo "<table cellpadding=\"4\" cellspacing=\"0\" border=\"1\">";
	echo "<tr><th>Date</th><th>Reason</th><th>User</th></tr>";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r)){
		printf('<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
			$w['postdate'],(!empty($w['textStr'])?$w['textStr']:$w['post']),
			$w['username']);
	}
	echo "</table>";
}

include($FANNIE_ROOT.'src/footer.html');

?>
