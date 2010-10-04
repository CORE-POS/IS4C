<?
// setlocale(LC_MONETARY, 'en_US');
require('conf.php');

//$db_date = '2007-03-19';
// 
// $db = mysql_connect('localhost','root','');
// mysql_select_db('is4c_log',$db);
// require_once('../src/mysql_connect.php');
// 
// $db_date = $_SESSION['db_date'];
// $table = $_SESSION['table'];

/** 
 * total sales 
 * Gross = total of all inventory depts. 1-15 (at PFC)
 * Hash = People Shares + General Donations + Customers Svcs. + gift certs. sold + Bottle Deposits & Returns + Comm. Rm. fees
 * Net = Gross + Everything else + R/A (45) - Market EBT (37) - Charge pmts.(35) - All discounts - Coupons(IC & MC) - 
 * 		Gift Cert. Tender - Store Charge
 */

$grossQ = "SELECT ROUND(sum(total),2) as GROSS_sales
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date' 
	AND department < 20
	AND department <> 0
	AND trans_subtype NOT IN('IC','MC')
	AND trans_status <> 'X'
	AND emp_no <> 9999";

	$results = mysql_query($grossQ);
	$row = mysql_fetch_row($results);
	$gross = $row[0];

$hashQ = "SELECT ROUND(sum(total),2) AS HASH_sales
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND department >= 34 AND department <= 44
	AND trans_status <> 'X'
	AND emp_no <> 9999";

	$results = mysql_query($hashQ);
	$row = mysql_fetch_row($results);
	$hash = $row[0];
	if (is_null($hash)) {
		$hash = 0;
	}

//
//	BEGIN STAFF_TOTAL	
//	Total Staff discount given less the needbased and MAD discount
//
// $staffQ = "SELECT (SUM(d.unitPrice)) AS staff_total
// 	FROM is4c_log.$table AS d
// 	WHERE date(d.datetime) = '$db_date'
// 	AND d.upc = 'DISCOUNT'
// 	AND d.staff IN(1,2)
// 	AND d.trans_status <> 'X' 
// 	AND d.emp_no <> 9999";
// 
// $lessQ = "SELECT (SUM(d.unitPrice) * -1) AS TOT
// 	FROM is4c_log.$table AS d 
// 	WHERE date(d.datetime) = '$db_date'
// 	AND d.staff IN(1,2)
// 	AND d.voided IN(9,10)
// 	AND d.trans_status <> 'X'
// 	AND d.emp_no <> 9999";
// 
// 	$staffR = mysql_query($staffQ);
// 	$row = mysql_fetch_row($staffR);
// 	$staff = $row[0];
// 	if (is_null($staff)) {
// 		$staff = 0;
// 	}
// 	$lessR = mysql_query($lessQ);
// 	$row = mysql_fetch_row($lessR);
// 	$less = $row[0];
// 	if (is_null($less)) {
// 		$less = 0;
// 	}
// 	
// $staff_total = $staff + $less;
// 
// if (is_null($staff_total)) {
// 	$staff_total = 0;
// }

//	Calculate discounts by pct. -- test 2009-03-09
	
$staffQ = "SELECT (-SUM(total) * ($staff_discount / 100)) AS staff_total
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND department BETWEEN 1 AND 20
	AND staff IN(1,2)
	AND trans_status <> 'X' 
	AND emp_no <> 9999";
	
// echo $staffQ;
	
$staffR = mysql_query($staffQ);
$row = mysql_fetch_row($staffR);
$staff_total = $row[0];
if (is_null($staff_total)) { $staff_total = 0;}



//
//	END STAFF_TOTAL
//

