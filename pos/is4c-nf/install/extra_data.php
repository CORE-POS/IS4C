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
<h2><?php echo _('IT CORE Lane Installation: Sample Data'); ?></h2>

<form action=extra_data.php method=post>
<div class="alert success"><b>
<?php
$db = new \COREPOS\pos\lib\SQLManager(CoreLocal::get('localhost'),
    CoreLocal::get('DBMS'),
    CoreLocal::get('pDatabase'),
    CoreLocal::get('localUser'),
    CoreLocal::get('localPass'));

if (isset($_REQUEST['employees'])){
    echo _("Loading employees");
    $db->query("TRUNCATE TABLE employees");
    Loader::loadSampleData($db,'employees');    
}
elseif(isset($_REQUEST['custdata'])){
    echo _("Loading custdata");
    $db->query("TRUNCATE TABLE custdata");
    Loader::loadSampleData($db,'custdata');
}
elseif(isset($_REQUEST['products'])){
    echo _("Loading products");
    $db->query("TRUNCATE TABLE products");
    Loader::loadSampleData($db,'products');
}
elseif (isset($_REQUEST['tenders'])){
    echo _("Loading tenders");
    $db->query("TRUNCATE TABLE tenders");
    Loader::loadSampleData($db,'tenders');
} elseif(isset($_REQUEST['depts'])){
    echo _("Loading departments");
    $db->query("TRUNCATE TABLE departments");
    Loader::loadSampleData($db,'departments');
    echo _("<br />Loading super departments");
    $db->query("TRUNCATE TABLE MasterSuperDepts");
    Loader::loadSampleData($db,'MasterSuperDepts');
} elseif(isset($_REQUEST['quicklookups'])){
    echo _("Loading QuickLookups");
    $db->query("TRUNCATE TABLE QuickLookups");
    Loader::loadSampleData($db,'QuickLookups');
}
?>
</b></div>
<p><?php echo _('Some sample data is available to get a test lane
up & running quickly. Keep in mind this data
overwrites whatever is currently in the table.'); ?></p>
<hr />
<b>Employees</b>
<p><?php echo _("This table contains login information for cashiers. The two
included logins are '56' and '7000'."); ?></p>
<input id="data" type=submit name=employees value="<?php echo _('Load sample employees'); ?>" />
<hr />
<b>Custdata</b>
<p><?php echo _('Customer data is the membership information. Sample data includes
 a way too many mostly identical lines. A very scrubbed version
of someone\'s customer table I think.'); ?></p>
<input type=submit name=custdata value="<?php echo _('Load sample customers'); ?>" />
<hr />
<b>Products</b>
<p><?php echo _('Stuff to sell. There\'s a lot of sample data. I think this might
be the Wedge\'s or at least a snapshot of it.'); ?></p>
<input type=submit name=products value="<?php echo _('Load sample products'); ?>" />
<hr />
<b>Departments</b> &amp; <b>Superdepts</b>
<p><?php echo _('Products get categorized into departments & super departments.
You can also ring amounts directly to a department. Not needed,
strictly speaking, for a basic lane (Ring up items, total, 
accept tender, provide change).'); ?></p>
<input type=submit name=depts value="<?php echo _('Load sample departments'); ?>" />
<hr />
<b>Tenders</b>
<p><?php echo _('Methods of payment such as cash, check, credit, etc'); ?></p>
<input type=submit name=tenders value="<?php echo _('Load sample tenders'); ?>" />
<?php if ($db->table_exists('QuickLookups')) { ?>
<hr />
<b>Quick Lookups</b>
<p><?php echo _('Basic menus for QuickKeys and QuickLookups plugins'); ?></p>
<input type=submit name=quicklookups value="<?php echo _('Load sample menus'); ?>" />
<?php } ?>
</form>
</div> <!--    wrapper -->
</body>
</html>
