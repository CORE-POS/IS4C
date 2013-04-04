<?php 
header("Location: /git/fannie/mem/mailList.php");
/*
   header("Content-Disposition: inline; filename=mailingList.xls");
   header("Content-Description: PHP3 Generated Data");
   header("Content-type: application/vnd.ms-excel; name='excel'");
*/
echo "<head>";
echo "<title>Mailing List</title>";
echo "</head>";
echo "<body>";
if (!class_exists("SQLManager")) include($_SERVER["DOCUMENT_ROOT"]."/sql/SQLManager.php");
include('../db.php');

$query = "SELECT CardNo, 
          LastName, 
          FirstName, 
          street,
          city,
          state,
          zip,
          phone,
          memType,
          end_date
          FROM custdata AS c
	  LEFT JOIN meminfo AS m
	  ON c.cardno=m.card_no
	  LEFT JOIN memDates AS d
	  ON c.cardno=d.card_no
          WHERE 
          memType in (1,3)
	  AND c.Type='PC'
          AND (end_date > now() or end_date = '')
          AND ads_OK = 1
          AND PersonNum = 1
          order by m.card_no";

$result = $sql->query($query,$db);

echo '<table>';
while($row = $sql->fetch_row($result)){
   echo '<tr><td>'. $row[0].'</td><td>'.$row[1];
   if (strstr($row[2],"\n") === False)
	   echo '</td><td>'.$row[2].'</td><td>&nbsp;';
   else {
	$pts = explode("\n",$row[2]);
	echo '</td><td>'.$pts[0].'</td><td>'.$pts[1];
   }
   echo '</td><td>'.$row[3].'</td><td>'.$row[4];
   echo '</td><td>'.$row[5].'</td><td>'.$row[6];
   echo '</td><td>'.$row[7].'</td><td>'.$row[8];
   echo '</td></tr>';
}

?>

</body>
</html>
