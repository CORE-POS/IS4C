<?php
/*******************************************************************************

    Copyright 2007 Alberta Cooperative Grocery, Portland, Oregon.

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

// view_members.php (07-29-2007 (haus))

// This script retrieves all the records from the membership table.


$$page_title='Fannie - Member Management Module';
$header='Search & View Members';
include('../src/header.html');
include('./includes/header.html');

// Page header.
echo'<h1 id="mainhead">Membership</h1>';

require_once ('../src/mysql_connect.php'); // Connect to the DB.

// How many records per page.
$display = 50;

$query = "SELECT COUNT(id) FROM custdata ORDER BY CardNo ASC"; // Count the number of records.
$result = @mysql_query($query); // Run the query.
$row = mysql_fetch_array($result, MYSQL_NUM); // Retrieve the query.
$num_records = $row[0]; // Store the results.

// Determine how many pages there are.
if (isset($_GET['np'])) { // Already been determined.
	$num_pages = $_GET['np'];
} else { // Need to determine.
	
	// Calculate the number of pages.
	if ($num_records > $display) { // If there are more than one page of records.
		$num_pages = ceil ($num_records/$display);
	} else {
		$num_pages = 1; // There is only one page.
	}
} // End of page count IF.

// Determine where the page is starting.
if (isset($_GET['s'])) { // If we've been through this before.
	$start = $_GET['s'];
} else { // If this is the first time.
	$start = 0;
}

$link1 = "{$_SERVER['PHP_SELF']}?sort=lna";
$link2 = "{$_SERVER['PHP_SELF']}?sort=fna";
$link3 = "{$_SERVER['PHP_SELF']}?sort=cna";

// Determine the sorting order.
if (isset($_GET['sort'])) { // If a non-default sort has been chosen.
	
	// Use existing sorting order.
	switch ($_GET['sort']) {
		
		case 'lna':
		$order_by = 'LastName ASC';
		$link1 = "{$_SERVER['PHP_SELF']}?sort=lnd";
		break;
		
		case 'lnd':
		$order_by = 'LastName DESC';
		$link1 = "{$_SERVER['PHP_SELF']}?sort=lna";
		break;
		
		case 'fna':
		$order_by = 'FirstName ASC';
		$link2 = "{$_SERVER['PHP_SELF']}?sort=fnd";
		break;
		
		case 'fnd':
		$order_by = 'FirstName DESC';
		$link2 = "{$_SERVER['PHP_SELF']}?sort=fna";
		break;
		
		case 'cna':
		$order_by = 'CardNo ASC';
		$link3 = "{$_SERVER['PHP_SELF']}?sort=drd";
		break;
		
		case 'cnd':
		$order_by = 'CardNo DESC';
		$link3 = "{$_SERVER['PHP_SELF']}?sort=dra";
		break;
		
		default:
		$order_by = 'CardNo DESC';
		break;
		
	}
	
	// $sort will be appended to the pagination links.
	$sort = $_GET['sort'];
	
} else { // Use the default sorting order.
	$order_by = 'CardNo DESC';
	$sort = 'cnd';
}
		

// Make the query using the LIMIT function and the $start information.
$query = "SELECT c.LastName AS LastName, 
	c.FirstName AS FirstName, 
	c.CardNo AS CardNo, 
	c.discount AS discount, 
	s.staff_desc AS type,
	m.memDesc AS status,
	c.id as id 
	FROM custdata c, staff s, memtype m 
	WHERE s.staff_no = c.staff AND m.memtype = c.memType 
	AND CardNo != 9999 AND CardNo != 99999 
	ORDER BY $order_by 
	LIMIT $start, $display";

$result = @mysql_query ($query);

// Display the current number of registered users.
echo "<p>There are currently $num_records members.</p>\n";

// Table header.
echo '<table align="center" width="100%" cellspacing="0" cellpadding="4">
<tr>
<td align="center"><b><a href="' . $link3 . '&s=' . $start . '&np=' . $num_pages . '">Mem Num</a></b></td>
<td align="center"><b><a href="' . $link1 . '&s=' . $start . '&np=' . $num_pages . '">Last Name</a></b></td>
<td align="center"><b><a href="' . $link2 . '&s=' . $start . '&np=' . $num_pages . '">First Name</a></b></td>
<td align="center">Disc.</td>
<td align="center">mem type</td>
<td align="center">mem status</td>
</tr>';

// Fetch and print all the records.
$bg = '#eeeeee'; // Set background color.
while ($row = mysql_fetch_array ($result, MYSQL_ASSOC)) {
	$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
	echo '<tr bgcolor="' . $bg . '">
	<td align="center"><a href="modify_household.php?cardno=' . $row['CardNo'] . '">' . $row['CardNo'] . ' </a></td>
	<td align="left">' . $row['LastName'] . '</td>
	<td align="left">' . $row['FirstName'] . '</td>
	<td align="right">' . $row['discount'] . '</td>
	<td align="left">' . substr($row['type'],0,5) . '</td>
	<td align="left">' . substr($row['status'],0,5) . '</td>
	</tr>';
}

echo '</table>';

mysql_free_result ($result); // Free up the resources.

mysql_close(); // Close the database connection.

// Make the links to other pages, if necessary.
if ($num_pages > 1) {
	echo '<br /><p>';
	// Determine what page the script is on.
	$current_page = ($start/$display) + 1;
	
	// If it's not on the first page, make a Previous button.
	if ($current_page != 1) {
		echo '<a href="view_members.php?s=' . ($start - $display) . '&np=' . $num_pages . '&sort=' . $sort . '">Previous</a> ';
	}
	
	// Make all the numbered pages.
	for ($i = 1; $i <= $num_pages; $i++) {
		if ($i != $current_page) {
		echo '<a href="view_members.php?s=' . ($display * ($i - 1)) . '&np=' . $num_pages . '&sort=' . $sort . '">' . $i . '</a> ';
		} else {
			echo $i . ' ';
		}
	}
	
	// If it's not the last page, make a Next button.
	if ($current_page != $num_pages) {
		echo '<a href="view_members.php?s=' . ($start + $display) . '&np=' . $num_pages . '&sort=' . $sort . '">Next</a> ';
	}
	echo '</p>';
} // End of links section.

include ('./includes/footer.html'); // Include the HTML footer.
include('../src/footer.html');
?>
