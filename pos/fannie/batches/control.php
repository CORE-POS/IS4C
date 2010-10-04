<?php

require_once('../src/mysql_connect.php');

function batchReset($bid) {
	
	echo "<div id=alert><p>Updating ACTIVE batch product information...  
		<!--<a href=# onclick=\"window.location.reload(true)\">clear</a>--></p>";
	
	if ($bid == 'ALL') {
		$clause = "AND b.startDate <= curdate() AND (b.endDate >= curdate() OR b.endDate = 0)";
	} else {	
		$clause = "AND b.batchID = $bid";
	}
	
	$query1 = "UPDATE is4c_op.products AS p,
		is4c_op.batches AS b,
		is4c_op.batchList AS l
		SET p.start_date = NULL,
		p.end_date = NULL,
		p.special_price = 0,
		p.discounttype = 0,
		l.active = 0,
		b.active = 0
		WHERE l.upc = p.upc
		AND b.batchID = l.batchID
		$clause";
	// echo $query1 . "<br />";
	$result1 = mysql_query($query1);
	if (!$result1) {
		$message  = 'Invalid query: ' . mysql_error() . "\n";
		$message .= 'Whole query: ' . $query1;
			die($message);
	} else {
		echo "<p>Successfully reset batch #$bid.<br />";
	
		$query2 = "UPDATE is4c_op.products as p,
			is4c_op.batches as b,
			is4c_op.batchList as l
			SET p.start_date = b.startDate,
			p.end_date = b.endDate,
			p.special_price = l.salePrice,
			p.discounttype = b.batchType,
			l.active = 1,
			b.active = 1 
			WHERE l.upc = p.upc
			AND b.batchID = l.batchID
			$clause";
		// echo $query2 . "<br />";
		$result2 = mysql_query($query2);
		if (!$result2) {
			$message  = 'Invalid query: ' . mysql_error() . "\n";
			$message .= 'Whole query: ' . $query2;
				die($message);
		} else {
			echo "Successfully forced batch #$bid.</p>";
		}
	}
	echo "</div>";
}

// if ($_GET['batchID']) {
// 	batchReset($_GET['batchID']);
// 	// exit;
// }

?>