<?php


/* -----------------------start select_to_table-----------------------*/
/* creates a table from query defined outside function. 
   Variables are:
           $query = query to run 
  
   example:
    $x = "SELECT * FROM tlog WHERE TransDate BETWEEN '2004-04-01' AND '2004-04-02' LIMIT 50"
    select_to_table($x);

*/
include('../db.php');

//$my = mysql_connect('localhost','root') 
//   or die("Cannot find server");

function select_cols_to_table($query,$border,$bgcolor,$cols)
{
    global $sql;
        $sum = 0;
        $results = $sql->query($query);
        //echo "<b>query: $query</b>";
        //layout table header
        echo "<table border = $border bgcolor=$bgcolor>\n";
        echo "<tr align left>\n";
        /*for($i=0; $i<5; $i++)
        {
                echo "<th>" . $sql->fieldName($results,$i). "</th>\n";
        }
        echo "</tr>\n"; *///end table header
        //layout table body
        while($row = $sql->fetch_row($results))
        {
                echo "<tr align=left>\n";
                echo "<td >";
                        if(!isset($row[0]))
                        {
                                echo "NULL";
                        }elseif(isset($row[5])){
                                 ?>
                                 <a href="transaction.php?id=<? echo $row[5]; ?>">
                                 <? echo $row[0]; ?></a>
                        <? echo "</td>";
                        }
            else {
                echo $row[0]."</td>";
            }
                for ($i=1;$i<$cols; $i++)
                {
                echo "<td>";
                        if(!isset($row[$i])) //test for null value
                        {
                                echo "NULL";
                        }else{
                                echo $row[$i];
                        }
                        if ($i == 2)
                             $sum += $row[$i];
                        echo "</td>\n";
                } echo "</tr>\n";
        } echo "</table>\n";
        return $sum;
}

function select_to_table($query,$border,$bgcolor)
{
    global $sql;
    $results = $sql->query($query);
    //echo "<b>query: $query</b>";
    //layout table header
    echo "<table border = $border bgcolor=$bgcolor>\n";
    echo "<tr align left>\n";
    /*for($i=0; $i<5; $i++)
    {
        echo "<th>" . $sql->fieldName($results,$i). "</th>\n";
    }
    echo "</tr>\n"; *///end table header
    //layout table body
    while($row = $sql->fetch_row($results))
    {
        echo "<tr align=left>\n";
        echo "<td >";
            if(!isset($row[0]))
            {
                echo "NULL";
            }else{
                 ?>
                 <a href="transaction.php?id=<? echo $row[5]; ?>">
                 <? echo $row[0]; ?></a>
            <? echo "</td>";
            }
        for ($i=1;$i<$number_cols-1; $i++)
        {
        echo "<td>";
            if(!isset($row[$i])) //test for null value
            {
                echo "NULL";
            }else{
                echo $row[$i];
            }
            echo "</td>\n";
        } echo "</tr>\n";
    } echo "</table>\n";
}

/* -------------------------------end select_to_table-------------------*/ 

function prodList_to_table($query,$border,$bgcolor,$upc)
{
    global $sql;
        $results = $sql->query($query); 
        $number_cols = $sql->numFields($results);
        //display query
        //echo "<b>query: $query</b>";
        //layout table header
        echo "<table border = $border bgcolor=$bgcolor>\n";
        echo "<tr align left>\n";
        /*for($i=0; $i<5; $i++)
        {
                echo "<th>" . $sql->fieldName($results,$i). "</th>\n";
        }
        echo "</tr>\n"; *///end table header
        //layout table body
        while($row = $sql->fetch_row($results))
        {
                echo "<tr align=left>\n";
        if($row[0]==$upc){
            echo "<td bgcolor='#CCCCFFF'>";
        }else{
                    echo "<td >";
        }
                
        if(!isset($row[0]))
                        {
                                echo "NULL";
                        }else{
                                 ?>
                                 <a href="productTestLike.php?upc=<? echo $row[0]; ?>">
                                 <? echo $row[0]; ?></a>
                        <? echo "</td>";
                        }
        echo "<td width=250>";
        if(!isset($row[1]))
        {
            echo "NULL";
        }else{
            echo $row[1];
        }    
        echo "</td>";
                for ($i=2;$i<$number_cols; $i++)
                {
            echo "<td width = 55 align=right>";

                        if(!isset($row[$i])) //test for null value
                        {
                                echo "NULL";
                        }else{
                                echo $row[$i];
                        }
                        echo "</td>\n";
                } echo "</tr>\n";
        } echo "</table>\n";
}

