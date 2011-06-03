<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

require('../../config.php');
require($FANNIE_ROOT.'src/mysql_connect.php');

$page_title = 'Fannie - Clear Shelf Tags';
$header = 'Clear Shelf Tags';
include($FANNIE_ROOT.'src/header.html');

$id = 0;
if (isset($_GET['id'])) $id = $_GET['id'];
$checkNoQ = "SELECT * FROM shelftags where id=$id";
$checkNoR = $dbc->query($checkNoQ);

$checkNoN = $dbc->num_rows($checkNoR);
if($checkNoN == 0){
   echo "Barcode table is already empty. <a href='index.php'>Click here to continue</a>";
}else{
   if(isset($_GET['submit']) && $_GET['submit']==1){
      echo "<body bgcolor='669933'>";
      
      $deleteQ = "DELETE FROM shelftags WHERE id=$id";
	echo $deleteQ;
      $deleteR = $dbc->query($deleteQ);
      echo "Barcode table cleared <a href='index.php'>Click here to continue</a>";
   }else{
      echo "<body bgcolor=red";
      echo "<b><a href='dumpBarcodes.php?id=$id&submit=1'>Click here to clear barcodes</a></b>";
   }
}

include($FANNIE_ROOT.'src/footer.html');
?>
