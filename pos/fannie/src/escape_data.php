<?php
// Create a function for escaping the data.
function escape_data ($data) {
	global $dbc;
	
	// Address Magic Quotes.
	if (ini_get('magic_quotes_gpc')) {
		$data = stripslashes($data);
	}
	
	$data = $dbc->escape(trim($data));
	
	// Return the escaped value.
	return $data;
} // End of function.
?>
