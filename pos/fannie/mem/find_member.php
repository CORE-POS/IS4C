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
// A page to search the member base.
$page_title='Fannie - Member Management Module';
$header='Find A Member';
include('../src/header.html');
include ('./includes/header.html');

require_once('../src/mysql_connect.php');

if ((isset($_POST['submitted'])) || (isset($_GET['sort']))) { // If the form has been submitted or sort columns have been clicked, check the data and display the results.
	
	// Initialize the errors array.
	$errors = array();
	
	// Validate the form data.
	if (isset($_POST['search_method'])){
		switch ($_POST['search_method']) { // How do they want to search?
			
			case 'fn':
			if (empty($_POST['first_name'])) {
			
				$errors[] = 'You left their first name blank.';
			
			} else {
				$fn = escape_data($_POST['first_name']); // Store the first name.
				$sm = "c.FirstName LIKE '$fn%'";
			}
			break;
		
			case 'ln':
			if (empty($_POST['last_name'])) {
			
				$errors[] = 'You left their last name blank.';
			
			} else {
				$ln = escape_data($_POST['last_name']); // Store the last name.
				$sm = "c.LastName LIKE '$ln%'";
			}
			break;
			
			case 'cn':
			if (empty($_POST['card_no'])) {
				
				$errors[] = 'You left their member number blank.';
				
			} else {
				$cn = escape_data($_POST['card_no']); // Store the member number.
				$sm = "c.cardno = $cn";
			}
			break;
			
			case 'ds':
			if (empty($_POST['ps_discount'])) {
				
				$errors[] = 'You left the discount field blank.';
				
			} else {
				$ds = escape_data($_POST['ps_discount']);
				$sm = "c.discount = $ds";
			}
			break;
			
			case 'mt':
			if (empty($_POST['ps_staff'])) {
				
				$errors[] = 'You left the member type field blank.';
				
			} else {
				$mt = escape_data($_POST['ps_staff']);
				$sm = "c.staff = $mt";
			}
			break;

			case 'ms':
			if (empty($_POST['ps_memtype'])) {
				
				$errors[] = 'You left the member status blank.';
				
			} else {
				$ms = escape_data($_POST['ps_memtype']);
				$sm = "c.memType = $ms";
			}
			break;
  		}
	} else {$sm = $_GET['sm'];}
	if (empty($errors)) {
		$sm = stripslashes($sm);
		$query = "SELECT * FROM custdata c WHERE " . $sm;
		$result = $dbc->query($query);
		
		if ($dbc->num_rows($result) == 0) { // No results
			echo '<h1 id="mainhead">Error!</h1>
			<p class="error">Your search yielded no results.</p>';
		} else { // Results!
			
			$link1 = "{$_SERVER['PHP_SELF']}?sort=lna";
			$link2 = "{$_SERVER['PHP_SELF']}?sort=fna";
			$link3 = "{$_SERVER['PHP_SELF']}?sort=cna";
			
			// Determine the sorting order.
			$order_by = 'c.CardNo DESC';
			$sort = 'cnd';
			if (isset($_GET['sort'])) { // If a non-default sort has been chosen.
				
				// Use existing sorting order.
				switch ($_GET['sort']) {
					
					case 'lna':
					$order_by = 'c.LastName ASC';
					$link1 = "{$_SERVER['PHP_SELF']}?sort=lnd";
					break;
					
					case 'lnd':
					$order_by = 'c.LastName DESC';
					$link1 = "{$_SERVER['PHP_SELF']}?sort=lna";
					break;
					
					case 'fna':
					$order_by = 'c.FirstName ASC';
					$link2 = "{$_SERVER['PHP_SELF']}?sort=fnd";
					break;
					
					case 'fnd':
					$order_by = 'c.FirstName DESC';
					$link2 = "{$_SERVER['PHP_SELF']}?sort=fna";
					break;
					
					case 'cna':
					$order_by = 'c.CardNo ASC';
					$link3 = "{$_SERVER['PHP_SELF']}?sort=drd";
					break;
					
					case 'cnd':
					$order_by = 'c.CardNo DESC';
					$link3 = "{$_SERVER['PHP_SELF']}?sort=dra";
					break;
					
					default:
					$order_by = 'c.CardNo DESC';
					break;
					
				}
				
				// $sort will be appended to the pagination links.
				$sort = $_GET['sort'];
				
			}
					
			
			// Make the query using the LIMIT function and the $start information.
			$query = "SELECT c.LastName AS LastName, 
				c.FirstName AS FirstName, 
				c.CardNo AS CardNo, 
				m.memDesc as type, 
				c.discount AS disc
				FROM custdata c, memtype m
				WHERE m.memtype = c.memType 
				AND $sm 
				AND c.CardNo NOT IN(9999,99999)
				ORDER BY $order_by ";

			$result = $dbc->query ($query);

			// Display the  number of matches.
			echo '<h1 id="mainhead">Search Results</h1>
			<p>The following <b>( ' . $dbc->num_rows($result) . ' )</b> members matched your search string:</p>';
						
			// Table header.
			echo '<table align="center" width="100%" cellspacing="0" cellpadding="5" >
			<tr>
			<td align="left"><a href="' . $link3 . '&s=' . $start . '&np=' . $num_pages . '&sm=' . $sm . '"><b>Member #</b></a></td>
			<td align="left"><a href="' . $link1 . '&s=' . $start . '&np=' . $num_pages . '&sm=' . $sm . '"><b>Last Name</b></a></td>
			<td align="left"><a href="' . $link2 . '&s=' . $start . '&np=' . $num_pages . '&sm=' . $sm . '"><b>First Name</b></a></td>
			<td align="left">Disc.</td>
			<td align="left">Mem Type</td>
			</tr>';
			
			// Fetch and print all the records.
			$bg = '#eeeeee'; // Set background color.
			while ($row = $dbc->fetch_array ($result)) {
				$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
				echo '<tr bgcolor="' . $bg . '">';
//				echo '<td align="left"><a href="mem_edit.php?id=' . $row['id'] . '">Edit</a></td>';
				echo '<td align="left"><a href="modify_household.php?cardno=' .$row['CardNo']. '"><b>' . $row['CardNo'] . '</b></a></td>
				<td align="left"><b>' . $row['LastName'] . '</b></td>
				<td align="left"><b>' . $row['FirstName'] . '</b></td>
				<td align="center">' . $row['disc'] . '</td>
				<td align="left">' . substr($row['type'],0,5) . '</td>
				</tr>';
			}
			
			echo '</table>';
		}
					
	} else { // Report the errors.
		
		echo '<h1 id="mainhead">Error!!</h1>
		<p class="error">The following error(s) occurred:<br />';
		foreach ($errors as $msg) { // Print each error.
			echo " - $msg<br />\n";
		}
		echo '</p><p>Please try again.</p><p><br /></p>';
			
	} // End of if (empty($errors)) IF.
		
} // End of submit conditional.