function like_to_table($query,$border,$bgcolor)
{
    global $sql;
        $results = $sql->query($query); 
        $number_cols = $sql->numFields($results);
        //display query
        //echo "<b>query: $query</b>";
        //layout table header
        echo "<table border = $border bgcolor=$bgcolor>\n";
        echo "<tr align left>\n";
        /*for($i=0; $i<5; $i++)
        {
                echo "<th>" . $sql->fieldName($results,$i). "</th>\n";
        }
        echo "</tr>\n"; *///end table header
        //layout table body
        while($row = $sql->fetch_row($results))
        {
                echo "<tr align=left>\n";
                echo "<td >";
                        if(!isset($row[0]))
                        {
                                echo "NULL";
                        }else{
                                 ?>
                                 <a href="productTestLike.php?upc=<? echo $row[0]; ?>">
                                 <? echo $row[0]; ?></a>
                        <? echo "</td>";
                        }
                for ($i=1;$i<$number_cols-1; $i++)
                {
                echo "<td>";
                        if(!isset($row[$i])) //test for null value
                        {
                                echo "NULL";
                        }else{
                                echo $row[$i];
                        }
                        echo "</td>\n";
                } echo "</tr>\n";
        } echo "</table>\n";
}




function receipt_to_table($query,$query2,$border,$bgcolor)
{
    global $sql;
    //echo $query2;
    $result = $sql->query($query2);
    $results = $sql->query($query); 
    $number_cols = $sql->numFields($results);
    $number2_cols = $sql->numFields($result);
    //display query
    //echo "<b>query: $query</b>";
    //layout table header
    $row2 = $sql->fetch_row($result);
    $emp_no = $row2[4];    
    //echo $emp_no;
    //$queryEmp = "SELECT * FROM Employees where emp_no = $emp_no";
    //$resEmp = $sql->query($queryEmp,$db);
    //$rowEmp = $sql->fetch_row($resEmp);
    //echo $rowEmp[4];
    
    //echo $query2;
    echo "<table border = $border bgcolor=$bgcolor>\n";
    echo "<tr><td align=center colspan=4>W H O L E" . " &nbsp " ."F O O D S" . " &nbsp "."C O - O P</TD></tR>";
    echo "<tr><td align=center colspan=4>(218) 728-0884</td></tr>";
    echo "<tr><td align=center colspan=4>MEMBER OWNED SINCE 1970</td></tr>";
    echo "<tr><td align=center colspan=4>$row2[0] &nbsp; &nbsp; $row2[2]</td></tr>";
    echo "<tr><td align=center colspan=4>Cashier:&nbsp;$row2[4]</td></tr>";
    echo "<tr><td colspan=4>&nbsp;</td></tr>";
    echo "<tr align left>\n";
    /*for($i=0; $i<5; $i++)
    {
        echo "<th>" . $sql->fieldName($results,$i). "</th>\n";
    }
    echo "</tr>\n"; *///end table header
    //layout table body
    while($row = $sql->fetch_row($results))
    {
        echo "<tr align=left>\n";
        echo "<td >";
            if(!isset($row[0]))
            {
                echo "NULL";
            }else{
                 ?>
                 <? echo $row[0]; ?>
            <? echo "</td>";
            }
        for ($i=1;$i<$number_cols-1; $i++)
        {
        echo "<td align=right>";
            if(!isset($row[$i])) //test for null value
            {
                echo "NULL";
            }else{
                echo $row[$i];
            }
            echo "</td>\n";
        } 
    
               echo "</tr>\n";
    
        
    } 
    echo "<tr><td colspan=4>&nbsp;</td></tr>";
    echo "<tr><td colspan=4 align=center>--------------------------------------------------------</td></tr>";
    echo "<tr><td colspan=4 align=center>Reprinted Transaction</td></tr>";
    echo "<tr><td colspan=4 align=center>--------------------------------------------------------</td></tr>";
    echo "<tr><td colspan=4 align=center>Member #: $row2[1]</td</tr>";
    echo "</table>\n";


}

