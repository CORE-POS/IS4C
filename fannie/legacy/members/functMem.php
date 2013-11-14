<?php


function trans_to_table($query,$border,$bgcolor)
{
	global $sql, $FANNIE_URL;
	$results = $sql->query($query); 
	$number_cols = $sql->num_fields($results);
	//display query
	//echo "<b>query: $query</b>";
	//layout table header
	echo "<table border = $border bgcolor=$bgcolor>\n";
	echo "<tr align left>\n";


	/*for($i=0; $i<5; $i++)
	{
		echo "<th>" . $sql->field_name($results,$i). "</th>\n";
	}*/
	echo "</tr>\n"; //end table header
	//layout table body
	while($row = $sql->fetch_array($results))
	{
		echo "<tr align=center>\n";
		echo "<td >";
			if(!isset($row[0]))
			{
				echo "NULL";
			}else{
				 ?>
				 <a href="<?php echo $FANNIE_URL; ?>admin/LookupReceipt/RenderReceiptPage.php?receipt=<? echo $row[5]; ?>&cardno=<? echo $row[4]; ?>&month=<? echo $row[0]; ?>&day=<? echo $row[1]; ?>&year=<? echo $row[2]; ?>">
				 <? echo $row[0] .'-'.$row[1].'-'.$row[2]; ?></a>
			<? echo "</td>";
			}
		for ($i=3;$i<$number_cols-1; $i++)
		{
		if("$row[$i]"=='S'){
			echo "<td bgcolor='ff3300'>";
				echo $row[$i];
			echo "</td>";
		}elseif("$row[$i]"=='P'){
                        echo "<td bgcolor='ff66ff'>";
                                echo $row[$i];
                        echo "</td>";
                }elseif("$row[$i]"=='C'){
                        echo "<td bgcolor='0055ff'>";
                                echo $row[$i];
                        echo "</td>";
		}elseif("$row[$i]" == 'MC'){
                echo "<td bgcolor='003311'><font color='ffffcc'>";
                echo $row[$i];
                echo "</font></td>";
        }else{		
			echo "<td>";
			if(!isset($row[$i])) //test for null value
			{
				echo "NULL";
			}else{
				echo $row[$i];
			}
			echo "</td>\n";
		}	
		} echo "</tr>\n";
	} echo "</table>\n";
}


/* -------------------------------end select_to_table-------------------*/