// Always show the form.
$query = "SELECT staff_no, staff_desc FROM staff ORDER BY staff_no ASC";
$query2 = "SELECT memtype, memDesc FROM memtype ORDER BY memtype ASC";
$query3 = "SELECT Discount FROM custdata GROUP BY Discount ORDER BY Discount";
$result = $dbc->query($query);
$result2 = $dbc->query($query2);
$result3 = $dbc->query($query3);


	
	// Create the form.
	echo '<h2>Find a Member.</h2>
	<p>Select <b>one</b> of the below search options.</p>
	<form action="find_member.php" method="post">
	<p><input type="radio" name="search_method" value="fn" />First Name: <input type="text" name="first_name" size="15" maxlength="15"';
	if (isset($_POST['first_name'])) {echo ' value="' . $_POST['first_name'] . '"';}
	echo ' /></p>
	<p><input type="radio" name="search_method" value="ln" />Last Name: <input type="text" name="last_name" size="15" maxlength="30"';
	if (isset($_POST['last_name'])) {echo ' value="' . $_POST['last_name'] . '"';}
	echo ' /></p>
	<p><input type="radio" name="search_method" value="cn" CHECKED />Member Number: <input type="text" name="card_no" size="6" ';
	if (isset($_POST['card_no'])) {echo ' value="' . $_POST['card_no'] . '"';}
	echo ' /></p>';
	echo '<p><input type="radio" name="search_method" value="ds" />Discount: <select name="ps_discount">';
	while($row = $dbc->fetch_array($result3))
		printf("<option value=%d>%d%%</option>",$row[0],$row[0]);
	echo '</select></p>';
	echo '<p><input type="radio" name="search_method" value="ms" />Member Type: <select name="ps_memtype">';
	while ($row2 = $dbc->fetch_array($result2)) {
		echo '<option value='. $row2['memtype'];
		if ($row2['memtype'] == 1) {echo ' SELECTED';}
		echo '>' . $row2['memDesc'];
	}
	echo '</select>
	<p><input type="submit" name="submit" value="Submit" /></p>
	<input type="hidden" name="submitted" value="TRUE" />
	</form>';

include('../src/footer.html');
?>