//
//	BEGIN HOO_TOTAL
//
// $hoosQ = "SELECT SUM(d.unitPrice) AS hoos 
// 	FROM is4c_log.$table AS d  
// 	WHERE date(d.datetime) = '$db_date'
// 	AND d.upc = 'DISCOUNT'
// 	AND d.staff = 3
// 	AND d.trans_status <> 'X' 
// 	AND d.emp_no <> 9999";
// 
// $lessQ = "SELECT (SUM(d.unitPrice) * -1) AS TOT
// 	FROM is4c_log.$table AS d 
// 	WHERE date(d.datetime) = '$db_date'
// 	AND d.staff = 3
// 	AND d.voided IN(9,10)
// 	AND d.trans_status <> 'X'
// 	AND d.emp_no <> 9999";
// 
// 	$hoosR = mysql_query($hoosQ);
// 	$row = mysql_fetch_row($hoosR);
// 	$hoos = $row[0];
// 	
// 	$lessR = mysql_query($lessQ);
// 	$row = mysql_fetch_row($lessR);	
// 	$less = $row[0];
// 	
// 	if (is_null($hoos)) {
// 		$hoos = 0;
// 	}
// 	if (is_null($less)) {
// 		$less = 0;
// 	}
// 
// $hoo_total = $hoos + $less;
// if (is_null($hoo_total)) {
// 	$hoo_total = 0;
// }
//
//	END HOO_TOTAL
//

//	NEW HOO discount formula
$hoo_total = 0;
foreach($volunteer_discount AS $row) {
	$wmQ = "SELECT (-SUM(total) * ($row / 100)) AS working_member
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND staff = 3
		AND department BETWEEN 1 AND 20
		AND percentDiscount = $row";
	// echo $wmQ;
	$wmR = mysql_query($wmQ);
	$row = mysql_fetch_row($wmR);
	$hoo_tot = $row[0];
	$hoo_total = $hoo_total + $hoo_tot;
}




//
//	BEGIN BENE_TOTAL
//
// $benefitsQ = "SELECT (ROUND(SUM(d.unitPrice),2)) AS benefits_providers
// 	FROM is4c_log.$table AS d 
// 	WHERE date(d.datetime) = '$db_date'
// 	AND d.upc = 'DISCOUNT' 
// 	AND d.staff = 5
// 	AND d.trans_status <> 'X' 
// 	AND d.emp_no <> 9999";
// 
// 	$benefitsR = mysql_query($benefitsQ);
// 	$row = mysql_fetch_row($benefitsR);
// 	$benefits = $row[0];
// 	if (is_null($benefits)) {
// 		$benefits = 0;
// 	}
// 
// $lessQ = "SELECT (SUM(d.unitPrice) * -1) AS TOT
// 	FROM is4c_log.$table AS d 
// 	WHERE date(d.datetime) = '$db_date'
// 	AND d.staff = 5
// 	AND d.voided IN(9,10)
// 	AND d.trans_status <> 'X'
// 	AND d.emp_no <> 9999";
// 
// 	$lessR = mysql_query($lessQ);
// 	$row = mysql_fetch_row($lessR);	
// 	$less = $row[0];
// 	if (is_null($less)) {
// 		$less = 0;
// 	}
// 
// $bene_total = $benefits + $less;
// 
// if (is_null($bene_total)) {
// 	$bene_total = 0;
// }
// //
//	END BENE_TOTAL
//

//	NEW benefits providers calcs
	
$bene_total = 0;
foreach($volunteer_discount AS $row) {
	$beneQ = "SELECT (-SUM(total) * ($row / 100)) AS benefit_provider
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND staff = 5
		AND department BETWEEN 1 AND 20
		AND percentDiscount = $row";
	// echo $wmQ;
	$beneR = mysql_query($beneQ);
	$row = mysql_fetch_row($beneR);
	$bene_tot = $row[0];
	$bene_total = $bene_total + $bene_tot;
}

