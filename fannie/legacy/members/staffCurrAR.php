<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
include($FANNIE_ROOT."src/functions.php");

  header("Content-Disposition: inline; filename=queryResults.xls");
  header("Content-Description: PHP3 Generated Data");
  header("Content-type: application/vnd.ms-excel; name='excel'");

$query = "SELECT * FROM staffAR";
select_to_table($query,array(),0,"FFFFFF");

?>
