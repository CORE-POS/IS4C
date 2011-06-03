<?php
/*******************************************************************************

    Copyright 2005 Whole Foods Community Co-op

    This file is part of WFC's PI Killer.

    PI Killer is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    PI Killer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

// $dbhost = 'localhost';
// $dbuser = 'root';
// $dbpass = '';
// $dbdata = 'is4c_op';

// $db = $dbc->connect($dbhost,$dbuser,$dbpass);
// $dbc->select_db($dbdata,$db);

/* -----------------------start select_to_table-----------------------*/
/* creates a table from query defined outside function. 
   Variables are:
   		$query = query to run 
  
   example:
	$x = "SELECT * FROM tlog WHERE TransDate BETWEEN '2004-04-01' AND '2004-04-02' LIMIT 50"
	select_to_table($x);

*/


function select_cols_to_table($query,$border,$bgcolor,$cols)
{
	global $dbc;
        $results = $dbc->query($query);
        //echo "<b>query: $query</b>";
        //layout table header
        echo "<table border = $border bgcolor=$bgcolor>\n";
        echo "<tr align left>\n";
        /*for($i=0; $i<5; $i++)
        {
                echo "<th>" . $dbc->field_name($results,$i). "</th>\n";
        }
        echo "</tr>\n"; *///end table header
        //layout table body
        while($row = $dbc->fetch_row($results))
        {
                echo "<tr align=left>\n";
                echo "<td >";
                        if(!isset($row[0]))
                        {
                                echo "NULL";
                        }else{
                                 ?>
                                 <a href="transaction.php?id=<?php echo $row[5]; ?>">
                                 <?php echo $row[0]; ?></a>
                        <?php echo "</td>";
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
                        echo "</td>\n";
                } echo "</tr>\n";
        } echo "</table>\n";
}

/* -------------------------------end select_to_table-------------------*/ 

