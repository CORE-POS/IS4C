<?php
include('../../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (isset($_GET["delete"])){
	$delQ = "delete from newItemBatches where batchID=".$_GET["delete"];
	$delR = $sql->query($delQ);
	$delQ = "delete from newItemsBatchList where batchID=".$_GET["delete"];
	$delR = $sql->query($delQ);
}

?>
<html>
<head><title>Start here...</title></head>
<body>Select a buyer 
<form action=newBatchEdit.php method=post>
<select name=buyer>
   <option value=1>Bulk</option>
   <option value=2>Cool</option>
   <option value=4>Grocery</option>
   <option value=5>HBC</option>
   <option value=8>Meat</option>
   <option value=9>Gen Merch</option>
</select>
<br />
<?php

$q = "select batchID,batchName from newItemBatches order by batchID desc";
$r = $sql->query($q);
echo "<table border=1 cellpadding=3 cellspacing=0><tr>";
echo "<th>Batch Name</th><th>Delete</th><th>Select</th></tr>";
$selected = "checked";
while ($w = $sql->fetch_array($r)){
	echo "<tr>";
	echo "<td>".$w[1]."</td>";
	echo "<td><a onclick=\"return confirm('Delete batch $w[1]?');\" href=new.php?delete=$w[0]>Delete</a></td>";
	echo "<td><input type=radio name=batchID value=$w[0] $selected /></td>";
	echo "</tr>";
	$selected = "";
}
echo "</table>";
?>
<input type=submit value=Submit>
</form>
</body>
</html>
