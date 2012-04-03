<?php
include('../../../config.php');
include($FANNIE_ROOT.'legacy/queries/funct1Mem.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$batchID = $_REQUEST['batchID'];
//echo $batchID;
if (isset($_REQUEST['datechange']) && $_REQUEST['datechange'] == "Change Dates"){
  $batchID = $_REQUEST['batchID'];
  $startdate = $_REQUEST['startdate'];
  $enddate = $_REQUEST['enddate'];
  
  $dateQ = "update batchTest set startdate='$startdate',
            enddate='$enddate' where batchID=$batchID";
  $dateR = $sql->query($dateQ);
}
else if(isset($_REQUEST['submit']) && $_REQUEST['submit']=="submit"){
   foreach ($_REQUEST AS $key => $value) {
     $batchID = $_REQUEST['batchID'];
     
     //echo "values".$key . ": ".$value . "<br>";
     if(substr($key,0,4) == 'sale'){
        $$key = $value;
        $upc1 = substr($key,4);
	    $queryTest = "UPDATE batchListTest SET salePrice = $value WHERE upc = '$upc1' and batchID = $batchID";
        //echo $queryTest . "<br>";
	    $resultTest = $sql->query($queryTest);
        $updateBarQ = "UPDATE newbarcodes SET normal_price=$value WHERE upc = '$upc1'";
        $updateBarR = $sql->query($updateBarQ);
      }

     if(substr($key,0,3) == 'del'){
       $$key = $value;
       $upc1 = substr($key,3);
       $infoQ = "select b.batchName,l.salePrice from batchListTest as l left join batchTest as b on b.batchID
		= l.batchID where b.batchID = $batchID and l.upc = '$upc1'";
       $infoR = $sql->query($infoQ);
       $infoW = $sql->fetch_array($infoR);
       $name = $infoW[0];
       preg_match("/priceUpdate(.*?)\d+/",$name,$matches);
       $name = $matches[1];
       $price = $infoW[1];
       $delItmQ = "DELETE FROM batchListTest WHERE upc = '$upc1' and batchID = $batchID";
       $delBarQ = "DELETE FROM shelftags WHERE upc='$upc1' and normal_price=$price";
       //echo $delBarQ."<br />";
       $delItmR = $sql->query($delItmQ);
       $delBarR = $sql->query($delBarQ);
     }
   }   
}

$batchInfoQ = "SELECT * FROM batchTest WHERE batchID = $batchID";
$batchInfoR = $sql->query($batchInfoQ);
$batchInfoW = $sql->fetch_array($batchInfoR);


$selBItemsQ = "SELECT b.*,p.*  from batchListTest as b LEFT JOIN 
               products as p ON p.upc = b.upc WHERE batchID = $batchID 
               ORDER BY b.listID DESC";
//echo $selBItemsQ;
$selBItemsR = $sql->query($selBItemsQ);

echo "<form action=batches.php method=post>";
echo "<table border=1>";
echo "<tr><td>Batch Name: <font color=blue>$batchInfoW[3]</font></td>";
echo "<td>Start Date: <input type=text name=startdate value=\"$batchInfoW[1]\" size=9></td>";
echo "<td>End Date: <input type=text name=enddate value=\"$batchInfoW[2]\" size=9></td>";
echo "<td><input type=submit value=\"Change Dates\" name=datechange></td></tr>";
echo "<input type=hidden name=batchID value=$batchID>";
echo "</form>";
echo "<th>UPC<th>Description<th>Normal Price<th>UNFI SRP<th>Delete";
while($selBItemsW = $sql->fetch_array($selBItemsR)){
   $upc = $selBItemsW[1];
   $field = 'sale'.$upc;
   $del = 'del'.$upc;
   //echo $del;
   echo "<tr><td>$selBItemsW[1]</td><td>{$selBItemsW['description']}</td>";
   echo "<td>{$selBItemsW['normal_price']}</td><td>{$selBItemsW['salePrice']}</td>";
   echo "<input type=hidden name=upc value='$upc'>";
   echo "<td><input type=checkbox value=1 name=$del></td></tr>";
}
echo "<input type=hidden value=$batchID name=batchID>";
echo "<tr><td><input type=submit name=submit value=submit></td><td><a href=forceBatch.php?batchID=$batchID target=blank>Force Sale Batch Now</a></td></tr>";
echo "</form>";

echo "</table>";

?>
