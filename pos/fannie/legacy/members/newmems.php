<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
include('functMem.php');

if(isset($_GET['start'])){
header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="newmemsReport.xls"');

$start  = $_GET['start'];
$end = $_GET['end'];

$query = "SELECT m.card_no,lastname,firstname,street,
          city, state, zip, phone
          FROM meminfo AS m LEFT JOIN
	  custdata AS c ON m.card_no=c.cardno
          WHERE c.memtype <> 0
          AND personnum = 1 
          AND m.card_no between $start and $end";

$result = $sql->query($query);


echo "<table>";
while($row = $sql->fetch_array($result)){
   echo '<tr><td>'.$row['card_no'].'</td><td>'.$row['lastname'].
        '</td><td>'.$row['firstname'].'</td><td>';
   if (strstr($row['street'],"\n")===False)
	echo $row['street']."</td><td>&nbsp;";
   else {
	$pts = explode("\n",$row['street']);
	echo $pts[0]."</td><td>".$pts[1];
   }
   echo '</td><td>'.$row['city'].
        '</td><td>'.$row['state'].'</td><td>'.$row['zip'].
        '</td><td>'.$row['phone'].'</td></tr>';
}

echo "</table>";
}else{

?>
<html><head><title>Get new member info</title></head>

<body>
<form name=numbers action=newmems.php method=get>
<input type=text name=start />
<input type=text name=end />
<input type=submit name=submit value=submit />
</form>
</body>
</html>
<?

}
?>