function head_to_table($query,$border,$bgcolor)
{
	global $sql;
        $results = $sql->query($query); 
        $number_cols = $sql->num_fields($results);
        //display query
        //echo "<b>query: $query</b>";
        //layout table header
        echo "<table border = $border bgcolor=$bgcolor>\n";
        $r=0;

        while($r < $sql->num_fields($results)){
                $reportF = $sql->fetch_field($results,$r);
                $field = $reportF->name;
                echo "<th>" . $field;
                $r++;
        }
        echo "<tr align left>\n";
        echo "</tr>\n"; //end table header
        //layout table body
        while($row = $sql->fetch_array($results))
        {
                echo "<tr align=center>\n";
                echo "<td >";
                        if(!isset($row[0]))
                        {
                                echo "NULL";
                        }else{
                                  echo $row[0];
                         echo "</td>";
                        }
                for ($i=1;$i<$number_cols; $i++)
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
}/* -------------------------------end select_to_table-------------------*/ 

/* -------------------------------start edit_to_table ------------------*/

function edit_to_table($query,$border,$bgcolor)
{
	global $sql;
	//echo $query;
        $results = $sql->query($query); 
        $number_cols = $sql->num_fields($results);
        //display query
        //echo "<b>query: $query</b>";
        //layout table header
        echo "<table border = $border bgcolor=$bgcolor>\n";
        echo "<th>MemNum<th>Per #<th>Last Name<th>First Name<th>Charge<th>Edit";
        echo "<tr align left>\n";
        echo "</tr>\n"; //end table header
        //layout table body
        while($row = $sql->fetch_array($results))
        {
                echo "<tr align=center>\n";
                echo "<td >";
                        
			if(!isset($row[0]))
                        {
                                echo "NULL";
                        }else{
                                  echo $row[0];
                         echo "</td><td>" . $row[1] . "</td>";
                        }
                for ($i=2;$i<$number_cols; $i++)
                {

                echo "<td>";
                        if(!isset($row[$i])) //test for null value
                        {
                                echo "NULL";
                        }elseif($i <> 4){
                                echo "<input type=hidden name=cardNo value='$row[0]'>";
				echo "<input type=hidden name=person value='$row[1]'>";
				echo "<input type=text name='Last$row[1]' value='$row[2]'>";
				echo "<inpyt type=text name='First$row[1]' value=$row[3]'>";
                        }else{
				if($row[4] == 1){
				   echo "<input type=checkbox name='charge$row[1]' checked>";
				}else{
				   echo "<input type=checkbox name='charge$row[1]'>";
				}
			echo "</td><td><input type=radio value=$row[1] name=edit>";
			}
                        echo "</td>\n";
                } echo "</tr>\n";
        } echo "</table>\n";
}
/* -------------------------------end edit_to_table --------------------*/

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
	$number_cols = $sql->num_fields($results);
	//display query
	echo "<b>query: $query</b>";
	//layout table header
	echo "<table border = 1>\n";
	echo "<tr align left>\n";
	//for($i=0; $i<$number_cols; $i++)
	//{
	//	echo "<th>" . $sql->field_name($results,$i). "</th>\n";
	//}
	echo "</tr>\n"; //end table header
	//layout table body
	while($row = $sql->fetch_array($results))
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

function select_where_equal($table,$where,$whereVar,$order)
{
	global $sql;
	if(empty($order)){
            $query = "SELECT * FROM $table WHERE $where = '$whereVar'";
        }else{
            $query = "SELECT * FROM $table WHERE $where = '$whereVar' order by $order";
        }

	$results = $sql->query($query); 
	$number_cols = $sql->num_fields($results);
	//display query
	echo "<b>query: $query</b>";
	//layout table header
	echo "<table border = 1>\n";
	echo "<tr align left>\n";
	echo "</tr>\n"; //end table header
	//layout table body
	while($row = $sql->fetch_array($results))
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
/* ----------------------------start edit_where_equal--------------------------*/

function edit_where_equal($table,$where,$whereVar,$order)
{
	global $sql;
        if(empty($order)){
            $query = "SELECT * FROM $table WHERE $where = '$whereVar'";
        }else{
            $query = "SELECT * FROM $table WHERE $where = '$whereVar' order by $order";
        }

        $results = $sql->query($query); 
        $number_cols = $sql->num_fields($results);
        //display query
        echo "<b>query: $query</b>";
        //layout table header
        echo "<table border = 1>\n";
        echo "<tr align left>\n";
        echo "</tr>\n"; //end table header
        //layout table body
        while($row = $sql->fetch_array($results))
        {
                echo "<tr align left>\n";
                for ($i=0;$i<$number_cols; $i++)
                {
                echo "<td>";
                        if(!isset($row[$i])) //test for null value
                        {
                                echo "NULL";
                        }else{
                                echo "<input type=text value='$row[$i]'>";
                        }
                        echo "</td>\n";
                } echo "</tr>\n";
        } echo "</table>\n";
}

/* ----------------------------end edit_where_equal --------------------------*/

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
	$number_cols = $sql->num_fields($results);
	//display query
	echo "<b>query: $query</b>";
	//layout table header
	echo "<table border = 1>\n";
	echo "<tr align left>\n";
	for($i=0; $i<$number_cols; $i++)
	{
		echo "<th>" . $sql->field_name($results,$i). "</th>\n";
	}
	echo "</tr>\n"; //end table header
	//layout table body
	while($row = $sql->fetch_array($results))
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
	$default = default value on the drop down menu

	example:
		select_to_drop(depts,deptNum,deptDesc,deptList)
		
*/

function select_to_drop($table,$value,$label,$name,$default)
{
	global $sql;
	$query = "SELECT * FROM $table";
	$results = $sql->query($query); 
	$number_cols = $sql->num_fields($results);
	//display query
	echo "<b>query: $query</b>";
	echo "<select name=$name id=$name>";
	while ($row_members = $sql->fetch_assoc($results))
	{  
  		echo "<option value=" .$row_members[$value] . ">";
  		echo $row_members[$label];
  		echo "</option>";
	} 
}

/*function query_to_drop($query,$value,$label,$name,$selected)
{
        //$query = "SELECT * FROM $table";
		$results = $sql->query($query); /*or
                die("<li>errorno=".$sql->errno()
                        ."<li>error=" .$sql->error()
                        ."<li>query=".$query);*/
/*        $number_cols = $sql->num_fields($results);
        //display query
	//echo $number_cols;
        //echo "<b>query: $query</b>";
        echo "<select name=$name id=$name>";
        do
        {
                if($selected = $row_members[$value]){
			echo "<option value=" .$row_members[$value] . " SELECTED >";
                	echo $row_members[$label];
                	//echo "</option>";
		}else{
			echo "<option value=" .$row_members[$value] . ">";
                	echo $row_members[$label];
                	//echo "</option>";
		}
        } while ($row_members = $sql->fetch_array($results));
        $rows = $sql->num_rows($results);

}*/

function query_to_drop($dropQ,$value,$label,$name,$line)
{
	global $sql;
        $dropR= $sql->query($dropQ); 
        $dropNC= $sql->num_fields($dropR);
        //display query
        //echo $number_cols;
        //echo "<b>query: $query</b>";
        echo "\n\n<select name=$name id=$name>\n";
        while ($row_members = $sql->fetch_array($dropR))
        {       
	  //echo $line."<-line row->".$row_members[$value]."<br>";
                if($line == $row_members[$value]){
                        echo "<option value=" .$row_members[$value] . " SELECTED>";
                        echo $row_members[$label] .  ' ' . $row_members[$value];
			echo "</option>\n";
                }else{  
                        echo "<option value=" .$row_members[$value] . ">";
                        echo $row_members[$label] . ' ' . $row_members[$value];
			echo "</option>\n";
                }
        } 
        
	echo "</select>\n\n";
}      




/* add_household_member adds a new household member to an account, setting all variables to person 1, except for personnum, lastname, firstname, and chargeok */

function add_household_member($cardNo,$person,$lastName,$firstName,$chargeOk){
	global $sql;

	$selCustQ = "SELECT * FROM custdata WHERE cardNo = '$cardNo' and personnum = '1'";
	$selCustR = $sql->query($selCustQ,$db);

	$selCustA = $sql->fetch_array($selCustR);
	foreach ($selCustA AS $key => $value) {
    		$$key = $value;
	}
	
	$blueLine = $cardNo . " " . $lastName;

	$insCustQ = "INSERT INTO custdata VALUES(
			'$CardNo',
			'$person',
			'$lastName',
			'$firstName',
			CONVERT(money,$CashBack),
			CONVERT(money,$Balance),
			'$Discount',
			CONVERT(money,$MemDiscountLimit),
			'$chargeOk',
			'$WriteChecks',
			'$StoreCoupons',
			'$Type',
			'$memType',
			'$staff',
			'$SSI',
			CONVERT(money,$Purchases),
			'$NumberOfChecks',
			'$memCoupons',
			'$blueLine',
			'$Shown')";
	//echo $insCustQ;
	$insCustR = $sql->query($insCustQ,$db);
	
	$dbPOS1 = $sql->connect('129.103.2.11','sa');
	$sql->select_db('POSBDAT',$dbPOS1);
	$insCustRp1 = $sql->query($insCustQ,$dbPOS1);

}

function update_household_member($cardNo,$person,$lname,$fname,$chargeOK)
{
	global $sql;
	$blueLine = $cardNo . ' ' . $lname;

        $updateCustQ = "UPDATE custdata SET lastName = '$lname', 
                        firstName = '$fname', chargeOK = $chargeOK,
                        blueLine = '$blueLine' WHERE cardno = $cardNo
                        and personnum = $person";
	echo $updateCustQ;
	$updateCustR = $sql->query($updateCustQ);
	
}

function edit_work_status($query,$border,$bgcolor)
{
	global $sql;
        $results = $sql->query($query); 
        $number_cols = $sql->num_fields($results);
        //display query
        //echo "<b>query: $query</b>";
        //layout table header
        echo "<table border = $border bgcolor=$bgcolor>\n";
        /*$r=0;

        while($r < $sql->num_fields($results)){
                $reportF = $sql->fetch_field($results,$r);
                $field = $reportF->name;
                echo "<th>" . $field; 
                $r++;
        }*/
        echo "<tr align left>\n";
        echo "</tr>\n"; //end table header
        //layout table body
        while($row = $sql->fetch_array($results))
        {
                echo "<tr align=center>\n";
                echo "<td >";
                        if(!isset($row[0]))
                        {
                                echo "NULL";
                        }else{
                                  echo $row[0];
                         echo "</td>";
                        }
                for ($i=1;$i<$number_cols; $i++)
                {
                echo "<td>";
                        if(!isset($row[$i])) //test for null value
                        {
                                echo "NULL";
                        }else{
                                echo $row[$i];
                        }
                        echo "</td>\n";
                } 
                
                        echo "<td>";
                        echo "<input type=checkbox value=$row[0] name=$row[0]>";
                        echo "</td>";
                echo "</tr>\n";
        } echo "</table>\n";
}

function log_info($name,$variable){
  global $FANNIE_ROOT;
  $logfile = $FANNIE_ROOT."logs/loginfo.txt";
  $log = fopen($logfile,"a");
  $message = "[".date('d-M-Y H:i:s')."] ".$name . ": ".$variable."\n";
  fwrite($log,$message);
  fclose($log);
}
?>
