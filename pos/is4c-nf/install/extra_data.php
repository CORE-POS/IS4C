<?php
include('../ini.php');
include('util.php');
if(!class_exists('SQLManager'))
	include('../lib/SQLManager.php');
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
<a href="index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="extra_config.php">Additional Configuration</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Sample Data
<br />
<form action=extra_data.php method=post>
<blockquote><i>
<?php
$db = new SQLManager($IS4C_LOCAL->get('localhost'),
	$IS4C_LOCAL->get('DBMS'),
	$IS4C_LOCAL->get('pDatabase'),
	$IS4C_LOCAL->get('localUser'),
	$IS4C_LOCAL->get('localPass'));

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
 a way too many mostly identical lines. A very scrubbed version
of someone's customer table I think.<br />
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
</form>
</body>
</html>
