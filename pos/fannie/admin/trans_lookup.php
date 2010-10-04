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

$header = 'Transaction Lookup';
$page_title='Search Transaction History';
include('../src/header.html');

echo '<HEAD><script src="../src/CalendarControl.js" language="javascript"></script>
	<SCRIPT TYPE="text/javascript">
	<!--
	function popup(mylink, windowname)
	{
	if (! window.focus)return true;
	var href;
	if (typeof(mylink) == "string")
	   href=mylink;
	else
	   href=mylink.href;
	window.open(href, windowname, "width=400,height=600,scrollbars=yes,menubar=no,location=no,toolbar=no,dependent=yes");
	return false;
	}
	//-->
	</SCRIPT>
	</HEAD><BODY>';

require_once('../src/mysql_connect.php');

if ((isset($_POST['submitted'])) || (isset($_GET['sort']))) { // If the form has been submitted or sort columns have been clicked, check the data and display the results.
	/*
        foreach ($_POST['search_method'] AS $key => $value) {
                echo "<p>$key</p><p>$value</p>";
        }
        */
        
	
	// Initialize the errors array.
	$errors = array();
	
	// Validate the form data.
        

	if (empty($_POST['date']) && empty($_GET['date'])) {
		$errors[] = 'You left the date field blank.';
	} else {
		if (isset($_POST['date'])) {
                        $da = escape_data($_POST['date']); // Store the date.
                } elseif (isset($_GET['date'])) {
                        $da = escape_data($_GET['date']); // Store the date.
                }
                $sm = "DATE(datetime) = '$da'";
                if ($da == DATE('Y-m-d')) {
                    $transtable = 'dtransactions';
                } else {
					$year = idate('Y',strtotime($da));
					$transtable = 'dlog_' . $year;
                }
	}
        
        if (isset($_POST['submitted'])) {
                // if (is_array($search_method)) {
                // 	if (isset($_POST['search_method'])) {$search = implode(",",$_POST['search_method']);}
                // 	elseif (isset($_GET['search_method'])) {$search = $_GET['search_method'];}
                // } 
                
                if (isset($_POST['ti']) && $_POST['ti'] == 'ti') {
                        if (empty($_POST['trans_id'])) {
                                $error[] = 'You left the transaction number field blank.';
                        } else {
                                // $dtn = escape_data($_POST['trans_id']); 
								// $temp_no = substr($_POST['trans_id'],0,4);
								$tid = explode('-',$_POST['trans_id']);
                                // $sm .= " AND daytrans_no = '$dtn'";
								$sm .= " AND emp_no = $tid[0] AND register_no = $tid[1] AND trans_no = $tid[2]";
                        }
                }
                
                if (isset($_POST['rn']) && $_POST['rn'] == 'rn') {
                        if (empty($_POST['reg_no']) || !is_numeric($_POST['reg_no'])) {
                                $error[] = 'You left the register number field blank or didn\'t enter a number.';
                        } else {
                                $rn = escape_data($_POST['reg_no']); 
                                $sm .= " AND register_no = $rn";
                        }
                }
                
                if (isset($_POST['cn']) && $_POST['cn'] == 'cn') {
                        if (empty($_POST['card_no']) || !is_numeric($_POST['card_no'])) {
                                $error[] = 'You left the member number field blank or didn\'t enter a number.';
                        } else {
                                $cn = escape_data($_POST['card_no']); 
                                $sm .= " AND card_no = $cn";
                        }
                }
                
                if (isset($_POST['em']) && $_POST['em'] == 'em') {
                        if (empty($_POST['cashier'])) {
                                $error[] = 'You didn\'t select a cashier from the list.';
                        } else {
                                $em = escape_data($_POST['cashier']); 
                                $sm .= " AND emp_no = $em";
                        }
                }	

				if (isset($_POST['tt']) && $_POST['tt'] == 'tt') {
						if (empty($_POST['total'])) {
								$error[] = 'You left the subtotal field blank.';
						}
						if (!is_numeric($_POST['total'])) {
								$error[] = 'You entered an invalid amount in the subtotal field.';
						} else {
								$tt = escape_data($_POST['total']);
								$sm .= " AND unitPrice = $tt";
						}
				}
        //	else {$sm = $_GET['sm'];}
        } elseif (isset($_GET['sort'])) {
                // if (is_array($search_method)) {
                // 	if (isset($_POST['search_method'])) {$search = implode(",",$_POST['search_method']);}
                // 	elseif (isset($_GET['search_method'])) {$search = $_GET['search_method'];}
                // } 
                
                if (!empty($_GET['daytrans_no'])) {        
                        $dtn = escape_data($_GET['trans_id']); 
                        $sm .= " AND daytrans_no = '$dtn'";

                }
                
                if (!empty($_GET['reg_no'])) {
                        $rn = escape_data($_GET['reg_no']); 
                        $sm .= " AND register_no = $rn";
                }
                
                if (!empty($_GET['card_no'])) {
                        $cn = escape_data($_GET['card_no']); 
                        $sm .= " AND card_no = $cn";
                }
                
                if (!empty($_GET['cashier'])) {
                        $em = escape_data($_GET['cashier']); 
                        $sm .= " AND emp_no = $em";
                }
                 
       			if (!empty($_GET['total'])) {
                        $tt = escape_data($_GET['total']); 
                        $sm .= " AND emp_no = $tt";
                }
        //	else {$sm = $_GET['sm'];}
        }        
                
                
        if (empty($errors)) {
		$sm = stripslashes($sm);
		$query = "SELECT * FROM is4c_log.$transtable WHERE trans_type = 'C' AND " . $sm;
		// echo "$query";
		$result = @mysql_query($query);
		
		if (mysql_num_rows($result) == 0) { // No results
			echo '<div id="alert"><p class="error">Your search yielded no results.</p></div>';
		} else { // Results!
						
			$query = "SELECT COUNT(*) FROM is4c_log.$transtable WHERE trans_type = 'C' AND $sm"; // Count the number of records.
			$result = @mysql_query($query); // Run the query.
			$row = mysql_fetch_array($result, MYSQL_NUM); // Retrieve the query.
			$num_records = $row[0]; // Store the results.
			
			$link1 = "{$_SERVER['PHP_SELF']}?sort=tia";
			$link2 = "{$_SERVER['PHP_SELF']}?sort=ema";
			$link3 = "{$_SERVER['PHP_SELF']}?sort=cna";
			
			// Determine the sorting order.
			if (isset($_GET['sort'])) { // If a non-default sort has been chosen.
				
				// Use existing sorting order.
				switch ($_GET['sort']) {
					
					case 'tia':
					$order_by = 'time ASC';
					$link1 = "{$_SERVER['PHP_SELF']}?sort=tid";
					break;
					
					case 'tid':
					$order_by = 'time DESC';
					$link1 = "{$_SERVER['PHP_SELF']}?sort=tia";
					break;
					
					case 'ema':
					$order_by = 'emp_no ASC';
					$link2 = "{$_SERVER['PHP_SELF']}?sort=emd";
					break;
					
					case 'emd':
					$order_by = 'emp_no DESC';
					$link2 = "{$_SERVER['PHP_SELF']}?sort=ema";
					break;
					
					case 'cna':
					$order_by = 'card_no ASC';
					$link3 = "{$_SERVER['PHP_SELF']}?sort=cnd";
					break;
					
					case 'cnd':
					$order_by = 'card_no DESC';
					$link3 = "{$_SERVER['PHP_SELF']}?sort=cna";
					break;
					
					default:
					$order_by = 'time DESC';
					break;
					
				}
				
				// $sort will be appended to the pagination links.
				$sort = $_GET['sort'];
				
			} else { // Use the default sorting order.
				$order_by = 'time DESC';
				$sort = 'tid';
			}
					
			
			// Make the query using the LIMIT function and the $start information.
			$query = "SELECT DATE(datetime) AS date, 
				TIME(datetime) AS time,
				emp_no, 
				register_no, 
				trans_no, 
				CONCAT(DATE(datetime),'-',emp_no,'-',register_no,'-',trans_no) AS t_id, 
				CONCAT(emp_no,'-',register_no,'-',trans_no) AS daytrans_no,
				card_no, 
				unitPrice 
				FROM is4c_log.$transtable 
				WHERE trans_type = 'C'
				AND description LIKE 'Subtotal%'
				AND $sm 
				AND emp_no <> 9999 
				GROUP BY t_id
				ORDER BY $order_by";

			$result = mysql_query ($query);
	//		$num_records = mysql_num_rows($result);
			// Display the  number of matches.
			echo '<h1 id="mainhead">Search Results</h1>
				<p>The following <b>( ' . $num_records . ' )</b> transactions matched your search:</p>';
			
	//		echo "$query";
						
			// Table header.
			echo '<table align="center" width="100%" cellspacing="0" cellpadding="5">';
			echo '<tr>
				<td align="center"><a href="' . $link1 . '&date=' . $da . '&daytrans_no=' . $dtn . '&reg_no=' . $rn . '&card_no=' . $cn . '&cashier=' . $em . '"><b>Time (24hr)</b></a></td>
				<td align="center"><b>Trans ID</b></td>
				<td align="center"><a href="' . $link2 . '&date=' . $da . '&daytrans_no=' . $dtn . '&reg_no=' . $rn . '&card_no=' . $cn . '&cashier=' . $em . '"><b>Emp No</b></a></td>
				<td align="center"><a href="' . $link3 . '&date=' . $da . '&daytrans_no=' . $dtn . '&reg_no=' . $rn . '&card_no=' . $cn . '&cashier=' . $em . '"><b>Member #</a></td>
				<td align="center"><b>Subtotal</td>
				</tr>';
			
			// Fetch and print all the records.
			$bg = '#eeeeee'; // Set background color.
			while ($row = mysql_fetch_array ($result, MYSQL_ASSOC)) {
				$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
				echo '<tr bgcolor="' . $bg . '">';
				echo '<td>' . $row['time'] . '</td>
					<td align="left"><a href="trans_receipt.php?t_id=' .$row['t_id']. '&time=' .$row['time']. '&card_no=' .$row['card_no']. '" onClick="return popup(this, \'trans_receipt\')";><b>' . $row['daytrans_no'] . '</b></a></td>
					<td align="left">' . $row['emp_no'] . '</td>
					<td align="left">';
				if ($row['card_no'] == 99999) { echo "NON-MEMBER"; } 
				else { echo $row['card_no']; }
				echo '</td><td align="right">' . money_format('%n',$row['unitPrice']) . '</td>
					</tr>';
			}
			
			echo '</table>';
			
			mysql_free_result ($result); // Free up the resources.

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

// function debug_p($var, $title) 
// {
//     print "<p>$title</p><pre>";
//     print_r($var);
//     print "</pre>";
// }  
// 
// debug_p($_REQUEST, "all the data coming in");

// Always show the form.
$query = "SELECT * FROM employees WHERE EmpActive = 1 ORDER BY FirstName ASC";
$result = @mysql_query($query);

	
	// Create the form.
	echo '<h2>Search Transaction History.</h2>
		<form action="trans_lookup.php" method="post">';
	echo '<table cellpadding=5 border=0><tr><td>
		<p>Date: </td><td><input type="text" name="date" size="11" maxlength="11"';
	if (isset($_POST['date'])) {echo ' value="' . $_POST['date'] . '"';} 
	echo 'onclick="showCalendarControl(this);"> * Required</p></td></tr>';
	echo '<tr><td><p><input type="checkbox" id="ti" name="ti" value="ti">';
	echo 'Transaction ID: </input></td><td><input type="text" name="trans_id" size="15" maxlength="15" onfocus="document.getElementById(\'ti\').checked = \'checked\'"';
	if (isset($_POST['daytrans_no'])) {echo ' value="' . $_POST['daytrans_no'] . '"';}
	echo ' /> like: eeee-r-tt</p></td></tr><td>
		<p><input type="checkbox" name="tt" id="tt" value="tt">Subtotal Amount: </input></p></td><td><input type="text" name="total" size="6" maxlength="6" onclick="document.getElementById(\'tt\').checked = \'checked\'"></td></tr><tr><td>
		<p><input type="checkbox" name="rn" id="rn" value="rn">Register Number: </input></p></td><td><input type="text" name="reg_no" size="2" maxlength="2" onclick="document.getElementById(\'rn\').checked = \'checked\'"></td></tr><tr><td>
		<p><input type="checkbox" name="cn" id="cn" value="cn">Member Number: </input></td><td><input type="text" name="card_no" size="5" maxlength="5" onclick="document.getElementById(\'cn\').checked = \'checked\'"';
	if (isset($_POST['card_no'])) {echo ' value="' . $_POST['card_no'] . '"';} 
	echo ' /></td></tr><tr><td><p><input type="checkbox" name="em" id="em" value="em">Cashier: </input></td><td><select name="cashier" onclick="document.getElementById(\'em\').checked = \'checked\'">';
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo '<option value='. $row['emp_no'] . '>' . $row['FirstName'] . ' ' . substr($row['LastName'],0,1) . '.';
	}
	echo '</select></p></td></tr>
		<tr><td><input type="submit" name="submit" value="Submit" />
		<td> <input type=reset name=reset value="Start Over"> </td>
		<input type="hidden" name="submitted" value="TRUE" /></td>
		</tr></table></form>';

mysql_close(); // Close the DB connection.
include('../src/footer.html');
?>
