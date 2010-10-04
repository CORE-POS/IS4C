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
?>


<html>
<head>
<link rel="stylesheet" href="../src/style.css" type="text/css" />
</head>
<body>
<?
// include_once($_SERVER["DOCUMENT_ROOT"].'/src/funct1Mem.php');
require_once('../src/mysql_connect.php');

foreach ($_POST AS $key => $value) {
    $$key = $value;
    //echo $key . ': '. $$key . "<br>";
}
// 
// $db = mysql_connect('localhost',$_SESSION["mUser"],$_SESSION["mPass"]);
// mysql_select_db('is4c_op',$db);

$maxBatchIDQ = "SELECT MAX(batchID) FROM batches";
$maxBatchIDR = mysql_query($maxBatchIDQ);
$maxBatchIDW = mysql_fetch_array($maxBatchIDR);

$batchID = $maxBatchIDW[0];

$batchInfoQ = "SELECT * FROM batches WHERE batchID = $batchID";
$batchInfoR = mysql_query($batchInfoQ);
$batchInfoW = mysql_fetch_row($batchInfoR);

//$batchID = 1;
if(isset($_GET['batchID'])){
   $batchID = $_GET['batchID'];
}
if(isset($_GET['submit'])){
   $upc = $upc =str_pad($_GET['upc'],13,0,STR_PAD_LEFT);
   $salePrice = $_GET['saleprice'];
   if(isset($_GET['delete'])){
      $del = $_GET['delete'];
   }
   ;
?>   <script language="javascript">
    parent.frames[1].location.reload();
    </script>
<?
} else {
	$upc = '0';
	$salePrice = '';
	$del = 0;
}
$batchInfoQ2 = "SELECT * FROM batches WHERE batchID = $batchID";
$batchInfoR2 = mysql_query($batchInfoQ2);
$batchInfoW2 = mysql_fetch_assoc($batchInfoR2);

$batch_active = $batchInfoW2['active'];

echo "<form action=addItems.php action=GET>";
echo "<table border=0><tr><td><b>Sale Price: </b><input type=text name=saleprice size=6></td>";
echo "<td><b>UPC: </b><input type=text name=upc></td>";
echo "<input type=hidden name=batchID value=$batchID>";
echo "<td><b>Delete</b><input type=checkbox name=delete value=1>";
echo "<td><input type=submit name=submit value=submit></td></tr></table>";

//echo "this is upc" . $upc;

$selBListQ = "SELECT * FROM batchList WHERE upc = $upc 
              AND batchID = $batchID";
// echo $selBListQ;
$selBListR = mysql_query($selBListQ);
$selBListN = mysql_num_rows($selBListR);

$startDate = $batchInfoW[1];
$endDate = $batchInfoW[2];

$checkItemQ = "SELECT l.* FROM batchList AS l JOIN batches AS b ON b.batchID = l.batchID
               where upc = $upc and b.endDate >= '$startDate'";
// echo $checkItemQ;
$checkItemR = mysql_query($checkItemQ);
$checkItemN = mysql_num_rows($checkItemR);
$checkItemW = mysql_fetch_row($checkItemR);


if($del == 1){
	$delBListQ = "DELETE FROM batchList WHERE upc = $upc AND
		batchID = $batchID";
	$delBListR = mysql_query($delBListQ);
}else{
      if($selBListN == 0){
		$insBItemQ = "INSERT INTO batchList(upc,batchID,salePrice,active)
			VALUES('$upc',$batchID,$salePrice,$batch_active)";
		echo $insBItemQ;
		$insBItemR = mysql_query($insBItemQ);
		
		// echo "<h1>" . $batchInfoW[6] . "</h1>";
		if ($upc != 0 && $batch_active == 1) {
			$prodUpdateQ = "UPDATE products AS p, batches AS b, batchList AS l 
				SET p.special_price = $salePrice, p.start_date = b.startDate, p.end_date = b.endDate, p.discounttype = b.discounttype 
				WHERE l.upc = p.upc AND b.batchID = l.batchID AND b.batchID = $batchID AND p.upc = $upc";
			// echo $prodUpdateQ;
			$prodUpdateR = mysql_query($prodUpdateQ) OR DIE ("<div id=alert><p>ERROR!</p><br />" . mysql_error() . "<br /></div>");
			if ($prodUpdateR) { echo "<div id=alert><p>Products table updated successfully (upc = $upc)</p></div>";}
		}
	}else{
		$upBItemQ = "UPDATE batchList SET salePrice=$salePrice WHERE upc = '$upc' 
			AND batchID = $batchID";
		//echo $upBItemQ;
        $upBItemR = mysql_query($upBItemQ);
		if ($upc != 0 && $batch_active == 1) {
			$prodUpdateQ = "UPDATE products AS p, batches AS b, batchList AS l 
				SET p.special_price = $salePrice, p.start_date = b.startDate, p.end_date = b.endDate, p.discounttype = b.discounttype
				WHERE l.upc = p.upc AND b.batchID = l.batchID AND b.batchID = $batchID AND p.upc = $upc";
			$prodUpdateR = mysql_query($prodUpdateQ) OR DIE ("<div id=alert><p>ERROR!</p><br />" . mysql_error() . "</div>");
			if ($prodUpdateR) { echo "<div id=alert><p>Products table updated successfully</p></div>";}
		}
	}
}

// PHP INPUT DEBUG SCRIPT  -- very helpful!
//

function debug_p($var, $title) 
{
    print "<p>$title</p><pre>";
    print_r($var);
    print "</pre>";
}  

// debug_p($_REQUEST, "all the data coming in");
?>
</body>
</html>