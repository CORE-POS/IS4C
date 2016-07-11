<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
//session_start();
include('../../../config.php');
if (!class_exists('FannieAPI'))
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$getBatchIDQ = "SELECT max(batchID) FROM batches";
$getBatchIDR = $sql->query($getBatchIDQ);
$getBatchIDW = $sql->fetchRow($getBatchIDR);

$batchID = $_GET['batchID'];

extract($_POST);

if($getBatchIDW[0] < $batchID){
   if($batchType == 6){
      $discounttype = 2;
   }elseif($batchType == 4 || $batchType == 5){
      $discounttype = 0;
   }else{
      $discounttype = 1;
   }
 
}

?>
    <FRAMESET rows='40,*' frameborder='0'>
        <FRAME src=''>
        <FRAME src='batches.php?batchID=<?php echo $batchID; ?>' name='items' border='0' scrolling='yes'>
    </FRAMESET>