//
//	BOD DISCOUNTS
//
// $bodQ = "SELECT (ROUND(SUM(d.unitPrice),2)) AS bod_discount
// 	FROM is4c_log.$table AS d 
// 	WHERE date(d.datetime) = '$db_date' 
// 	AND d.upc = 'DISCOUNT'
// 	AND d.staff = 4
// 	AND d.trans_status <> 'X' 
// 	AND d.emp_no <> 9999";
// 
// 	$bodR = mysql_query($bodQ);
// 	$row = mysql_fetch_row($bodR);
// 	$bod = $row[0];
// 	if (is_null($bod)) {
// 		$bod = 0;
// 	}
// 
// $lessQ = "SELECT (SUM(d.unitPrice) * -1) AS TOT
// 	FROM is4c_log.$table AS d 
// 	WHERE date(d.datetime) = '$db_date'
// 	AND d.staff = 4
// 	AND d.voided IN(9,10)
// 	AND d.trans_status <> 'X'
// 	AND d.emp_no <> 9999";
// 
// 	$lessR = mysql_query($lessQ);
// 	$row = mysql_fetch_row($lessR);	
// 	$less = $row[0];
// 	if (is_null($less)) {
// 		$less = 0;
// 	}
// 
// $bod_total = $bod + $less;
// 
// if (is_null($bod_total)) {
// 	$bod_total = 0;
// }
//	END BOD DISCOUNT

//	NEW bod discount -- 2009-03-09
	
$boardQ = "SELECT (-SUM(total) * ($board_discount / 100)) AS board_total
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND department BETWEEN 1 AND 20
	AND staff IN(4)
	AND trans_status <> 'X' 
	AND emp_no <> 9999";
		
$boardR = mysql_query($boardQ);
$row = mysql_fetch_row($boardR);
$bod_total = $row[0];
if (is_null($bod_total)) { $bod_total = 0;}

//	DISCOUNT CATCHALL




//	END DISCOUNT CATCHALL


// $MADcouponQ = "SELECT ROUND(SUM(unitPrice),2) AS MAD_Coupon_total
// 	FROM is4c_log.$table
// 	WHERE date(datetime) = '$db_date' 
// 	AND voided = 9
// 	AND trans_status <> 'X'
// 	AND emp_no <> 9999";
// 
// 	$MADcouponR = mysql_query($MADcouponQ);
// 	$row = mysql_fetch_row($MADcouponR);
// 	$MADcoupon = $row[0];
// 	if (is_null($MADcoupon)) {
// 		$MADcoupon = 0;
// 	}

// 	NEW MAD coupon reporting format?.....  -- 2009-03-09

$trans_IDQ = "SELECT CONCAT(emp_no,'_',register_no,'_',trans_no) AS trans_ID
	FROM is4c_log.$table
	WHERE DATE(datetime) = '$db_date'
	AND voided = 9
	AND trans_status NOT IN ('X','V')
	AND emp_no <> 9999";
// echo $trans_IDQ;
$result = mysql_query($trans_IDQ);
$MAD_num = mysql_num_rows($result);
$MADcoupon = 0;
while ($row = mysql_fetch_array($result)) {
	$n = explode('_',$row['trans_ID']);
	$emp_no = $n[0];
	$register_no = $n[1];
	$trans_no = $n[2];
	$query = "SELECT (-SUM(total) * ($MAD_discount / 100)) as MADdiscount
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND emp_no = $emp_no AND register_no = $register_no AND trans_no = $trans_no
		AND department BETWEEN 1 AND 20";
	$result2 = mysql_query($query);
	$row2 = mysql_fetch_row($result2);
	$MAD_tot = $row2[0];
	// echo "MAD_tot = " . $MAD_tot;
	$MADcoupon = $MADcoupon + $MAD_tot;
}


// $foodforallQ = "SELECT ROUND(SUM(unitPrice),2) AS FoodForAll_total
// 	FROM is4c_log.$table
// 	WHERE date(datetime) = '$db_date' 
// 	AND voided = 10
// 	AND trans_status <> 'X'
// 	AND emp_no <> 9999";
// 
// 	$foodforallR = mysql_query($foodforallQ);
// 	$row = mysql_fetch_row($foodforallR);
// 	$foodforall = $row[0];
// 	if (is_null($foodforall)) {
// 		$foodforall = 0;
// 	}

