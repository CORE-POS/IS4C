<?php
require_once('../src/mysql_connect.php');

function gross($table,$date1,$date2) {
	global $dbc;
	
	if (!isset($date2)) {$date2 = $date1;}
	
	$grossQ = "SELECT ROUND(sum(total),2) as GROSS_sales
		FROM $table
		WHERE tdate >= '$date1'
		AND tdate <= '$date2' 
		AND department <> 0
		AND trans_subtype NOT IN('IC','MC')
		AND trans_status <> 'X'
		AND emp_no <> 9999";

	$results = $dbc->query($grossQ);
	$row = $dbc->fetch_row($results);
	$gross = $row[0];

	return $gross;
}

function hash_total($table,$date1,$date2) {
	global $dbc;
	
	if (!isset($date2)) {$date2 = $date1;}
		
	$hashQ = "SELECT ROUND(sum(total),2) AS HASH_sales
		FROM $table
		WHERE tdate >= '$date1'
		AND tdate <= '$date2'
		AND department IN(34,36,38,40,41,42,43,44)
		AND trans_status <> 'X'
		AND emp_no <> 9999";

	$results = $dbc->query($hashQ);
	$row = $dbc->fetch_row($results);
	$hash = $row[0];

	return $hash;
}

function staff_total($table,$date1,$date2) {
	global $dbc;
	
	if (!isset($date2)) {$date2 = $date1;}
	
//	Total Staff discount given less the needbased and MAD discount
	$staffQ = "SELECT (SUM(total)) AS staff_total
		FROM $table
		WHERE tdate >= '$date1'
		AND tdate <= '$date2'
		AND upc = 'DISCOUNT'";

	$staffR = $dbc->query($staffQ);
	$row = $dbc->fetch_row($staffR);
	$staff = $row[0];
	if (is_null($staff)) {
		$staff = 0;
	}

	return $staff;
}
//	END STAFF_TOTAL

//	BEGIN HOO_TOTAL
function hoo_total($table,$date1,$date2) {
	
	if (!isset($date2)) {$date2 = $date1;}
	$hoosQ = "SELECT SUM(unitPrice) AS hoos 
		FROM $table
		WHERE date(datetime) >= '$date1'
		AND date(datetime) <= '$date2'		
		AND upc = 'DISCOUNT'
		AND staff = 3
		AND trans_status <> 'X' 
		AND emp_no <> 9999";

	$lessQ = "SELECT (SUM(d.unitPrice) * -1) AS TOT
		FROM is4c_log.$table AS d
		WHERE date(datetime) >= '$date1'
		AND date(datetime) <= '$date2'
		AND staff = 3
		AND voided IN(9,10)
		AND trans_status <> 'X'
		AND emp_no <> 9999";

	$hoosR = mysql_query($hoosQ);
	$row = mysql_fetch_row($hoosR);
	$hoos = $row[0];

	$lessR = mysql_query($lessQ);
	$row = mysql_fetch_row($lessR);	
	$less = $row[0];

	if (is_null($hoos)) {
		$hoos = 0;
	}
	if (is_null($less)) {
		$less = 0;
	}

	$hoo_total = $hoos + $less;

	if (is_null($hoo_total)) {
		$hoo_total = 0;
	}

	return $hoo_total;
}
//	END HOO_TOTAL
	
//	BEGIN BENE_TOTAL
function bene_total($table,$date1,$date2) {
	global $dbc;
	
	if (!isset($date2)) {$date2 = $date1;}
	$benefitsQ = "SELECT (ROUND(SUM(unitPrice),2)) AS benefits_providers
		FROM $table
		WHERE date(datetime) >= '$date1'
		AND date(datetime) <= '$date2'
		AND upc LIKE 'DISCOUNT' 
		AND staff = 5
		AND trans_status <> 'X' 
		AND emp_no <> 9999";
		
	$lessQ = "SELECT (SUM(unitPrice) * -1) AS TOT
		FROM is4c_log.$table
		WHERE date(datetime) >= '$date1'
		AND date(datetime) <= '$date2'
		AND staff = 5
		AND voided IN(9,10)
		AND trans_status <> 'X'
		AND emp_no <> 9999";

	$benefitsR = mysql_query($benefitsQ);
	$row = mysql_fetch_row($benefitsR);
	$benefits = $row[0];
	if (is_null($benefits)) {
		$benefits = 0;
	}

	$lessR = mysql_query($lessQ);
	$row = mysql_fetch_row($lessR);	
	$less = $row[0];
	if (is_null($less)) {
		$less = 0;
	}

	$bene_total = $benefits + $less;

	if (is_null($bene_total)) {
		$bene_total = 0;
	}
	
	return $bene_total;
}
//	END BENE_TOTAL

//	BOD DISCOUNTS
function bod_total($table,$date1,$date2) {
	
	if (!isset($date2)) {$date2 = $date1;}
	$bodQ = "SELECT (ROUND(SUM(unitPrice),2)) AS bod_discount
		FROM is4c_log.$table
		WHERE date(datetime) >= '$date1'
		AND date(datetime) <= '$date2'
		AND upc = 'DISCOUNT'
		AND staff = 4
		AND trans_status <> 'X' 
		AND emp_no <> 9999";

	$bodR = mysql_query($bodQ);
	$row = mysql_fetch_row($bodR);
	$bod = $row[0];
	if (is_null($bod)) {
		$bod = 0;
	}

	$lessQ = "SELECT (SUM(unitPrice) * -1) AS TOT
		FROM is4c_log.$table
		WHERE date(datetime) >= '$date1'
		AND date(datetime) <= '$date2'
		AND staff = 4
		AND voided IN(9,10)
		AND trans_status <> 'X'
		AND emp_no <> 9999";

	$lessR = mysql_query($lessQ);
	$row = mysql_fetch_row($lessR);	
	$less = $row[0];
	if (is_null($less)) {
		$less = 0;
	}

	$bod_total = $bod + $less;

	if (is_null($bod_total)) {
		$bod_total = 0;
	}
	
	return $bod_total;
}
	//	END BOD DISCOUNT

function MADcoupon($table,$date1,$date2) {
	global $dbc;

		if (!isset($date2)) {$date2 = $date1;}
	$MADcouponQ = "SELECT SUM(total) AS MAD_Coupon_total
		FROM $table
		WHERE tdate >= '$date1'
		AND tdate <= '$date2'
		AND trans_subtype = 'MA'
		AND trans_status <> 'X'
		AND emp_no <> 9999";

	$MADcouponR = $dbc->query($MADcouponQ);
	$row = $dbc->fetch_row($MADcouponR);
	$MADcoupon = $row[0];
	if (is_null($MADcoupon)) {
		$MADcoupon = 0;
	}

	return $MADcoupon;
}

function foodforall($table,$date1,$date2) {
	
	if (!isset($date2)) {$date2 = $date1;}
	$foodforallQ = "SELECT ROUND(SUM(unitPrice),2) AS FoodForAll_total
		FROM is4c_log.$table
		WHERE date(datetime) >= '$date1'
		AND date(datetime) <= '$date2'
		AND voided = 10
		AND trans_status <> 'X'
		AND emp_no <> 9999";

	$foodforallR = mysql_query($foodforallQ);
	$row = mysql_fetch_row($foodforallR);
	$foodforall = $row[0];
	if (is_null($foodforall)) {
		$foodforall = 0;
	}

	return $foodforall;
}


?>
