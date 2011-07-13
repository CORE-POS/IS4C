<?php
include('../../config.php');
if(!class_exists('SQLManager'))
	include($FANNIE_ROOT.'src/SQLManager.php');
?>
<html>
<head>
<title>Load optional data</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
</head>
<body>
<a href="../index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Sample Data
<br />
<form action=extra_data.php method=post>
<blockquote><i>
<?php
$db = new SQLManager($FANNIE_SERVER,
	$FANNIE_SERVER_DBMS,
	$FANNIE_OP_DB,
	$FANNIE_SERVER_USER,
	$FANNIE_SERVER_PW);

if (isset($_REQUEST['employees'])){
	echo "Loading employees";
	$db->query("TRUNCATE TABLE employees");
	loaddata($db,'employees');	
}
elseif(isset($_REQUEST['custdata'])){
	echo "Loading custdata";
	$db->query("TRUNCATE TABLE custdata");
	loaddata($db,'custdata');
}
elseif(isset($_REQUEST['products'])){
	echo "Loading products";
	$db->query("TRUNCATE TABLE products");
	loaddata($db,'products');
}
elseif(isset($_REQUEST['depts'])){
	echo "Loading departments";
	$db->query("TRUNCATE TABLE departments");
	loaddata($db,'departments');
	echo "<br />Loading subdepts";
	$db->query("TRUNCATE TABLE subdepts");
	loaddata($db,'subdepts');
}
elseif (isset($_REQUEST['tenders'])){
	echo "Loadintg tenders";
	$db->query("TRUNCATE TABLE tenders");
	loaddata($db,'tenders');
}
?>
</i></blockquote>
Some sample data is available to get a test lane
up &amp; running quickly. Keep in mind this data
overwrites whatever is currently in the table.
<hr />
<b>Employees</b><br />
This table contains login information for cashiers. The two
included logins are '56' and '7000'.<br />
<input type=submit name=employees value="Load sample employees" />
<hr />
<b>Custdata</b><br />
Customer data is the membership information. Sample data includes
members 6000 through 6010 and non-member 99999.<br />
<input type=submit name=custdata value="Load sample customers" />
<hr />
<b>Products</b><br />
Stuff to sell. There's a lot of sample data. I think this might
be the Wedge's or at least a snapshot of it.<br />
<input type=submit name=products value="Load sample products" />
<hr />
<b>Departments</b> &amp; <b>Subdepts</b><br />
Products get categorized into departments &amp; subdepartments.
You can also ring amounts directly to a department. Not needed,
strictly speaking, for a basic lane (Ring up items, total, 
accept tender, provide change).<br />
<input type=submit name=depts value="Load sample departments" />
<hr />
<b>Tenders</b>:
Load all the default tenders into the tenders table.<br />
<input type=submit name=tenders value="Load default tenders" />
</form>
</body>
</html>
<?php
function loaddata($sql, $table){
	if (file_exists("$table.sql")){
		$fp = fopen("$table.sql","r");
		while($line = fgets($fp)){
			$sql->query("INSERT INTO $table VALUES $line");
		}
		fclose($fp);
	}
	else if (file_exists("$table.csv")){
		$sql->query("LOAD DATA LOCAL INFILE
			'{$FANNIE_ROOT}install/sample_data/$table.csv'
			INTO TABLE $table
			FIELDS TERMINATED BY ','
			ESCAPED BY '\\\\'
			OPTIONALLY ENCLOSED BY '\"'
			LINES TERMINATED BY '\\r\\n'");
	}
}
?>
