<?php
use COREPOS\pos\install\data\Loader;
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
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
$db = new \COREPOS\pos\lib\SQLManager(CoreLocal::get('localhost'),
    CoreLocal::get('DBMS'),
    CoreLocal::get('pDatabase'),
    CoreLocal::get('localUser'),
    CoreLocal::get('localPass'));

if (isset($_REQUEST['employees'])){
    echo "Loading employees";
    $db->query("TRUNCATE TABLE employees");
    Loader::loadSampleData($db,'employees');    
}
elseif(isset($_REQUEST['custdata'])){
    echo "Loading custdata";
    $db->query("TRUNCATE TABLE custdata");
    Loader::loadSampleData($db,'custdata');
}
elseif(isset($_REQUEST['products'])){
    echo "Loading products";
    $db->query("TRUNCATE TABLE products");
    Loader::loadSampleData($db,'products');
}
elseif (isset($_REQUEST['tenders'])){
    echo "Loading tenders";
    $db->query("TRUNCATE TABLE tenders");
    Loader::loadSampleData($db,'tenders');
} elseif(isset($_REQUEST['depts'])){
    echo "Loading departments";
    $db->query("TRUNCATE TABLE departments");
    Loader::loadSampleData($db,'departments');
    echo "<br />Loading super departments";
    $db->query("TRUNCATE TABLE MasterSuperDepts");
    Loader::loadSampleData($db,'MasterSuperDepts');
} elseif(isset($_REQUEST['quicklookups'])){
    echo "Loading QuickLookups";
    $db->query("TRUNCATE TABLE QuickLookups");
    Loader::loadSampleData($db,'QuickLookups');
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
<b>Departments</b> &amp; <b>Superdepts</b>
<p>Products get categorized into departments &amp; super departments.
You can also ring amounts directly to a department. Not needed,
strictly speaking, for a basic lane (Ring up items, total, 
accept tender, provide change).</p>
<input type=submit name=depts value="Load sample departments" />
<hr />
<b>Tenders</b>
<p>Methods of payment such as cash, check, credit, etc</p>
<input type=submit name=tenders value="Load sample tenders" />
<?php if ($db->table_exists('QuickLookups')) { ?>
<hr />
<b>Quick Lookups</b>
<p>Basic menus for QuickKeys and QuickLookups plugins</p>
<input type=submit name=quicklookups value="Load sample menus" />
<?php } ?>
</form>
</div> <!--    wrapper -->
</body>
</html>
