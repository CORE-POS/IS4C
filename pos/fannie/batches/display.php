<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

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
require_once('../src/mysql_connect.php');

$batchID = $_GET['batchID'];

$getBatchIDQ = "SELECT max(batchID) FROM batches";
$getBatchIDR = mysql_query($getBatchIDQ);
$getBatchIDW = mysql_fetch_row($getBatchIDR);

foreach ($_POST AS $key => $value) {
    $$key = $value;
    //echo $key .": " . $value . "<br>";
}

if($getBatchIDW[0] < $batchID){
   if($batchType == 6){
      $discounttype = 2;
   }else{
      $discounttype = 1;
   }
 
   $insBatchQ = "INSERT INTO batches(startDate,endDate,batchName,batchType,discounttype) 
                 VALUES('$startDate','$endDate','$batchName',$batchType,$discounttype)";
   //echo $insBatchQ;
   $insBatchR = mysql_query($insBatchQ);
}
//echo $batchID;
?>
	<FRAMESET rows='100,*' frameborder='0'>
	   <FRAME src='addItems.php?batchID=<?php echo $batchID; ?>' name='add' border='0' scrolling='no'>
	   <FRAME src='batches.php?batchID=<?php echo $batchID; ?>' name='items' border='0' scrolling='yes'>
	</FRAMESET>
