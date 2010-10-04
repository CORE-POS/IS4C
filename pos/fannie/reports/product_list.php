<?php
require_once('../src/mysql_connect.php');

if(isset($_POST['submit']) || isset($_GET['sort'])) {

echo "<html><head><title>Department Product List</title>
	<script type=\"text/javascript\" src=\"../src/tablesort.js\"></script>
	<link rel='stylesheet' href='../src/style.css' type='text/css' />
	<link rel='stylesheet' href='../src/tablesort.css' type='text/css' /></head>";
	
if (isset($_GET['sort'])) {
	foreach ($_GET AS $key => $value) {
		$$key = $value;
		//echo $key ." : " .  $value."<br>";
	}
} else {
	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}	
}
echo "<body>";

$today = date("F d, Y");	

if (isset($allDepts)) {
	$deptArray = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23";
	$arrayName = "ALL DEPARTMENTS";
} else {
	if (isset($_POST['dept'])) {$deptArray = implode(",",$_POST['dept']);}
	elseif (isset($_GET['dept'])) {$deptArray = $_GET['dept'];}
	$arrayName = $deptArray;
}

if ($inUse==1) {$inUseQ = 'AND inUse = 1';} else {$inUseQ = '';}
$query = "SELECT p.upc AS UPC, 
	p.description AS description,
	p.normal_price AS price, 
	d.dept_name AS dept, 
	s.subdept_name AS subdept, 
	p.foodstamp AS fs, 
	p.scale AS scale, 
	p.inuse AS inuse, 
	p.special_price AS sale
    FROM products AS p INNER JOIN subdepts AS s ON s.subdept_no = p.subdept INNER JOIN departments as d ON d.dept_no = p.department
    WHERE p.department IN ($deptArray)
    $inUseQ";
    // echo $query;
$result = mysql_query($query);
$num = mysql_num_rows($result);

echo "<center><h1>Product List</h1></center>";

echo "<table id=\"output\" cellpadding=0 cellspacing=0 border=0 class=\"sortable-onload-1 rowstyle-alt colstyle-alt\">\n
  <caption>Department range: ".$arrayName.". Search yielded (".$num.") results. Generated on " . date('n/j/y \a\t h:i A') . "</caption>\n
  <thead>\n
    <tr>\n
      <th class=\"sortable-numeric\">UPC</th>\n
      <th class=\"sortable-text\">Description</th>\n
      <th class=\"sortable-currency\">Price</th>\n
      <th class=\"sortable-text\">Dept.</th>\n
      <th class=\"sortable-text\">Subdept.</th>\n
      <th class=\"sortable-text\">FS</th>\n
      <th class=\"sortable-text\">wgh.</th>\n
      <th class=\"sortable-text\">Sale</th>\n		
    </tr>\n
  </thead>\n
  <tbody>\n";

// Fetch and print all the records.
// $bg = '#eeeeee'; // Set background color.
while ($row = mysql_fetch_array ($result, MYSQL_ASSOC)) {
	// $bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
	echo '<tr>
		<td align=right>' . $row["UPC"] . '</td>
		<td>' . $row["description"] . '</td>
		<td align=right>' . money_format('%n',$row["price"]) . '</td>
		<td>' . substr($row["dept"],0,10) . '</td>
		<td>' . substr($row["subdept"],0,20) . '</td>
		<td>'; 
	if ($row["fs"] == 1) { echo 'FS';} else { echo "X";}
	echo '</td><td align=center>';
	if($row["scale"] == 1) { echo '#';} else { echo 'ea.';}
	echo '</td><td align=right><font color=green>';
	if($row["sale"] == 0) { echo '';} else { echo $row["sale"];}
	echo '</font></td></tr>';


}

echo '</table>';

//
// PHP INPUT DEBUG SCRIPT  -- very helpful!
//
/*
function debug_p($var, $title) 
{
    print "<p>$title</p><pre>";
    print_r($var);
    print "</pre>";
}  

debug_p($_REQUEST, "all the data coming in");
*/
} else {
	
$page_title = 'Fannie - Reporting';
$header = 'Product List';
include('../src/header.html');

echo '<form method = "post" action="product_list.php" target="_blank">
	<table border="0" cellspacing="3" cellpadding="5" align="center">
		<tr> 
            <th colspan="2" align="center"> <p><b>Select dept.</b></p></th>
		</tr>
		<tr>';

include('../src/departments.php');

echo '</tr>
        <tr>
			<td>
			<font size="-1"><input type="checkbox" name="inUse" value=1><b>Filter PLUs that aren&apos;t "In Use"?</b></font><br />
			</td>
		</tr>
	<tr> 
			<td><input type=submit name=submit value="Submit"> </td>
			<td><input type=reset name=reset value="Start Over"> </td>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>';

include('../src/footer.html');
}
?>
