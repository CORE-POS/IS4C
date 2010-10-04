<?php
require_once('../../src/mysql_connect.php');

$query = "DELETE FROM products WHERE upc = " . $_POST['upc'];

$result = mysql_query($query) OR DIE ('database error:' . mysql_error());

if (!$result) { echo "There was a problem.  UPC " . $_POST['upc'] . " was NOT deleted"; }

?>