function edit_receipt($query,$query2,$border,$bgcolor)
{
    global $sql;
        $result = $sql->query($query2);
        $results = $sql->query($query);
        $number_cols = $sql->numFields($results);
        $number2_cols = $sql->numFields($result);
        $row2 = $sql->fetch_row($result);
        $emp_no = $row2[4];
    echo "<form action=editReceipt2.php method=GET name=edit>";
        echo "<table border = $border bgcolor=$bgcolor>\n";
        echo "<tr><td align=center colspan=4>W H O L E" . " &nbsp " ."F O O D S" . " &nbsp "."C O - O P</TD></tR>";
        echo "<tr><td align=center colspan=4>(218) 728-0884</td></tr>";
        echo "<tr><td align=center colspan=4>MEMBER OWNED SINCE 1970</td></tr>";
        echo "<tr><td align=center colspan=4>$row2[0] &nbsp; &nbsp; $row2[2]</td></tr>";
        echo "<tr><td align=center colspan=4>Cashier:&nbsp;$row2[4]</td></tr>";
        echo "<tr><td colspan=4>&nbsp;</td></tr>";
        echo "<tr align left>\n";
        while($row = $sql->fetch_row($results))
        {
                echo "<tr align=left>\n";
                echo "<td >";
                        if(!isset($row[0]))
                        {
                                echo "NULL";
                        }else{
                                 ?>
                                 <? echo $row[0]; ?>
                        <? echo "</td>";
                        }
                for ($i=1;$i<$number_cols-1; $i++)
                {
                echo "<td align=right>";
                        if(!isset($row[$i])) //test for null value
                        {
                                echo "NULL";
                        }else{
                                echo $row[$i];
                        }
                        echo "</td>\n";
                }
           echo "<td><input type=checkbox value='$row[4]' edit='edit'></td>";
               echo "</tr>\n";
           

        }
    $transID = rtrim($row2[2]);
    $emp = rtrim($row2[4]);
    $reg = rtrim($row2[3]);
    $trans = rtrim($row2[5]);
        //echo $trans . "trans";
    $date = rtrim($row2[0]);
    //echo $date;    
    echo "<input type=hidden value='$emp' name=emp>";
    echo "<input type=hidden value='$reg' name=reg>";
    echo "<input type=hidden value='$trans' name=trans>";
    echo "<input type=hidden value='$date' name=date>";
    echo "<input type=hidden value='$trans' name=trans>";
        echo "<tr><td colspan=4>&nbsp;</td></tr>";
        echo "<tr><td colspan=4 align=center>--------------------------------------------------------</td></tr>";
        echo "<tr><td colspan=4 align=center><input type=submit value='Edit' name=submit></td></tr>";
        echo "<tr><td colspan=4 align=center>--------------------------------------------------------</td></tr>";
        echo "<tr><td colspan=4 align=center>Member #: $row2[1]</td</tr>";
        echo "</table>\n";
    echo "</form>";
}

/* -------------------------------start select_star_from----------------*/
/* creates a table returning all values from a table (SELECT * FROM depts)
   Variables are:
           $table = table to run query on
  
   example:
       select_star_from(depts);
*/

