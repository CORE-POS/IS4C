<html>
<head>	<Title>List of Queries</title>
<link href="../../styles.css" rel="stylesheet" type="text/css">
</head>
	<body>
		<CENTER><font size='4'><Strong>WFC Query List</strong></font></center>
		<br>
		<table border = '1'> 
				<th>Query Title</th>
				<th>&nbsp;</th>
				<th>Description & Documentation (follow link)</th>
			<tr>
				<td><a href=/git/fannie/reports/DepartmentMovement/>Movement Report (a.k.a. Deli Query)</a><br />
				<a href=/git/fannie/reports/ProductMovement/>Single Product Movement Report</a><br>
				<a href=/git/fannie/reports/ManufacturerMovement/>Manufacturer Movement Report</a><br />
				<a href=/git/fannie/reports/VendorMovement/>Vendor Movement Report</a><br />
				<a href=/git/fannie/reports/NonMovement/>Non-Movement Report</a><br />
				<a href=/git/fannie/reports/Correlated/>Correlated Movement</a><br />
				<a href=/git/fannie/reports/MonthOverMonth/>Monthly Movement</a><br />
				<a href=/git/fannie/reports/MovementPrice/>Movement by Price</a><br />
				<a href=/git/fannie/reports/MarginMovement/>Margin Movement (non-sale items)</a>
				</td>
				<td>&nbsp;</td>
				<td valign=top><a href="docs/movement.html">Creates a movement report for specified time period. Sales may be grouped by Date, Dept or UPC/PLU</a></td>
			</tr>
			<tr>
				<td>Batch Maintenance: <a href="/it/newbatch">Old</a> ... <a href="/git/fannie/batches/newbatch">New</a><br />
				<a href=/git/fannie/batches/xlsbatch/>Excel Batch</a>
				</td>
 				<td>&nbsp;</td>
				<td><a href="docs/batches.html">Gateway to the IS4C batch maintenance system</a></td>
			</tr>
			<tr>
				<td><a href=/git/fannie/item/ProductListPage.php>Product List (UPC Sort)</a></td>
				<td>&nbsp;</td>
				<!--<td><a href=lisaCSV.html>Product List (Excel)</a></td>-->
				<td><a href="docs/prodList.html">Creates a product list sorted by UPC/PLU, Department or Desciption for selected Departments, with a secondary sort by UPC.</a></td>
			</tr>
			<tr>
				<td><a href=/git/fannie/reports/HourlySales/>Sales by hour</a></td>
				<td>&nbsp;</td>
				<td>Sales by hour. Optionally limited to a single buyer. Optionally grouped by day of the week</td>
			</tr>
			<tr>
				<td><a href=/git/fannie/reports/HourlyTrans/hourlyTrans.php>Transactions by hour</a></td>
				<td>&nbsp;</td>
				<td>Transactions per hour. Optionally limited to a single buyer. Optionally grouped by day of the week</td>
			</tr>
			<tr>
				<td><a href="/git/fannie/reports/PriceHistory">Price History Report</a></td>
				<td>&nbsp;</td>
				<td>Creates a report of price changes within a given time period</td>
			<!--<tr>
				<td width=225><a href=penny.php>Total hours worked query</a>&nbsp;<img align=bottom src="../images/locked.gif"><br>
                <a href=hours90.php>Rolling 90 day number of hours report</a>&nbsp;<img align=bottom src="../images/locked.gif"><br>
				<a href=penavg.php>Average hours worked query</a>&nbsp;<img align=bottom src="../images/locked.gif"></td><td width=225>&nbsp;</td>
				<td>Reports total or average hours worked by selected employee within defined dates</td>
			</tr>-->
			<tr>
				<td><a href=productTest.php>Price Change page</a>
				<br />
				<a href="/git/fannie/legacy/IT/UNFI/new_items.php">Add new UNFI items</a>
				<!--<br /><a href=moff/productTest.php>MFF Price Change Page</a>-->
				</td>
				<td>&nbsp;</td><td>Price maintenance page for IS4C</td>
			</tr>
			<tr>
				<td><a href=productTest.php>Scale Price Maintenance page</a></td>
				<td>&nbsp</td>
				<td>Price maintenance page for IS4C/Scales</td>
			</tr>
			<tr>
				<td><a href=/git/fannie/item/deleteItem.php>Delete item</a>&nbsp;<img align=bottom src="../images/locked.gif"></td>
				<td></td>
				<td>Delete an item immediately at the till. <font color=red><b>USE WITH CAUTION</b></font></td>
			</tr>
			<tr>
				<td><a href="bulkInventory.php">Bulk Item List</a></td>
				<td>&nbsp;</td>
				<td>Create bulk item list for extending inventory</td>
			</tr>
			<tr>
				<td><a href=/git/fannie/reports/Store-Specific/WFC/LocalSales/>Local Sales by Department</a> </td>
				<td>&nbsp;</td>
				<td>Show local sales report broken out by department/buyer</td>
			</tr>
			<tr>	
				<td><a href=/git/fannie/reports/GeneralSales/>General Sales Report</a></td>
				<td>&nbsp;</td>
				<td>Sales report by department/buyer with defined date (same as Weekly Sales report, but date specific)</td>
			</tr>
			<tr>
				<td><a href=/git/fannie/admin/LookupReceipt/>Reprint receipt</a></td>
				<td>&nbsp;</td>
				<td>Reprint receipt if you know the receipt number and the date of the receipt</td>
			</tr>
			<tr>
				<td><a href=/git/fannie/ordering/>Special Orders</a></td>
				<td>&nbsp;</td>
				<td>Electronic Special Order Interface</td>
			</tr>
			<tr>
			        <td><a href=/git/fannie/item/likecodes/>Manage Like Codes</a></td>
				<td>&nbsp;</td>
				<td>Rename or delete like codes.</td>
			</tr>
			<tr>
				<td><a href=emailReport.php>Email Reporting Tool</a>&nbsp;<img src=../images/locked.gif align=bottom></td>
				<td>&nbsp;</td>
				<td>Use this tool for email report</td>
			</tr>
			<tr>
				<td><a href=/git/fannie/reports/Trends/>Trends Report</a></td>
				<td>&nbsp;</td>
				<td>Track daily item sales</td>
			</tr>
			<tr>
				<td><a href=/git/fannie/reports/CustomerCount/>Customer Report</a></td>
				<td>&nbsp;</td>
				<td>Lists number of shoppers by date &amp; type.</td>
			</tr>
			<tr>
				<td><a href=/links/inv.php>Vendor Invoices</a></td>
				<td>&nbsp;</td>
				<td>Download copies of vendor invoices</td>
			</tr>
			<tr>
				<td><a href=/wiki/>WFC Wiki</a></td>
				<td>&nbsp;</td>
				<td>Mostly IT info/documentation</td>
			</tr>
			<!--
			<tr>
				<td valign=top><a href="http://192.168.1.10/manual.html">Printer Documentation (PDFs)</a></td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			-->
		</table>
    <img src=../images/locked.gif>: indicates password protected areas.<br />
<?php
require('../auth/login.php');
$user = checkLogin();
if ($user){
	echo "You are logged in as <b>$user</b>. <a href=/auth/ui/loginform.php?logout=yes>Logout</a>";
}
else{
	echo "You are not <a href=/auth/ui/loginform.php?redirect=/queries/index.php>logged in</a>.";
}
?>
	</body>
</html>
