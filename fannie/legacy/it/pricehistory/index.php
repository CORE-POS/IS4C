<?php
include('../../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

/* provide a department range and date range to
   get history for all products in those departments
   for that time period AND current price

   provide just a upc to get history for that upc
*/
if (isset($_GET['dept1']) || isset($_GET['upc']) || isset($_GET['manufacturer'])){
  $dept1 = isset($_GET['dept1'])?$_GET['dept1']:'';
  $dept2 = isset($_GET['dept2'])?$_GET['dept1']:'';
  $upc = isset($_GET['upc'])?str_pad($_GET['upc'],13,'0',STR_PAD_LEFT):'';
  $start_date = isset($_GET['date1'])?$_GET['date1']:'';
  $end_date = isset($_GET['date2'])?$_GET['date2']:'';
  $manu = isset($_GET['manufacturer'])?$_GET['manufacturer']:'';
  $mtype = isset($_GET['mtype'])?$_GET['mtype']:'';
  
  if (isset($_GET['excel'])){
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="dailyReport.xls"');
  }

  $q = "";
  $args = array();
  if (!isset($_GET['type'])){
    $q = "select upc,description,price,modified from prodUpdate
         where upc = ?
      order by upc,modified desc";
      $args = array($upc);
  }
  else if ($_GET['type'] == 'upc'){
    $q = "select upc,description,price,modified from prodUpdate
         where upc = ? and modified between
      ? AND ?
      order by upc,modified";
      $args = array($upc, $start_date, $end_date);
  }
  else if ($_GET['type'] == 'department'){
    $q = "select upc,description,price,modified from prodUpdate
        where department between ? and ? and modified 
      between ? AND ?
      order by upc, modified";
      $args = array($dept1, $dept2, $start_date, $end_date);
    unset($_GET['upc']);
  }
  else {
    if ($mtype == 'upc'){
      $q = "select upc,description,price,modified from prodUpdate
         where upc like ? and modified
      between ? AND ?
      order by upc,modified";
      $args = array('%'.$manu.'%', $start_date, $end_date);
    }
    else {
      $q = "select p.upc,p.description,p.price,p.modified
        from prodUpdate as p left join prodExtra as x
        on p.upc = x.upc where x.manufacturer=? and
        modified between ? AND ?
        order by p.upc,p.modified";
      $args = array($manu, $start_date, $end_date);
    }
    unset($_GET['upc']);
  }
  $p = $sql->prepare($q);
  $r = $sql->execute($p, $args);

  echo "<table cellspacing=2 cellpadding=2 border=1>";
  echo "<tr><th>UPC</th><th>Description</th>";
  echo "<th>Price</th><th>Date</th>";
  if (!isset($_GET['upc']))
    echo "<th>Current Price</th>";
  echo "</tr>";

  $prevUPC = '';
  $prevPrice = '';
  $prow = '';
  $currentPrice = '';
  $lastprice = '';
  $currQ = $sql->prepare("select price from products where upc=?");
  while ($row = $sql->fetchRow($r)){
    if ($prevUPC != $row['upc']){
    if ($prevUPC != ''){
      echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
      echo "<td>&nbsp;</td><td>&nbsp;</td></tr>";
      $prevPrice = '';
        }
    if (!isset($_GET['upc'])){
      $currR = $sql->execute($currQ, array($row['upc']));
      $currW = $sql->fetchRow($currR);
      $currentPrice = $currW[0];
        }
    /*
    echo "<tr>";
    echo "<td><a href=/queries/productTest.php?upc=$row[0]>$row[0]</td>";
    echo "<td>$row[1]</td>";
    echo "<td>$row[2]</td><td>$row[3]</td>";
    if (!isset($_GET['upc']))
        echo "<td>$currentPrice</td>";
    echo "</tr>";
        */
    }
    else {
    /* eat rows where price didn't change */
    if ($prow["price"] != $row['price']){
      echo "<tr>";
      echo "<td><a href=/queries/productTest.php?upc=$prow[0]>$prow[0]</td>";
      echo "<td>$prow[1]</td>";
      echo "<td>$prow[2]</td><td>$prow[3]</td>";
      if (!isset($_GET['upc']))
        echo "<td>$currentPrice</td>";
      echo "</tr>";
      $lastprice = $row['price'];
    }
    }
    $prevUPC = $row['upc'];
    $prow = $row;
  }
  if ($lastprice != $row['price']){
    echo "<tr>";
    echo "<td><a href=/queries/productTest.php?upc=$prow[0]>$prow[0]</td>";
    echo "<td>$prow[1]</td>";
    echo "<td>$prow[2]</td><td>$prow[3]</td>";
    echo "</tr>";
  }
  echo "</table>";
}
else {
?>
<html>
<head>
<script type=text/javascript>
function showUPC(){
    document.getElementById('upcfields').style.display='block';
    document.getElementById('departmentfields').style.display='none';
    document.getElementById('manufacturerfields').style.display='none';
}
function showDept(){
    document.getElementById('upcfields').style.display='none';
    document.getElementById('departmentfields').style.display='block';
    document.getElementById('manufacturerfields').style.display='none';
}
function showManu(){
    document.getElementById('upcfields').style.display='none';
    document.getElementById('departmentfields').style.display='none';
    document.getElementById('manufacturerfields').style.display='block';
}
</script>
<style type=text/css>
#departmentfields{
    display:none;
}
#manufacturerfields{
    display:none;
}
</style>
</head>
<body onload=showUPC()>
<form method=get action=index.php>
Type: <input type=radio name=type value=upc onclick=showUPC() checked /> UPC 
<input type=radio name=type value=department onclick=showDept() /> Department 
<input type=radio name=type value=manufacturer onclick=showManu() /> Manufacturer
<br />

<div id=upcfields>
UPC: <input type=text name=upc /><br />
</div>

<div id=departmentfields>
Department Start: <input type=text name=dept1 /><br />
Department End: <input type=text name=dept2 /><br />
</div>

<div id=manufacturerfields>
Manufacturer: <input type=text name=manufacturer /><br />
<input type=radio name=mtype value=upc checked /> UPC prefix 
<input type=radio name=mtype value=name /> Manufacturer name<br />
</div>

Start Date: <input type=text name=date1 /><br />
End Date: <input type=text name=date2 /><br />
<input type=submit name=Submit /> <input type=checkbox name=excel /> Excel
</form>
</body>
</html>
<?php
}

