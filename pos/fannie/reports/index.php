<?php
/*******************************************************************************

    Copyright 2007 People's Food Co-op, Portland, Oregon.

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
$page_title = 'Fannie - Reporting';
$header = 'List available reporting options.';
include('../src/header.html');
?>
	<a href="DepartmentMovement"><font size=4>Department Movement</font></a></br>
	Product movements by department or group of departments
</br></br>
	<a href="ManufacturerMovement"><font size=4>Manufacturer Movement</font></a></br>
	Product movements by manufacturer name or UPC prefix
</br></br>
	<a href="ProductMovement"><font size=4>Item Movement</font></a></br>
	Movement on a single product
</br></br>
	<a href="Correlated"><font size=4>Correlated Movement</font></a></br>
	Overlapping movement between two departments or groups of departments
</br></br>
	<a href="NonMovement"><font size=4>Non Movement</font></a></br>
	Items with zero movement by department or group of departments
</br></br>
	<a href="Trends"><font size=4>Trends</font></a></br>
	Track daily item sales
</br></br>
	<a href="GeneralSales"><font size=4>General Sales Report</font></a></br>
	Sales for a period by super department. Default period is the previous week.
</br></br>
	<a href="SalesToday"><font size=4>Today's Sales</font></a></br>
	Sales from today for the entire store or a super department
</br></br>
	<a href="HourlySales"><font size=4>Store Hourly Sales</font></a></br>
	Hourly sales for the whole store
</br></br>
	<a href="HourlySales"><font size=4>Department Hourly Sales</font></a></br>
	Hourly sales for the a department or group of departments
</br></br>
	<a href="../item/productList.php"><font size=4>Product List</font></a></br>
	List all products for a department or group of departments
</br></br>
	<a href="DepartmentSettings"><font size=4>Department Settings</font></a></br>
	List departments and view their current settings
</br></br>

<?
include('../src/footer.html');
?>
