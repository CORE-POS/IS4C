<?php

if (!class_exists("SQLManager")) require_once("../../sql/SQLManager.php");
include('../../db.php');

// START TESTING STUFF

echo "<h3>THIS WON'T WORK IF COST VALUES ARE NOT IN PRODEXTRA</h3>";
echo "<form action=dept_margin.php method=get>";
echo "Dept no: <input type=text name=dept size=5 /> ";
echo "Month: <input type=text name=month size=4 /> ";
echo "Year: <input type=text name=year size=4 /> ";
echo "<input type=submit value=Submit />";
echo "</form>";

if (isset($_GET['dept'])){
	$dept = $_GET['dept'];
	$month = 0;
	if (isset($_GET['month'])) $month = $_GET['month'];
	$year = 0;
	if (isset($_GET['year'])) $year = $_GET['year'];

	$margin = dept_projected_margin($dept);	
	$margin2 = dept_realized_margin($dept,$month,$year);

	echo "Projected: $margin%<br />";
	echo "Realized: $margin2%<br />";
}

echo "<hr />";
echo "<form action=dept_margin.php method=get>";
echo "Sub no: <input type=text name=sub size=5 /> ";
echo "Month: <input type=text name=month size=4 /> ";
echo "Year: <input type=text name=year size=4 /> ";
echo "<input type=submit value=Submit />";
echo "</form>";

if (isset($_GET['sub'])){
	$dept = $_GET['sub'];
	$month = 0;
	if (isset($_GET['month'])) $month = $_GET['month'];
	$year = 0;
	if (isset($_GET['year'])) $year = $_GET['year'];

	$margin = sub_projected_margin($dept);	
	$margin2 = sub_realized_margin($dept,$month,$year);

	echo "Projected: $margin%<br />";
	echo "Realized: $margin2%<br />";
}

// END TESTING STUFF

/*
	Weighted average of all items in a sub department
*/
function sub_realized_margin($sub,$month=0,$year=0){
	global $sql;
	if ($month == 0)
		$month = date("m");
	if ($year == 0)
		$year = date("Y");

	$counts = select_counts($month,$year);

	$countQ = "select p.normal_price,q.cost,c.count from
		   products as p left join prodExtra as q
		   on p.upc = q.upc left join $counts as c
		   on p.upc = c.upc left join departments as d
		   on c.department = d.dept_no
		   left join MasterSuperDepts AS s ON
		   s.dept_ID=d.dept_no
		   where s.superID = $sub
		   and q.cost <> 0 order by c.count desc";
	$countR = $sql->query($countQ);

	$num = 0;
	$denom = 0;	

	while($countW = $sql->fetch_array($countR)){
		$num += $countW[2] * margin($countW[0],$countW[1]);
		$denom += $countW[2];
	}

	return $num / $denom;
}

/* 
	Average of all items in a sub department
*/
function sub_projected_margin($sub){
	$prodQ = "select p.normal_price,q.cost from products as p
		  left join prodExtra as q on p.upc = q.upc
		  left join departments as d on p.department = d.dept_no
		  left join MasterSuperDepts AS s ON d.dept_no=s.dept_ID
		  where s.superID = $sub and q.cost <> 0";
	$prodR = $sql->query($prodQ);

	$count = 0;
	$margin = 0;
	while ($prodW = $sql->fetch_array($prodR)){
		$margin += margin($prodW[0],$prodW[1]);
		$count++;
	}

	return $margin / $count;
}

/*
	Average margin of all items in department
*/
function dept_realized_margin($dept,$month=0,$year=0){
	global $sql;
	if ($month == 0)
		$month = date("m");
	if ($year == 0)
		$year = date("Y");

	$counts = select_counts($month,$year);

	$countQ = "select p.normal_price,q.cost,c.count from
		   products as p left join prodExtra as q
		   on p.upc = q.upc left join $counts as c
		   on p.upc = c.upc
		   where c.department = $dept
		   and q.cost <> 0 order by c.count desc";
	$countR = $sql->query($countQ);

	$num = 0;
	$denom = 0;	

	while($countW = $sql->fetch_array($countR)){
		$num += $countW[2] * margin($countW[0],$countW[1]);
		$denom += $countW[2];
	}

	return $num / $denom;
}

/*
	weighted average all items in department
*/
function dept_projected_margin($dept){
	global $sql;
	$prodQ = "select p.normal_price,q.cost from products as p
		  left join prodExtra as q on p.upc = q.upc
		  where p.department = $dept and q.cost <> 0";
	$prodR = $sql->query($prodQ);

	$count = 0;
	$margin = 0;
	while ($prodW = $sql->fetch_array($prodR)){
		$margin += margin($prodW[0],$prodW[1]);
		$count++;
	}

	return $margin / $count;
}

/*
	Calculate the margin given a price and a cost
	Separate for the sake of modularity
	Returns % margin, multiplied by 100 (for
	common % representation)
*/
function margin($price,$cost){
	return ( ($price - $cost) / $cost ) * 100;
}


/*
	load item sale counts into the proper dlog_archive.dbo.count_* table
*/
function reload_counts($month,$year){
	global $sql;
	$datestring = str_pad($year,4,'20',STR_PAD_LEFT)."_".str_pad($month,2,'0',STR_PAD_LEFT);
	$dlog = "dlog_archive.dbo.dlog_".$datestring;

	$dropQ = "drop table dlog_archive.dbo.counts_".$datestring;
	$dropR = $sql->query($dropQ);

	$insQ = "select d.upc,p.department,sum(d.quantity) as count
		 into dlog_archive.dbo.counts_$datestring
		 from $dlog as d left join products as p on
		 d.upc = p.upc
		 where p.department is not NULL
		 group by d.upc,p.department";
	$insR = $sql->query($insQ);
}

/*
	reload all the counts tables, 9/2004 through present
*/
function reload_all_counts(){
	$month = 9;
	$year = 2004;

	$endmonth = date('n');
	$endyear = date('Y');

	while (($month < $endmonth and $year <= $endyear) or $year < $endyear){
		reload_counts($month,$year);
		$month++;
		if ($month > 12){
			$month = 1;
			$year++;
		}
	}
}

/*
	return the proper counts table for the given month
*/
function select_counts($month,$year){
	if (date("m-Y") == str_pad($month,2,'0',STR_PAD_LEFT)."-".str_pad($year,4,'20',STR_PAD_LEFT))
		return "counts";
	else
		return "dlog_archive.dbo.counts_".str_pad($year,4,'20',STR_PAD_LEFT)."_".str_pad($month,2,'0',STR_PAD_LEFT);	
}
?>