function select_star_from($table)
{
    global $sql;
    $query = "SELECT * FROM $table";
    $results = $sql->query($query); 
    $number_cols = $sql->numFields($results);
    //display query
    echo "<b>query: $query</b>";
    //layout table header
    echo "<table border = 1>\n";
    echo "<tr align left>\n";
    for($i=0; $i<$number_cols; $i++)
    {
        echo "<th>" . $sql->fieldName($results,$i). "</th>\n";
    }
    echo "</tr>\n"; //end table header
    //layout table body
    while($row = $sql->fetch_row($results))
    {
        echo "<tr align left>\n";
        for ($i=0;$i<$number_cols; $i++)
        {
        echo "<td>";
            if(!isset($row[$i])) //test for null value
            {
                echo "NULL";
            }else{
                echo $row[$i];
            }
            echo "</td>\n";
        } echo "</tr>\n";
    } echo "</table>\n";
}

/* ------------------------------end select_start_from-----------------0-------*/

/* ------------------------------start select_where_equal----------------------*/
/* creates a table using a SELECT WHERE syntax (SELECT * FROM transmemhead WHERE memNum = '175')
   Variables are
           $table = table for select
        $where = field for where statement
        $whereVar = value for where statement

    example:
        select_where(transmemhead,memNum,175)

*/

function select_where_equal($table,$where,$whereVar)
{
    global $sql;
    $query = "SELECT * FROM $table WHERE $where = '$whereVar'";
    $results = $sql->query($query); 
    $number_cols = $sql->numFields($results);
    //display query
    echo "<b>query: $query</b>";
    //layout table header
    echo "<table border = 1>\n";
    echo "<tr align left>\n";
    for($i=0; $i<$number_cols; $i++)
    {
        echo "<th>" . $sql->fieldName($results,$i). "</th>\n";
    }
    echo "</tr>\n"; //end table header
    //layout table body
    while($row = $sql->fetch_row($results))
    {
        echo "<tr align left>\n";
        for ($i=0;$i<$number_cols; $i++)
        {
        echo "<td>";
            if(!isset($row[$i])) //test for null value
            {
                echo "NULL";
            }else{
                echo $row[$i];
            }
            echo "</td>\n";
        } echo "</tr>\n";
    } echo "</table>\n";
}

/* ----------------------------end select_where_equal--------------------------*/

/* ----------------------------start select_where_between----------------------*/
/* creates a table using a SELECT WHERE syntax (SELECT * FROM transmemhead WHERE memNum BETWEEN '175' AND '185')
   Variables are 
           $table = table for select 
        $where = field for where statement
        $whereVar1 = beginning value for where statement
        $whereVar2 = ending value for where statement

    example:
        select_where_between(transmemhead,memNum,175,185)

*/

function select_where_between($table,$where,$whereVar1,$whereVar2)
{
    global $sql;
    $query = "SELECT * FROM $table WHERE $where BETWEEN '$whereVar1' AND '$whereVar2'";
    $results = $sql->query($query); 
    $number_cols = $sql->numFields($results);
    //display query
    echo "<b>query: $query</b>";
    //layout table header
    echo "<table border = 1>\n";
    echo "<tr align left>\n";
    for($i=0; $i<$number_cols; $i++)
    {
        echo "<th>" . $sql->fieldName($results,$i). "</th>\n";
    }
    echo "</tr>\n"; //end table header
    //layout table body
    while($row = $sql->fetch_row($results))
    {
        echo "<tr align left>\n";
        for ($i=0;$i<$number_cols; $i++)
        {
        echo "<td>";
            if(!isset($row[$i])) //test for null value
            {
                echo "NULL";
            }else{
                echo $row[$i];
            }
            echo "</td>\n";
        } echo "</tr>\n";
    } echo "</table>\n";
}
/* ----------------------------end select_where_between------------------*/


/* ----------------------------start select_to_drop----------------------*/
/* creates a dynamic drop down menu for use in forms. Variables are:
    $table = table for select
    $value = field to be used for drop down value
    $label = field to be used for the label on the drop down menu
    $name = name of the drop down menu
    
    example:
        select_to_drop(depts,deptNum,deptDesc,deptList)
        
*/

