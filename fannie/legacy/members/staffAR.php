<?php
include('../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'auth/login.php');

include('../db.php');

if(!validateUserQuiet('staffar')){
?>
   <html>
   <head><title>Please log in</title>
   <link href="../styles.css" rel="styleshhet" type="text/css">
   </head>
   <body>
   <div id=logo><img src='../images/newLogo_small.gif'></div>
   <div id=main><?php
   echo "<a href=\"{$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/members/staffAR.php\">Click here</a> to login<p />";
}else{

  $sql->query("use is4c_trans");

  if(isset($_POST['recalc'])){
    $truncQ = "TRUNCATE TABLE staffAR";
    $truncR = $sql->query($truncQ);

    $query = "SELECT
              c.CardNo,
              c.LastName,
              c.FirstName,
              n.balance as Ending_Balance
              FROM is4c_op.custdata as c INNER JOIN staffID as a ON a.cardNo = c.CardNo
	      LEFT JOIN ar_live_balance AS n ON c.CardNo=n.card_no
              WHERE (c.memType = 9 OR c.memType = 3 OR c.memType = 6)
              and c.personNum = 1
              order by c.LastName";

      $result=$sql->query($query);

      $insARQ = "INSERT INTO staffAR (cardNo, lastName, firstName, adjust)
	      SELECT
              c.CardNo,
              c.LastName,
              c.FirstName,
              n.balance as Ending_Balance
              FROM is4c_op.custdata as c INNER JOIN staffID as a ON a.cardNo = c.CardNo
	      LEFT JOIN ar_live_balance AS n ON c.CardNo=n.card_no
              WHERE (c.memType = 9 OR c.memType = 3 OR c.memType=6)
              and c.personNum = 1
              order by c.LastName";

      $insARR = $sql->query($insARQ);
   }

    
?>  
   <html>
   <head><title>Staff AR Page</title>
   <link href="../styles.css" rel="stylesheet" type="text/css">	
   <script type="text/javascript">
   function recalcCheck(){
	if (confirm("This will load CURRENT account balances. Do you want to continue?"))
		return true;
	return false;
   }
   </script>
   </head>
   <body>

<?php
   if(isset($_POST['submit'])){
      $updateQ = $sql->prepare("UPDATE staffAR SET adjust=? WHERE cardno=?");
      foreach($_POST AS $key=>$value){
         //echo $key . ": ".$value."<br>";
        if($value !='Submit'){
           //echo $updateQ ."<br>";          
           $updateR = $sql->execute($updateQ, array($value, $key));
         }
      }
   }
   
   $query = "SELECT a.*,s.adpID FROM staffAR AS a LEFT JOIN
	staffID AS s ON a.cardNo=s.cardno order by lastName";
   $result = $sql->query($query);

   echo "<form name=upStaffAR method=post action=staffAR.php>";
   echo "<table cellspacing=0 cellpadding=3>";
   echo "<tr><th align=left>Mem#</th><th align=left>ADP#</th><th align=left>Lastname</th><th align=left>Firstname</th>";
   echo "<th align=left>Current deduction</th><th align=left>Change deduction to</th></tr>";
   $sum = 0;
   $colors = array('#ffffff','#ffffaa');
   $c = 1;
   while($row = $sql->fetch_array($result)){
      echo "<tr><td bgcolor=$colors[$c]>".$row['cardNo']."</td>"
	  ."<td bgcolor=$colors[$c]>".$row['adpID']."</td>"
	  ."<td bgcolor=$colors[$c]>".$row['lastName']."</td>"
	  ."<td bgcolor=$colors[$c]>".$row['firstName']."</td>"
          ."<td bgcolor=$colors[$c]>".trim($row['adjust'])."</td>"
	  ."<td bgcolor=$colors[$c]><input type=text name=".$row['cardNo']." value="
          .trim($row['adjust'])."></td></tr>";
      $sum += $row['adjust'];
      echo "<input type=hidden value=".$row['cardNo'].">";
      $c = ($c + 1) % 2;
   }
   echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>Sum:</td>";
   echo "<td>$sum</td><td>&nbsp;</td></tr>";
   echo "<tr><td><input type=submit name=submit value=Submit></td><td><input type=reset value=Reset"
       ." name=reset></td><td><input type=submit value=Recalc name=recalc onclick=\"return recalcCheck();\"></td></tr>";

   echo "</table>";
   echo "</form>";
   echo "</body>";
   echo "</html>";
}
?>