function prodList_to_table($query,$border,$bgcolor,$upc)
{
	global $dbc;
        $results = $dbc->query($query) or
                die("<li>errorno=".$dbc->errno()
                        ."<li>error=" .$dbc->error()
                        ."<li>query=".$query);
        $number_cols = $dbc->num_fields($results);
        //display query
        //echo "<b>query: $query</b>";
        //layout table header
        echo "<table border = $border bgcolor=$bgcolor>\n";
        echo "<tr align left>\n";
        /*for($i=0; $i<5; $i++)
        {
                echo "<th>" . $dbc->field_name($results,$i). "</th>\n";
        }
        echo "</tr>\n"; *///end table header
        //layout table body
        while($row = $dbc->fetch_row($results))
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
                                 <a href="productTestLike.php?upc=<?php echo $row[0]; ?>">
                                 <?php echo $row[0]; ?></a>
                        <?php echo "</td>";
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
	global $dbc;
        $results = $dbc->query($query) or
                die("<li>errorno=".$dbc->errno()
                        ."<li>error=" .$dbc->error()
                        ."<li>query=".$query);
        $number_cols = $dbc->num_fields($results);
        //display query
        //echo "<b>query: $query</b>";
        //layout table header
        echo "<table border = $border bgcolor=$bgcolor>\n";
        echo "<tr align left>\n";
        /*for($i=0; $i<5; $i++)
        {
                echo "<th>" . $dbc->field_name($results,$i). "</th>\n";
        }
        echo "</tr>\n"; *///end table header
        //layout table body
        while($row = $dbc->fetch_row($results))
        {
                echo "<tr align=left>\n";
                echo "<td >";
                        if(!isset($row[0]))
                        {
                                echo "NULL";
                        }else{
                                 ?>
                                 <a href="productTestLike.php?upc=<?php echo $row[0]; ?>">
                                 <?php echo $row[0]; ?></a>
                        <?php echo "</td>";
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
	global $dbc;
	//echo $query2;
    $result = $dbc->query($query2);
	$results = $dbc->query($query); 
	$number_cols = $dbc->num_fields($results);
	$number2_cols = $dbc->num_fields($result);
	//display query
	//echo "<b>query: $query</b>";
	//layout table header
	$row2 = $dbc->fetch_row($result);
	$emp_no = $row2[4];	
	//echo $emp_no;
	//$queryEmp = "SELECT * FROM Employees where emp_no = $emp_no";
	//$resEmp = $dbc->query($queryEmp,$db);
	//$rowEmp = $dbc->fetch_row($resEmp);
	//echo $rowEmp[4];
	
	//echo $query2;
	echo "<table border = $border bgcolor=$bgcolor>\n";
	echo "<tr><td align=center colspan=4>A L B E R T A " . " &nbsp " ."C O - O P" . " &nbsp "."G R O C E R</TD></tR>";
	echo "<tr><td align=center colspan=4>503-287-4333</td></tr>";
	echo "<tr><td align=center colspan=4>MEMBER OWNED SINCE 1970</td></tr>";
	echo "<tr><td align=center colspan=4>$row2[0] &nbsp; &nbsp; $row2[2]</td></tr>";
	echo "<tr><td align=center colspan=4>Cashier:&nbsp;$row2[4]</td></tr>";
	echo "<tr><td colspan=4>&nbsp;</td></tr>";
	echo "<tr align left>\n";
	/*for($i=0; $i<5; $i++)
	{
		echo "<th>" . $dbc->field_name($results,$i). "</th>\n";
	}
	echo "</tr>\n"; *///end table header
	//layout table body
	while($row = $dbc->fetch_row($results)) {
		echo "<tr><td align=left>";
		echo $row["description"]; 
		echo "</td><td align=right>";
		echo $row["quantity"]. " @ " .$row["unitPrice"];
		echo "</td><td>";
		echo $row["total"];
		echo "</td></tr>";	
	} 
	
	echo "<tr><td colspan=4>&nbsp;</td></tr>";
	echo "<tr><td colspan=4 align=center>--------------------------------------------------------</td></tr>";
	echo "<tr><td colspan=4 align=center>Reprinted Transaction</td></tr>";
	echo "<tr><td colspan=4 align=center>--------------------------------------------------------</td></tr>";
	echo "<tr><td colspan=4 align=center>Member #: $row2[1]</td</tr>";
	echo "</table>\n";


}

/*		PART OF ORINGINAL RCPT_TO_TABLE FUNCTIN
		
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
*/

/* -------------------------------start select_star_from----------------*/
/* creates a table returning all values from a table (SELECT * FROM depts)
   Variables are:
   		$table = table to run query on
  
   example:
   	select_star_from(depts);
*/
/*
function select_star_from($table)
{
	$query = "SELECT * FROM $table";
	$results = $dbc->query($query) or
		die("<li>errorno=".$dbc->errno()
			."<li>error=" .$dbc->error()
			."<li>query=".$query);
	$number_cols = $dbc->num_fields($results);
	//display query
	echo "<b>query: $query</b>";
	//layout table header
	echo "<table border = 1>\n";
	echo "<tr align left>\n";
	for($i=0; $i<$number_cols; $i++)
	{
		echo "<th>" . $dbc->field_name($results,$i). "</th>\n";
	}
	echo "</tr>\n"; //end table header
	//layout table body
	while($row = $dbc->fetch_row($results))
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
*/
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
/*
function select_where_equal($table,$where,$whereVar)
{
	$query = "SELECT * FROM $table WHERE $where = '$whereVar'";
	$results = $dbc->query($query) or
		die("<li>errorno=".$dbc->errno()
			."<li>error=" .$dbc->error()
			."<li>query=".$query);
	$number_cols = $dbc->num_fields($results);
	//display query
	echo "<b>query: $query</b>";
	//layout table header
	echo "<table border = 1>\n";
	echo "<tr align left>\n";
	for($i=0; $i<$number_cols; $i++)
	{
		echo "<th>" . $dbc->field_name($results,$i). "</th>\n";
	}
	echo "</tr>\n"; //end table header
	//layout table body
	while($row = $dbc->fetch_row($results))
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
*/
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
/*
function select_where_between($table,$where,$whereVar1,$whereVar2)
{
	$query = "SELECT * FROM $table WHERE $where BETWEEN '$whereVar1' AND '$whereVar2'";
	$results = $dbc->query($query) or
		die("<li>errorno=".$dbc->errno()
			."<li>error=" .$dbc->error()
			."<li>query=".$query);
	$number_cols = $dbc->num_fields($results);
	//display query
	echo "<b>query: $query</b>";
	//layout table header
	echo "<table border = 1>\n";
	echo "<tr align left>\n";
	for($i=0; $i<$number_cols; $i++)
	{
		echo "<th>" . $dbc->field_name($results,$i). "</th>\n";
	}
	echo "</tr>\n"; //end table header
	//layout table body
	while($row = $dbc->fetch_row($results))
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
} */
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
	global $dbc;
	$query = "SELECT * FROM $table";
	$results = $dbc->query($query) or
		die("<li>errorno=".$dbc->errno()
			."<li>error=" .$dbc->error()
			."<li>query=".$query);
	$number_cols = $dbc->num_fields($results);
	//display query
	//echo "<b>query: $query</b>";
	echo "<select name=$name id=$name>";
	do 
	{  
  		echo "<option value=" .$row_members[$value] . ">";
  		echo $row_members[$label];
  		echo "</option>";
	} while ($row_members = $dbc->fetch_array($results));
  	$rows = $dbc->num_rows($results);
  	if($rows > 0) 
  	{
    	$dbc->data_seek($results, 0);
		$row_members = $dbc->fetch_assoc($results);
  	}

}


/** SNIPPED FROM PRODfUNCTION.PHP . . . 
	$query2 = "SELECT * FROM departments ORDER BY  dept_no";
    $value = "dept_no";
    $label = "dept_name";
    $deptList = "dept";
    $select = $rowItem[12]; 
	query_to_drop($query2,$value,$label,$deptList,$select);
**/

function query_to_drop($query,$value,$label,$name,$line)
{
	global $dbc;
	$results = $dbc->query($query); 
        //$number_cols = $dbc->num_fields($results);
        //display query
	//echo $number_cols;
        //echo "<b>query: $query</b>";
	echo "<select name=$name id=$name>";
        
	while($row_members = $dbc->fetch_array($results)){
	   if($line == $row_members[$value]){
   	      echo "<option value=" .$row_members[$value] . " selected>";
	      echo $row_members[$label];
 	   }else{
	      echo "<option value=" .$row_members[$value] . ">";
	      echo $row_members[$label];
	   }	
	}
	/*do
        {
		if($line == $row_members[$value]){
			echo "<option value=" .$row_members[$value] . " SELECTED >";
                	echo $row_members[$label];
		}else{
			echo "<option value=" .$row_members[$value] . ">";
                	echo $row_members[$label];
		}
        } while ($row_members = $dbc->fetch_array($results));*/
        	$rows = $dbc->num_rows($results);
        if($rows > 0)
        {
        $dbc->data_seek($results, 0);
                $row_members = $dbc->fetch_array($results);
        }

}

function item_sales_month($upc,$period,$time){
	global $dbc;
    $query_sales = "SELECT COUNT(upc),SUM(total) FROM dLogMonth WHERE upc = '$upc' AND datediff($period,getdate(),tdate) = $time";
    //echo $query_sales;	
    $result_sales = $dbc->query($query_sales);
    $num_sales = $dbc->num_rows($result_sales);
    
    $row_sales=$dbc->fetch_row($result_sales);
    echo "<td align=right>";
    echo $row_sales[0]; 
    echo "</td><td align=right>$ " . $row_sales[1];
    
}

function item_sales_last_month($upc,$period,$time){
	global $dbc;
    $query_sales = "SELECT COUNT(upc),SUM(total) FROM dLogLastMonth WHERE upc = '$upc' AND datediff($period,getdate(),tdate) = $time";
    //echo $query_sales;        
    $result_sales = $dbc->query($query_sales);
    $num_sales = $dbc->num_rows($result_sales);
    
    $row_sales=$dbc->fetch_row($result_sales);
    echo "<td align=right>";
    echo $row_sales[0]; 
    echo "</td><td align=right>$ " . $row_sales[1];
    
}

/* pads upc with zeroes to make $upc into IT CORE compliant upc*/

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
	global $dbc;
   $upc = str_pad_upc($upc); 
   $testLikeQ = "SELECT likeCode FROM upcLike WHERE upc = '$upc'";
   $testLikeR = $dbc->query($testLikeQ);
   $testLikeN = $dbc->num_rows($testLikeR);
   $testLikeR = $dbc->fetch_row($testLikeR);

   return $testLikeN;
}

/* find_like_code checks to see if $upc is in the upcLike table. Returns likeCodeID if it is.
*/

function find_like_code($upc){
	global $dbc;
   $like = test_like($upc);
   //echo $like;
   if($like > 0){
      $upc = str_pad_upc($upc);
      $getLikeCodeQ = "SELECT * FROM upcLike WHERE upc = '$upc'";
      //echo $getLikeCodeQ;
      $getLikeCodeR = $dbc->query($getLikeCodeQ);
      $getLikeCodeW = $dbc->fetch_row($getLikeCodeR);
      $likeCode = $getLikeCodeW[1];     
      //echo $likeCode;
    }else{
      $likeCode = 0;
    } 
  
    return $likeCode;
}

/* finds all like coded items that share likeCode with $upc*/

function like_coded_items($upc){
	global $dbc;
   $like = test_like($upc);
   $upc = str_pad_upc($upc);
   
   $selUPCLikeQ = "SELECT * FROM upcLike where likeCode = $like";
   $selUPCLikeR = $dbc->query($selUPCLikeQ);
 
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

/* rounding function to create 'non-stupid' pricing */

?>