function select_to_drop($table,$value,$label,$name)
{
    global $sql;
    $query = "SELECT * FROM $table";
    $results = $sql->query($query); 
    $number_cols = $sql->numFields($results);
    //display query
    echo "<b>query: $query</b>";
    echo "<select name=$name id=$name>";
    while ($row_members = $sql->fetchRow($results))
    {  
          echo "<option value=" .$row_members[$value] . ">";
          echo $row_members[$label];
          echo "</option>";
    } 

}

function query_to_drop($query,$value,$label,$name,$line)
{
    global $sql;
    $results = $sql->query($query); 
        $number_cols = $sql->numFields($results);

        //display query
    //echo $number_cols;
        //echo "<b>query: $query</b>";
    echo "<select name=$name id=$name>";
        while ($row_members = $sql->fetchRow($results))
        {
       if($label == 'dept_name'){
         $label1 = $row_members['dept_no'] . " " . $row_members['dept_name'];
           }else{
        $label1 = $row_members[$label];
       }
        if($line == $row_members[$value]){
            echo "<option value=" .$row_members[$value] . " SELECTED >";
                    echo $label1;
        }else{
            echo "<option value=" .$row_members[$value] . ">";
                    //echo $row_members[$label];
            echo $label1;
        }
        } 
}


function item_sales_month($upc,$period,$time){
    global $sql;
    if($period == 'mm'){
    $dlog = "trans_archive.dbo.dlog".date("Ym");
        $query_sales = "SELECT sum(quantity),SUM(total) 
                        FROM $dlog WHERE upc = '$upc' AND 
                        datediff($period,getdate(),tdate) = $time";
    }else{
    $dlog = "dlog_90_view";
        $query_sales = "SELECT sum(quantity),SUM(total)                         
                        FROM $dlog WHERE upc = '$upc' AND             
                        datediff($period,getdate(),tdate) = $time";
        //echo $query_sales;
    }
    //echo $query_sales;    
    $result_sales = $sql->query($query_sales);
    $num_sales = $sql->num_rows($result_sales);
    
    $row_sales=$sql->fetch_row($result_sales);
    echo "<td align=right>";
    echo $row_sales[0]; 
    echo "</td><td align=right>$ " . $row_sales[1];
    
}

function item_sales_month_like($likecode,$period,$time){
    global $sql;
    if($period == 'mm'){
    $dlog = "trans_archive.dbo.dlog".date("Ym");
        $query_sales = "SELECT sum(d.quantity),SUM(d.total) from $dlog as d, upclike as u
                        WHERE u.upc = d.upc AND u.likecode = $likecode  
                        AND datediff($period,getdate(),tdate) = $time";
    }else{
        $query_sales = "SELECT sum(d.quantity),SUM(d.total) from dLog_90_view as d, upclike as u
                        WHERE u.upc = d.upc AND u.likecode = $likecode  
                        AND datediff($period,getdate(),tdate) = $time";
        //echo $query_sales;
    }
    $result_sales = $sql->query($query_sales);
    $num_sales = $sql->num_rows($result_sales);
    
    $row_sales=$sql->fetch_row($result_sales);
    echo "<td align=right>";
    echo $row_sales[0]; 
    echo "</td><td align=right>$ " . $row_sales[1];
}

function item_sales_last_month($upc,$period,$time){
    global $sql;
    $stamp = mktime(0,0,0,date('n')-1,1,date('Y'));
    $dlog = "trans_archive.dbo.dlog".date("Ym",$stamp);
    if($period == 'mm'){
        $query_sales = "SELECT sum(quantity),SUM(total) FROM $dlog WHERE upc = '$upc' AND datediff($period,getdate(),tdate) = $time";
    //echo $query_sales;        
    }else{
        $query_sales = "SELECT sum(quantity),SUM(total) FROM $dlog WHERE upc = '$upc' AND datediff($period,getdate(),tdate) = $time";
        //echo $query_sales;
    }
    $result_sales = $sql->query($query_sales);
    $num_sales = $sql->num_rows($result_sales);
    
    $row_sales=$sql->fetch_row($result_sales);
    echo "<td align=right>";
    echo $row_sales[0]; 
    echo "</td><td align=right>$ " . $row_sales[1];
    
}

