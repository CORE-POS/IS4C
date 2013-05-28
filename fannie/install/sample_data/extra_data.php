<?php
include('../../config.php');
if(!class_exists('SQLManager'))
	include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
include('../util.php');
?>
<html>
<head>
<title>Fannie: Sample Data</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
<link rel="stylesheet" href="../../src/css/install.css" type="text/css" />
<script type="text/javascript" src="../../src/jquery/jquery.js"></script>
</head>
<body>
<?php
echo showInstallTabs("Sample Data", '../');
?>
<form action=extra_data.php method=post>
<H1>Fannie: Sample Data</H1>
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
elseif(isset($_REQUEST['memtype'])){
	echo "Loading memtype";
	$db->query("TRUNCATE TABLE memtype");
	loaddata($db,'memtype');
	echo "Loading memdefaults";
	$db->query("TRUNCATE TABLE memdefaults");
	loaddata($db,'memdefaults');
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
	/* subdepts sample data is of questionable use
	echo "<br />Loading subdepts";
	$db->query("TRUNCATE TABLE subdepts");
	loaddata($db,'subdepts');
	*/
}
elseif(isset($_REQUEST['memtype'])){
	echo "Loading memtype";
	$db->query("TRUNCATE TABLE memtype");
	loaddata($db,'memtype');
	echo "Loading memdefaults";
	$db->query("TRUNCATE TABLE memdefaults");
	loaddata($db,'memdefaults');
}
elseif (isset($_REQUEST['superdepts'])){
	echo "Loadintg super departments";
	$db->query("TRUNCATE TABLE superdepts");
	loaddata($db,'superdepts');
	$db->query("TRUNCATE TABLE superDeptNames");
	loaddata($db,'superDeptNames');
}
elseif (isset($_REQUEST['tenders'])){
	echo "Loadintg tenders";
	$db->query("TRUNCATE TABLE tenders");
	loaddata($db,'tenders');
}
elseif (isset($_REQUEST['authentication'])){
	echo "Loading authentication info";
	$db->query("TRUNCATE TABLE userKnownPrivs");
	loaddata($db,'userKnownPrivs');
}
elseif (isset($_REQUEST['origin'])){
	echo "Loading country info";
	$db->query("TRUNCATE TABLE originCountry");
	loaddata($db,'originCountry');
	echo "<br />Loading state/province info";
	$db->query("TRUNCATE TABLE originStateProv");
	loaddata($db,'originStateProv');
}
?>
</i></blockquote>
Some sample data is available to get a test lane
up &amp; running quickly. Keep in mind this data
overwrites whatever is currently in the table.
<br />These utilities populate the server tables.
Then use the <a href="../../sync/SyncIndexPage.php" target="_sync">Synchronize</a>
utilities to populate the lane tables.
<hr />
<b>Employees</b><br />
This table contains login information for cashiers. The two
included logins are '56' and '7000'.<br />
<input type=submit name=employees value="Load sample employees" />
<hr />
<b>Custdata</b><br />
Customer data is the membership information. Sample data includes
a bunch of members and default non-member 11.<br />
<input type=submit name=custdata value="Load sample customers" />
<br />
<input type=submit name=memtype value="Load sample member types" />
<hr />
<b>Products</b><br />
Stuff to sell. There's a lot of sample data. I think this might
be the Wedge's or at least a snapshot of it.<br />
<input type=submit name=products value="Load sample products" />
<hr />
<b>Departments</b> <br />
Products get categorized into departments .
You can also ring amounts directly to a department. Not needed,
strictly speaking, for a basic lane (Ring up items, total, 
accept tender, provide change).<br />
<input type=submit name=depts value="Load sample departments" />
<hr />
<b>Super Department Names</b> and <b>Super Department Links</b><br />
Super Departments are tags for grouping Departments.
A Department can have more than one.
Here is rudimentary set that agrees with the Products sample data.
Can also used to group the domains of Buyers.
<br />Use them with e.g. the <a href="../../fannie/item/productList.php">Product List report/tool</a>
<br />
<input type=submit name=superdepts value="Load sample super departments" />
<hr />
<b>Tenders</b>:
Load all the default tenders into the tenders table.<br />
<input type=submit name=tenders value="Load default tenders" />
<hr />
<b>Authentication</b>:
Load information about currently defined authorization classes<br />
<input type=submit name=authentication value="Load auth info" />
<hr />
<b>Countries, States, and Provinces</b>
Load default origin information<br />
<input type=submit name=origin value="Load origin info" />
<hr />
</form>
</body>
</html>
