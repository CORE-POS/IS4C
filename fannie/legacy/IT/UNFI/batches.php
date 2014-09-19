<?php
include('../../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');
if (!class_exists('FannieAPI'))
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

$batchID = $_REQUEST['batchID'];
//echo $batchID;
if (isset($_REQUEST['datechange']) && $_REQUEST['datechange'] == "Change Dates"){
  $batchID = $_REQUEST['batchID'];
  $startdate = $_REQUEST['startdate'];
  $enddate = $_REQUEST['enddate'];
  
  $dateP = $sql->prepare("update batchTest set startdate=?,
            enddate=? where batchID=?");
  $dateR = $sql->execute($dateP, array($startdate, $enddate, $batchID));
}
else if(isset($_REQUEST['submit']) && $_REQUEST['submit']=="submit"){
   foreach ($_REQUEST AS $key => $value) {
     $batchID = $_REQUEST['batchID'];
     
     //echo "values".$key . ": ".$value . "<br>";
     if(substr($key,0,4) == 'sale'){
        $$key = $value;
        $upc1 = substr($key,4);
	    $queryTest = $sql->prepare("UPDATE batchListTest SET salePrice = ? WHERE upc = ? and batchID = ?");
        //echo $queryTest . "<br>";
	    $resultTest = $sql->execute($queryTest, array($value, $upc1, $batchID));
        $updateBarQ = $sql->prepare("UPDATE newbarcodes SET normal_price=? WHERE upc = ?");
        $updateBarR = $sql->execute($updateBarQ, array($value, $upc1));
      }

     if(substr($key,0,3) == 'del'){
       $$key = $value;
       $upc1 = substr($key,3);
       $infoQ = $sql->prepare("select b.batchName,l.salePrice from batchListTest as l left join batchTest as b on b.batchID
		= l.batchID where b.batchID = ? and l.upc = ?");
       $infoR = $sql->execute($infoQ, array($batchID, $upc1));
       $infoW = $sql->fetch_array($infoR);
       $name = $infoW[0];
       preg_match("/priceUpdate(.*?)\d+/",$name,$matches);
       $name = $matches[1];
       $price = $infoW[1];
       $delItmQ = $sql->prepare("DELETE FROM batchListTest WHERE upc = ? and batchID = ?");
       $delItmR = $sql->execute($delItmQ, array($upc1, $batchID));
       $delBarR = $sql->execute($delBarQ, array($upc1, $price));
       $tags = new ShelftagsModel($sql);
       $tags->upc($upc1);
       $tags->normal_price($price);
       foreach ($tags->find as $tag) {
           $tag->delete();
       }
     }
   }   
}

$batchInfoQ = $sql->prepare("SELECT * FROM batchTest WHERE batchID = ?");
$batchInfoR = $sql->execute($batchInfoQ, array($batchID));
$batchInfoW = $sql->fetch_array($batchInfoR);


$selBItemsQ = $sql->prepare("SELECT b.*,p.*  from batchListTest as b LEFT JOIN 
               products as p ON p.upc = b.upc WHERE batchID = ?
               ORDER BY b.listID DESC");
//echo $selBItemsQ;
$selBItemsR = $sql->execute($selBItemsQ, array($batchID));

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