//	NEW need-based-discount reporting calcs
	
$trans_IDQ = "SELECT CONCAT(emp_no,'_',register_no,'_',trans_no) AS trans_ID
	FROM is4c_log.$table
	WHERE DATE(datetime) = '$db_date'
	AND voided = 10
	AND trans_status NOT IN ('X','V')
	AND emp_no <> 9999";
// echo $trans_IDQ;
$result = mysql_query($trans_IDQ);
$ffa_num = mysql_num_rows($result);
$foodforall = 0;
while ($row = mysql_fetch_array($result)) {
	$n = explode('_',$row['trans_ID']);
	$emp_no = $n[0];
	$register_no = $n[1];
	$trans_no = $n[2];
	$query = "SELECT (-SUM(total) * ($need_based_discount / 100)) as NBDiscount
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND emp_no = $emp_no AND register_no = $register_no AND trans_no = $trans_no
		AND department BETWEEN 1 AND 20";
	$result2 = mysql_query($query);
	$row2 = mysql_fetch_row($result2);
	$ffa_tot = $row2[0];
	// echo "ffa_tot = " . $ffa_tot;
	$foodforall = $foodforall + $ffa_tot;
}



$totalDisc = $staff_total + $bene_total + $hoo_total + $bod_total + $MADcoupon + $foodforall;

$ICQ = "SELECT ROUND(SUM(total),2) AS coupons
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND trans_subtype IN('IC')
	AND trans_status <> 'X'
	AND emp_no <> 9999";
	
	$ICR = mysql_query($ICQ);
	$row = mysql_fetch_row($ICR);
	$IC = $row[0];
	if (is_null($IC)) {
		$IC = 0;
	}

$MCQ = "SELECT ROUND(SUM(total),2) AS coupons
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND trans_subtype IN('MC')
	AND trans_status <> 'X'
	AND emp_no <> 9999";

	$MCR = mysql_query($MCQ);
	$row = mysql_fetch_row($MCR);
	$MC = $row[0];
	if (is_null($MC)) {
		$MC = 0;
	}
	
$TCQ = "SELECT ROUND(SUM(total),2) AS coupons
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND trans_subtype IN('TC')
	AND trans_status <> 'X'
	AND emp_no <> 9999";

	$TCR = mysql_query($TCQ);
	$row = mysql_fetch_row($TCR);
	$TC = $row[0];
	if (is_null($TC)) {
		$TC = 0;
	}

$coupons = $IC + $MC + $TC;

$strchgQ = "SELECT ROUND(SUM(total),2) AS strchg
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND trans_subtype IN('MI')
	AND trans_status <> 'X'
	AND emp_no <> 9999";

	$strchgR = mysql_query($strchgQ);
	$row = mysql_fetch_row($strchgR);
	$strchg = $row[0];
	if (is_null($strchg)) {
		$strchg = 0;
	}

$RAQ = "SELECT ROUND(SUM(total),2) as RAs
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND department IN(45)
	AND trans_status <> 'X'
	AND emp_no <> 9999";

	$RAR = mysql_query($RAQ);
	$row = mysql_fetch_row($RAR);
	$RA = $row[0];
	if (is_null($RA)) {
		$RA = 0;
	}

//
//	NET TOTALS
//

$net = $gross + $hash + $totalDisc + $coupons + $strchg + $RA;

$cashier_netQ = "SELECT -SUM(total) AS net
	FROM is4c_log.$table
	WHERE DATE(datetime) = '$db_date'
	AND trans_subtype IN ('CA','CK','DC','CC','FS','EC')
	AND emp_no <> 9999 AND trans_status <> 'X'";

	$cnR = mysql_query($cashier_netQ);
	$row = mysql_fetch_row($cnR);
	$cnet = $row[0];
	
	
$d2 = $net - $cnet;
?>
