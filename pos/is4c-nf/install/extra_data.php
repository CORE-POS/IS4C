<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
include('InstallUtilities.php');
?>
<html>
<head>
<title>IT CORE Lane Installation: Sample data</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2>IT CORE Lane Installation: Sample Data</h2>

<form action=extra_data.php method=post>
<div class="alert success"><b>
<?php
$db = new SQLManager($CORE_LOCAL->get('localhost'),
	$CORE_LOCAL->get('DBMS'),
	$CORE_LOCAL->get('pDatabase'),
	$CORE_LOCAL->get('localUser'),
	$CORE_LOCAL->get('localPass'));

if (isset($_REQUEST['employees'])){
	echo "Loading employees";
	$db->query("TRUNCATE TABLE employees");
	InstallUtilities::loadSampleData($db,'employees');	
}
elseif(isset($_REQUEST['custdata'])){
	echo "Loading custdata";
	$db->query("TRUNCATE TABLE custdata");
	InstallUtilities::loadSampleData($db,'custdata');
}
elseif(isset($_REQUEST['products'])){
	echo "Loading products";
	$db->query("TRUNCATE TABLE products");
	InstallUtilities::loadSampleData($db,'products');
}
elseif(isset($_REQUEST['depts'])){
	echo "Loading departments";
	$db->query("TRUNCATE TABLE departments");
	InstallUtilities::loadSampleData($db,'departments');
	echo "<br />Loading subdepts";
	$db->query("TRUNCATE TABLE subdepts");
	InstallUtilities::loadSampleData($db,'subdepts');
}
?>
</b></div>
<p>Some sample data is available to get a test lane
up &amp; running quickly. Keep in mind this data
overwrites whatever is currently in the table.</p>
<hr />
<b>Employees</b>
<p>This table contains login information for cashiers. The two
included logins are '56' and '7000'.</p>
<input id="data" type=submit name=employees value="Load sample employees" />
<hr />
<b>Custdata</b>
<p>Customer data is the membership information. Sample data includes
 a way too many mostly identical lines. A very scrubbed version
of someone's customer table I think.</p>
<input type=submit name=custdata value="Load sample customers" />
<hr />
<b>Products</b>
<p>Stuff to sell. There's a lot of sample data. I think this might
be the Wedge's or at least a snapshot of it.</p>
<input type=submit name=products value="Load sample products" />
<hr />
<b>Departments</b> &amp; <b>Subdepts</b>
<p>Products get categorized into departments &amp; subdepartments.
You can also ring amounts directly to a department. Not needed,
strictly speaking, for a basic lane (Ring up items, total, 
accept tender, provide change).</p>
<input type=submit name=depts value="Load sample departments" />
</form>
</div> <!--	wrapper -->
</body>
</html>
