<?php
// HTML niceties
echo "<html><head><title>Deli Scale Items</title></head><body>";

// connect to the database
include('../db.php');

// records per page, not in use right now
$perpage = 25;

// order results by either plu or item description
$orderby = 'plu';
if (isset($_GET['orderby'])){
  $orderby = $_GET['orderby'];
}

// sort ascending or descending
$sort = "Asc";
if (isset($_GET['sort'])){
  $sort = $_GET['sort'];
}

// resorting the currently selected column changes
// its sorting order.  defaults to ascending
$upcsort = "Asc";
if ($orderby == 'plu' and $sort == 'Asc'){
  $upcsort = "Desc";
}

$itemsort = "Asc";
if ($orderby == 'itemdesc' and $sort == 'Asc'){
  $itemsort = "Desc";
}

// current query gets plus and descriptions, ordered as specified
// commented out one is for paging
//$query = "select top $perpage plu, itemdesc from scaleItems order by $orderby $sort";
$query = "select plu,itemdesc from 
    scaleItems 
    order by ";
if (isset($_GET['orderby']) && $_GET['orderby'] == 'itemdesc')
    $query .= 'itemdesc';
else
    $query .= 'plu';
if (isset($_GET['sort']) && $_GET['sort'] == 'Desc'))
    $query .= ' DESC';
$result = $sql->query($query);

// table 'header'
// puts sorting links over columns
echo "<table cellspacing=2 cellpadding=2 border=1>";
echo "<tr>";
echo "<td><a href=scaleList.php?orderby=plu&sort=$upcsort>UPC</a></td>";
echo "<td><a href=scaleList.php?orderby=itemdesc&sort=$itemsort>Description</a></td>";
echo "</tr>";

// kick out the query results into the table
// link the plus to their price change page
while ($row = $sql->fetch_row($result)){
  echo "<tr>";
  echo "<td><a href=productTest.php?upc=$row[0]>$row[0]</a></td>";
  echo "<td>$row[1]</td>";
  echo "</tr>";
}
echo "</table>";

// paging is ridiculous in sql server...
// so it isn't happening right now
// but this gets the number of records
$query = "select count(plu) from scaleItems";
$result = $sql->query($query);
$row = $sql->fetch_row($result);
// and this divides to determine the number of pages
$pagecount = ceil($row[0] / $perpage);

echo "<p />";
echo "<a href=scaleSynch.php>Send all items to the scales</a><br />";
echo "<a href=scaleSynch.php?asnew=yes>Send all items as new</a>";

// wrap up html
echo "</body></html>";