function item_sales_last_month_like($likecode,$period,$time){
    global $sql;
    $stamp = mktime(0,0,0,date('n')-1,1,date('Y'));
    $dlog = "trans_archive.dbo.dlog".date("Ym",$stamp);
    if($period == 'mm'){
        $query_sales = "SELECT sum(d.quantity),SUM(d.total) from $dlog as d, upclike as u
                        WHERE u.upc = d.upc AND u.likecode = $likecode  
                        AND datediff($period,getdate(),tdate) = $time";
    //echo $query_sales;        
    }else{
        $query_sales = "SELECT sum(d.quantity),SUM(d.total) from $dlog as d, upclike as u
                        WHERE u.upc = d.upc AND u.likecode = $likecode  
                        AND datediff($period,getdate(),tdate) = $time";
    }
    $result_sales = $sql->query($query_sales);
    $num_sales = $sql->num_rows($result_sales);
    
    $row_sales=$sql->fetch_row($result_sales);
    echo "<td align=right>";
    echo $row_sales[0]; 
    echo "</td><td align=right>$ " . $row_sales[1];
}
    
/* pads upc with zeroes to make $upc into IS4C compliant upc*/

function str_pad_upc($upc){
   $strUPC = str_pad($upc,13,"0",STR_PAD_LEFT);
   return $strUPC;
}

function test_upc($upc){
   if(is_numeric($upc)){
      $upc=str_pad_upc($upc);
   }else{
      echo "not a number";
   }
}

function test_like($upc){
    global $sql;
   $upc = str_pad_upc($upc); 
   $testLikeQ = "SELECT likeCode FROM upcLike WHERE upc = '$upc'";
   $testLikeR = $sql->query($testLikeQ);
   $testLikeN = $sql->num_rows($testLikeR);
   $testLikeR = $sql->fetch_row($testLikeR);

   return $testLikeN;
}

/* find_like_code checks to see if $upc is in the upcLike table. Returns likeCodeID if it is.
*/

function find_like_code($upc){
    global $sql;
   $like = test_like($upc);
   //echo $like;
   if($like > 0){
      $upc = str_pad_upc($upc);
      $getLikeCodeQ = "SELECT * FROM upcLike WHERE upc = '$upc'";
      //echo $getLikeCodeQ;
      $getLikeCodeR = $sql->query($getLikeCodeQ);
      $getLikeCodeW = $sql->fetch_row($getLikeCodeR);
      $likeCode = $getLikeCodeW[1];     
      //echo $likeCode;
    }else{
      $likeCode = 0;
    } 
  
    return $likeCode;
}

/* finds all like coded items that share likeCode with $upc*/

function like_coded_items($upc){
    global $sql;
   $like = test_like($upc);
   $upc = str_pad_upc($upc);
   
   $selUPCLikeQ = "SELECT * FROM upcLike where likeCode = $like";
   $selUPCLikeR = $sql->query($selUPCLikeQ);
 
   return $selUPCLikeR;   
}

/* create an array from the results of a POSTed form */

function get_post_data($int){
    foreach ($_POST AS $key => $value) {
    $$key = $value;
    if($int == 1){
        echo $key .": " .  $$key . "<br>";
    }
    }
}

/* create an array from the results of GETed information */

function get_get_data($int){
    foreach ($_GET AS $key => $value) {
    $$key = $value;
    if($int == 1){
        echo $key .": " .  $$key . "<br>";
    }
    }
}


function log_info($name,$variable){
  $logfile = "/tmp/loginfo.txt";
  $log = fopen($logfile,"a");
  $message = "[".date('d-M-Y H:i:s')."] ".$name . ": ".$variable."\n";
  fwrite($log,$message);
  fclose($log);
}

