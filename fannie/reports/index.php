<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../config.php');

$page_title = "Fannie : Reports";
$header = "Reports";
include($FANNIE_ROOT.'src/header.html');
?>
<ul>
<li><span style="font-weight:bold;">Terminology</span>
<ul>
<li>"Buyer" and "Super Department" usually refer to the same kind of thing:
a grouping of Departments. 
Some coops organize their Super Departments by Buyer, the person buying, but others do not.
A Department can be in more than one Super Department,
say by Buyer e.g. "Patricia" and by Category of Product e.g. "Oils and Vinegars",
so in that sense they are like tags.
However, a product (item) cannot be in more than one Department.
</li>
<li>"Vendor", "Distributor" and "Supplier" usually refer to the same kind of thing:
the organization from which the coop obtained, purchased, bought the product.
It may refer to the same organization that made it, the Manufacturer,
but the name used in IS4C may be different.
</li>
<li>"Manufacturer" and "Brand" usually refer to the same kind of thing:
the organization that made the product and often whose name is on it.
Real UPCs are usually supplied by the Manufacturer.
The first five digits, or six, if the first is not 0, digits of the UPC usually identify the
manufacturer, but this is less strict than it used to be,
so that sometimes more leading digits identify the manufacturer.
IS4C UPCs have 13 characters, padded on the left with zeroes, so there may be two or three zeroes
before the first significant digit.
You can usually enter numbers starting with the first non-zero, but zeroes at the end are not assumed.
<li>"UPC" and "PLU" usually refer to the same kind of thing:
The unique code for an item that is scanned or typed at the PoS terminal.
More strictly, PLUs are used on produce and UPCs on packaged goods.
</li>
<li>"Member", "Owner" and "Customer" refer to the same thing: the membership designated by the member number.
It is printed at the end of receipts.
The Membership Card number, the barcode on the card is different; it is used to find the member number.
All member-related things in IS4C are on the member number.
</li>
<li><span style="font-weight:bold;">Download</span>
	<ul>
	<li>"Excel", in newer reports, where "CSV" is also an option, refers to a file
	with formatting similar to that on the page.
	It is for further use in Excel or another similar spreadsheet program.
	In older reports, where there is no "CSV" option, it is more like CSV (raw data).
	</li>
	<li>"CSV" refers to a file of raw data, without formatting.
	Literally, "Comma-Separated Values".  Many applications, including Excel, can import this format.
	</li>
	</ul>
	</li>
	</ul>
</li>
<li><span style="font-weight:bold;">Note</span>
<br />While making these reports it can be useful to have the 
<a href="../item/ItemEditorPage.php" target="_itemEdit">Item Maintenance</a> application
open in a separate tab or window as a reference for Manufacturers and Vendors (Distributors).
</li>
<li><span style="font-weight:bold;">Movement Reports</span> is a collection of reports relating to per-UPC sales stats
	<ul>
	<li><a href="DepartmentMovement/">Department Movement</a> lists sales
		for a department or group of departments over a given date range.</li>
	<li><a href="ManufacturerMovement/">Manufacturer Movement</a> lists sales
		for products from a specific manufacturer over a given date range.
		Manufacturer is given either by name or as a UPC prefix.
		<br />Manufacturer is who made the product, not who the coop bought it from.</li>
	<li><a href="VendorMovement/">Vendor (Distributor) Movement</a> lists sales
		for products from a specific vendor over a given date range.
		Vendor is given by name.  Vendor is sometimes called Distributor;
		it is who the coop bought the product from, not who made it.</li>
	<li><a href="ProductMovement/">Product Movement</a> lists sales for a 
		specific UPC over a given date range.</li>
	<li><a href="CorrelatedMovement/">Correlated Movement</a> shows what
		items purchasers from a certain department or group of departments
		also buy. Optionally, results can be filtered by department too.
		This may be clearer with an example: among transactions that
		include a sandwich, what do sales from the beverages department
		look like?</li>
	<li><a href="NonMovement/">Non-Movement</a> shows items in a department
		or group of departments that have no sales over a given date range.
		This is mostly for finding discontinued or mis-entered products.</li>
	<li><a href="Trends/">Trends</a> shows daily sales totals for items
		over a given date range. Items can be included by UPC, department,
		or manufacturer.</li>
	<li><a href="MonthOverMonth/">Monthly Movement</a> shows monthly sales totals
		for items or departments.</li>
	<li><a href="MovementPrice/">Movement by Price</a> lists item sales with
		a separate line for each price point. If an item was sold at more
		than one price in the given date range, sales from each	price
		are listed separately.</li>
	</ul>
<li><span style="font-weight:bold;">Sales Reports</span> is a higher level collection generally relating to store-wide or per-SuperDepartment sales
	<ul>
	<li><a href="StoreSummary/">Store Summary Report</a> shows total sales, costs and taxes per
		department for a given date range in dollars as well as a percentage
		of store-wide sales and costs. It uses actual item cost if known and estimates
		cost from price and department margin if not; relies on department margins being accurate.</li>
	<li><a href="GeneralSales/">General Sales Report</a> shows total sales per
		department for a given date range in dollars as well as a percentage
		of store-wide sales.</li>
	<li><a href="SalesToday/">Today's Sales</a> shows current day totals by hour.</li>
	<li><a href="SalesAndTaxToday/">Today's Sales and Tax</a> shows current day totals by hour
		and tax totals for the day.</li>
	<li><a href="HourlySales/">Store Hourly Sales</a> lists store-wide sales per hour
		over a given date range.</li>
	<li><a href="HourlySales/hourlySalesDept.php">Department Hourly Sales</a> lists
		sales per hour over a given date range for a subset of departments.</li>
	<li><a href="CustomerPurchases/">CustomerPurchases</a> lists items purchased by a member
		over a given date range.</li>
	</ul>
<li><a href="../item/productList.php">Product List</a> is a cross between a report and 
	a tool. It lists current item prices and status flags for a department or set
	of departments but also allows editing.</li>
<li><a href="PriceHistory/">Price History</a> shows what prices an item as been
	assigned over a given time period.</li>
<li><a href="DepartmentSettings/">Department Settings</a> provides a quick overview
	of current department settings for margin, tax, and foodstamp status.</li>
<li><a href="OpenRings/">Open Rings</a> shows UPC-less sales for a department or
	group of departments over a given date range.</li>
<li><a href="BatchReport/">Batch Report</a> lists sales for items in a 
	sales batch (or group of sales batches).</li>
<li><a href="CustomerCount/">Customer Count</a> lists the number of customers
	per day, separated by membership type.</li>
</ul>

<?php
include($FANNIE_ROOT.'src/footer.html');
?>
