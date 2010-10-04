<html>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<head>
<link rel="stylesheet" href="style.css" type="text/css" charset="utf-8">
<style type="text/css">
	h4 {
		text-align: left;
		font-size: 1.2em;
		line-height: 0.8em;
	}
	#alert {
		width: 60%;
		font-size: 0.8em;
		border: 1px solid;
	    margin: 10px 0px;
	    padding:8px 6px 8px 40px;
	    position:relative;
	    color: #00529B;
	    background-color: #BDE5F8;	
	}
	#alert a {
		text-align:right;
		display: block;
		font-size: 0.8em;
	}
	
	div.banner {
		background: #330000;
		width: 100%;
		height: 26px;
		line-height: 1.2em;
		position: fixed;
		top: 0px;
		left: 0px;
		margin-left: auto;
		margin-right: auto
		border-bottom: 2px solid black;
		/* display: none; */
	}
	div.banner p {
		margin: 0.2em;
		color: white;
		font-size: 0.9em; 
		font-family: Arial, sans-serif;
	}
	div.banner h4 {
		font-family: Arial, sans-serif;
	}
	#table {
		padding-top: 30px;
	}



	#dropdown {
		width:180px;
		border-left:1px dotted white;
		position:fixed;
		top:0px;
		right:0px;
		color:#FFF;
		padding-top: 4px;
		text-align: right;
		font-weight: bold;
		font-family: Arial, sans-serif;
	}
	#dropdown ul {
		list-style:none;
		margin:0;
		padding:0;
		display:none;
		position:absolute;
		top:0;
		left:0;
		background-color:#FFF;
		border: solid 1px #999;
	}
	#dropdown a {
		text-align: left;
		padding:2px 10px;
		width:180px;
		display:block;
		text-decoration:none;
		color:#000;
	}
	#dropdown a:hover {
		color:#000;
		background-color:#CCC;
	}
	#dropdown:hover ul, #dropdown.over ul {
		display:block;
	}

</style>
<script src="jquery-1.2.6.min.js" type="text/javascript" charset="utf-8"></script>
<script src="jquery.tablesorter.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript"> 
	$(function() {
		$("#delete_products").tablesorter({
			widgets: ['zebra'],
			headers: { 0:{sorter: false}}
		});
		
		$(".close").click(function(){
	        $("#alert").slideUp('slow');
	    });
		
	});
	
	function loadPage(list) {
		location.href=list.options[list.selectedIndex].value
	}
	

</script>

</head>
<body>
<?php
require_once('../../src/mysql_connect.php');

if ($_POST['submit']) {
	// debug_p($_REQUEST, "all the data coming in");
	$errors = array();
	$response = array();
	
	foreach ($_POST['deleteItem'] as $del) {
		$result0 = $dbc->query("INSERT INTO legacy_products SELECT * FROM products WHERE upc = $del");
		$result = $dbc->query("DELETE FROM products WHERE upc = $del");
		if (!$result) { $errors[] = "Error: Item $del was not deleted."; }	
		else { $response[] = "Item $del was successfully removed."; }	
	}
	
	echo "<div id='alert'>\n";
	if ($response || $errors) {
		if ($errors) {
			echo "<h4>Errors:</h4><ul>";
			foreach ($errors as $err) {
				echo "<li>" . $err . "</li>";
			}
			echo "</ul>";
		}
		echo "<h4>Results:</h4><ul>";
		foreach ($response as $line) {
			echo "<li>" . $line . "</li>";
		}
		echo "</ul>";
	}
	echo "<a href=# class=\"close\">[ close ]</a>";
	echo "</div>\n";
}

$dept = $_GET['dept'];
// $dept = "3,4,5";

if (!$dept) { $dept = 1; }

$query = "select p.upc as upc, p.description as description, p.normal_price as price, d.dept_name as dept, s.subdept_name as subdept, p.inUse inuse 
	from products p, departments d, subdepts s
	where p.department = d.dept_no and p.subdept = s.subdept_no and p.department IN ($dept) 
	ORDER BY p.modified";

$result = $dbc->query($query);

// echo "<form name='deleteForm' method='post' action='load.php' id='deleteForm'>\n";
echo "<form method='post' action='#'>\n";
echo "<div class='banner'>\n
	<p>DELETE ITEMS: Click checkbox (or use TAB and SPACEBAR) to select items.  Press ENTER/RETURN to submit.  This cannot be undone.\n
	<div id=\"dropdown\" onMouseOver=\"javascript:this.className='over';\" onMouseOut=\"javascript:this.className='';\">
		Select department.\n<ul id=\"dropdown\">\n";
	
$deptResult = $dbc->query("SELECT dept_no, dept_name FROM departments WHERE dept_no <= 20");
while ($deptRow = $dbc->fetch_row($deptResult)) {
	echo "<li><a href=index.php?dept=" . $deptRow['dept_no'] . ">" . ucwords($deptRow['dept_name']) . "</a></li>\n";
}

echo "</ul></div></p></div>\n";

echo "<div id='table'>\n<table id='delete_products' class='tablesorter'>\n
	<thead>\n
		<tr>\n
			<th>&nbsp;</th>\n
			<th>UPC</th>\n
			<th>Description</th>\n
			<th>Price</th>\n
			<th>Dept.</th>\n
			<th>Subdept.</th>\n
			<th>inUse</th>\n
		</tr>\n
	</thead>\n<tbody>\n";
	
while ($row = $dbc->fetch_row($result)) {
	echo "<tr>\n
			<td align='center'><input type='checkbox' name='deleteItem[]' id='deleteItem[]' value=" . $row['upc'] . " />\n
			<td>" . $row['upc'] . "</td>\n
			<td>" . $row['description'] . "</td>\n
			<td align=right>" . money_format("%n", $row['price']) . "</td>\n
			<td>" . $row['dept'] . "</td>\n
			<td>" . $row['subdept'] . "</td>\n<td align='center'>";
			if ($row['inuse'] == 1) { echo "on"; }
			else { echo "off"; }
			echo "</td>\n</tr>\n";
}
echo "</tbody>\n</table>\n</div>\n<input type='submit' value='submit' name='submit' /></form>\n";


//
// PHP INPUT DEBUG SCRIPT  -- very helpful!
//


function debug_p($var, $title) 
{
    print "<h4>$title</h4><pre>";
    print_r($var);
    print "</pre>";
}  


?>
</body>
</html>
