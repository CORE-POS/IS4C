<?php
include('functMem.php');
include_once($FANNIE_ROOT.'auth/login.php');

function addressList($memNum)
{
	global $sql,$FANNIE_URL;
	$custQ = "SELECT * FROM custdata where CardNo = $memNum and personnum= 1";
	$custR = $sql->query($custQ);
        $custN = $sql->num_rows($custR);
        $custW = $sql->fetch_row($custR);
	$status = $custW[11];

	if($status == 'PC') $status='ACTIVE';
	elseif($status == 'REG') $status='NONMEM';
	elseif($status == 'INACT2') $status='TERM (PENDING)';

	$infoQ = "SELECT * FROM meminfo WHERE card_no=$memNum";
	$infoR = $sql->query($infoQ);
	$infoW = $sql->fetch_row($infoR);
	$getsMail = $infoW['ads_OK'];

	$cardsQ = "SELECT upc FROM memberCards WHERE card_no=$memNum";
	$cardsR = $sql->query($cardsQ);
	$cardUPC = "";
	if ($sql->num_rows($cardsR) > 0){
		$cardUPC = array_pop($sql->fetch_row($cardsR));
	}

	$type = trim($custW[12]," ");
	//echo "<br> Here is type: " .$type;
	$query1 = "SELECT t.* FROM memTypeID as t WHERE t.memTypeID = $type";
	//echo "<br>".$query1;
	$result1 = $sql->query($query1);
	$row1 = $sql->fetch_row($result1);

	$dateQ = "SELECT CASE WHEN start_date IS NULL or start_date='' OR start_date='1900-01-01' OR start_date=0
		THEN '' ELSE DATE(start_date) END,
		CASE WHEN end_date IS NULL OR end_date = '' OR end_date='1900-01-01' OR end_date=0
		THEN '' ELSE 
		DATE(end_date) END from memDates
		WHERE card_no=$memNum";
	$dateR = $sql->query($dateQ);
	$dateW = $sql->fetch_row($dateR);

	//updated to new stock view based on stockpurchases table....CvR 02/27/06
	$query2 = "SELECT payments FROM is4c_trans.newBalanceStockToday_test WHERE memnum = $memNum";
	$stockResult = $sql->query($query2);
	$row2 = $sql->fetch_row($stockResult);
		
	//$query3 = "SELECT * FROM newBalanceToday WHERE memnum = $memNum";
        $query3 = "SELECT * FROM is4c_trans.ar_live_balance WHERE card_no= $memNum";
	$arResult = $sql->query($query3);
	$row3 = $sql->fetch_row($arResult);

	$query4 = "select LastName,FirstName from custdata where CardNo=$memNum and PersonNum > 1 order by PersonNum";
	$nameResult = $sql->query($query4);	
	$nameRows = $sql->num_rows($nameResult);


	$suspensionQ = "select type,reason,textStr,s.reasoncode&16 from suspensions s 
			left join reasoncodes r on s.reasoncode & r.mask <> 0
			where cardno=$memNum";
	$suspensionR = $sql->query($suspensionQ);
	$suspensionW = $sql->fetch_array($suspensionR);
	$suspended = $sql->num_rows($suspensionR);

	echo "<table>";
		echo "<tr>";
			echo "<td bgcolor=006633><font color=FFFF33>Owner Num</font></td>";
			echo "<td bgcolor=006633><font color=FFFF33>" . $memNum . "</font></td>";
			if($suspended != 0){
			  $code = $suspensionW[3];
			  echo "<td bgcolor='cc66cc'>$status</td>";
			  if ($suspended != 0){
			    echo "<td colspan=4>";
			    if ($suspensionW['reason'] != '') echo $suspensionW['reason'];
			    else {
			      $reasons = $suspensionW['textStr'];
			      while($suspensionW=$sql->fetch_array($suspensionR))
				  $reasons .= ", ".$suspensionW['textStr'];
			      echo $reasons;
			    }
			    echo "&nbsp;&nbsp;&nbsp;<a href=suspensionHistory.php?memNum=$memNum>History</a>";
			  }
			    if (validateUserQuiet('editmembers'))
			    		echo "&nbsp;&nbsp;&nbsp;<a href=alterstatus.php?memNum=$memNum>Change status</td>";
			    elseif(validateUserQuiet('editmembers_csc') && $code == 16)
			    		echo "&nbsp;&nbsp;&nbsp;<a href=alterstatus.php?memNum=$memNum&fixedaddress=yes&onclick=\"return confirm('Address now correct?');\">Address corrected</td>";
			}
			else {
			  echo "<td>$status $suspended</td>";
			  echo "<td colspan=2><a href=suspensionHistory.php?memNum=$memNum>History</a>";
			  if (validateUserQuiet('editmembers'))
				echo "&nbsp;&nbsp;&nbsp;<a href=deactivate.php?memNum=$memNum>Change Status</td>";
			  else
				echo "</td>";
                        }
			echo "<td><a href=\"{$FANNIE_URL}ordering/clearinghouse.php?card_no=$memNum\">Special Orders</a></td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td bgcolor='FFFF33'>First Name: </td>";
			echo "<td>" . $custW['FirstName'] . "</td>";
			echo "<td bgcolor ='FFFF33'>Last Name: </td>";
                        echo "<td>" . $custW['LastName'] . "</td>";
		echo "</tr>";
		echo "<tr>";
			$address = array();
			if (strstr($infoW['street'],"\n") === False)
				$address[0] = $infoW['street'];
			else
				$address = explode("\n",$infoW['street']);
			echo "<td bgcolor='FFFF33'>Address1: </td>";
			echo "<td>" . $address[0] . "</td>";
			echo "<td bgcolor=FFFF33>Gets mailings:</td><td>";
			if ($getsMail == 0){
			  echo "No";
			}
			else{
			  echo "Yes";
			}
			echo "</td>";
		echo "</tr>";
		echo "<tr>";
                        echo "<td bgcolor='FFFF33'>Address2: </td>";
                        echo "<td>" . (isset($address[1])?$address[1]:'&nbsp;') . "</td>";
			echo "<td bgcolor='FFFF33'>UPC: </td>";
			echo "<td colspan=2>";
			echo $cardUPC;
			echo "</td>";
                echo "</tr>";
		echo "<tr>";
                	echo "<td bgcolor='FFFF33'>City: </td>";
                        echo "<td>" . $infoW['city'] . "</td>";
		        echo "<td bgcolor='FFFF33'>State: </td>";
                        echo "<td>" . $infoW['state'] . "</td>";
                        echo "<td bgcolor='FFFF33'>Zip: </td>";
                        echo "<td>" . $infoW['zip'] . "</td>";
                echo "</tr>";
                echo "<tr>";
                        echo "<td bgcolor='FFFF33'>Phone Number: </td>";
                	echo "<td><font color='330099'>" . $infoW['phone'] . "</font></td>";
                        echo "<td bgcolor='FFFF33'>Start Date: </td>";
                        echo "<td>" . $dateW[0] . "</td>";
                        echo "<td bgcolor='FFFF33'>End Date: </td>";
                        echo "<td>" . $dateW[1] . "</td>";
                echo "</tr>";
		echo "<tr>";
                        echo "<td bgcolor='FFFF33'>Alt. Phone: </td>";
                	echo "<td><font color='330099'>" . $infoW['email_2'] . "</font></td>";
                        echo "<td bgcolor='FFFF33'>E-mail: </td>";
                        echo "<td colspan=2>" . $infoW['email_1'] . "</td>";
		echo "</tr>";
                echo "<tr>";         
			echo "<td bgcolor='FFFF33'>Stock Purchased: </td>";
                        echo "<td>" ;
			   echo $row2['payments'];
			echo "</td>";
                        echo "<td bgcolor='FFFF33'>Mem Type: </td>";
                        echo "<td>" . $row1[1] . "</td>";
                        echo "<td bgcolor='ffff33'>Discount:</td>";
                        echo "<td>".$custW[6]."</td>";
 
		echo "</tr>";
		echo "<tr>";
                        echo "<td bgcolor='FFFF33'>Charge Limit: </td>";
                        echo "<td>" . $custW['MemDiscountLimit'] . "</td>";
                        echo "<td bgcolor='FFFF33'>Current Balance: </td>";
                        echo "<td>" . $row3['balance'] . "</td>";
		echo "</tr>";
               echo "<tr bgcolor='FFFF33'><td colspan=6></td></tr>";
                echo "<tr>";
                        echo "<td bgcolor='006633' colspan=2>Additional household members</td>";
			echo "<td></td>";
			echo "<td bgcolor='006633'>Additional Notes</td>";
			echo "<td><a href=noteHistory.php?memNum=$memNum>Notes history</a></td>";
                echo "</tr>";
                echo "<tr>";
                        echo "<td></td>";
                        echo "<td bgcolor='FFFF33'>First Name</td>";
                        echo "<td bgcolor='FFFF33'>Last Name</td>";
			$noteQ = "select note from memberNotes where cardno=$memNum order by stamp desc limit 1";
			$noteR = $sql->query($noteQ);
			$notetext = "";
			if ($sql->num_rows($noteR) == 1)
				$notetext = stripslashes(array_pop($sql->fetch_array($noteR)));
			echo "<td colspan=4 width=\"300px\" rowspan=8>$notetext</td>";
                echo "</tr>";
		for($i=0;$i<$nameRows;$i++){
			echo "<tr>";
				$rowNames =  $sql->fetch_row($nameResult);
				$num = $i+1;
				echo "<td bgcolor='FFFF33'>".$num.".</td>";
				echo "<td>".$rowNames[1]."</td>";
				echo "<td>".$rowNames[0]."</td>";
			echo "</tr>";
		}
	echo "</table>";
}

function addressForm($memNum)
{
	global $sql;
  $typeQ = "select * from custdata where CardNo = $memNum and personNum = 1";
  $typeR = $sql->query($typeQ);
  $typeRow = $sql->fetch_array($typeR);
        $type = trim($typeRow['memType']," ");
        $status = trim($typeRow['Type']," ");
	$memcoupons = $typeRow['memCoupons'];
	if ($status != "PC") $memcoupons = 0;
	if($status == 'PC') $status = 'ACTIVE';
	elseif($status == 'REG') $status='NONMEM';
	elseif($status == 'INACT2') $status='TERM (PENDING)';

	$infoQ = "SELECT * FROM meminfo WHERE card_no=$memNum";
	$infoR = $sql->query($infoQ);
	$infoW = $sql->fetch_row($infoR);
	$getsMail = $infoW['ads_OK'];

	$cardsQ = "SELECT upc FROM memberCards WHERE card_no=$memNum";
	$cardsR = $sql->query($cardsQ);
	$cardUPC = "";
	if ($sql->num_rows($cardsR) > 0){
		$cardUPC = array_pop($sql->fetch_row($cardsR));
	}

        $query1 = "SELECT t.* FROM memTypeID as t WHERE t.memTypeID = $type";
        $result1 = $sql->query($query1);
        $row1 = $sql->fetch_row($result1);
	$memIDQ = "SELECT * FROM memTypeID";

        $query2 = "SELECT payments FROM is4c_trans.newBalanceStockToday_test WHERE memnum = $memNum";
        $stockResult = $sql->query($query2);
        $row2 = $sql->fetch_row($stockResult);

        $query3 = "SELECT * FROM is4c_trans.ar_live_balance WHERE card_no = $memNum";
        $arResult = $sql->query($query3);
        $row3 = $sql->fetch_row($arResult);

	//$query4 = "SELECT * FROM memnames WHERE memnum = $memNum AND personnum > 1 AND active = 1";
	$query4 = "SELECT LastName,FirstName FROM custdata where CardNo = $memNum AND PersonNum > 1 order by PersonNum";
	$nameResult = $sql->query($query4);	
	$nameRows = $sql->num_rows($nameResult);

	$dateQ = "SELECT CASE WHEN start_date IS NULL or start_date='' or start_date='1900-01-01'
		THEN '' ELSE DATE(start_date) END,
		CASE WHEN end_date IS NULL OR end_date = '' or end_date='1900-01-01'
		THEN '' ELSE 
		DATE(end_date) END from memDates
		WHERE card_no=$memNum";
	$dateR = $sql->query($dateQ);
	$dateW = $sql->fetch_row($dateR);

	$suspensionQ = "select type,reason from suspensions where cardno=$memNum";
	$suspensionR = $sql->query($suspensionQ);
	$suspensionW = $sql->fetch_array($suspensionR);
	$suspended = $sql->num_rows($suspensionR);

	echo "<form method=post action=insertEdit.php name=edit>";
	echo "<input type='hidden' value=$memNum name=memNum>";
        echo "<table>";
                echo "<tr>";
			echo "<td bgcolor=006633><font color=FFFF33>Owner Num</font></td>";
                        echo "<td bgcolor=006633><font color=FFFF33>" . $memNum . "</font></td>";
			if($suspended != 0){
			  if ($suspensionW[0] == 'I')
				  echo "<td bgcolor='cc66cc'>$status</td>";
			  else if ($suspensionW[0] == 'T')
				  echo "<td bgcolor='cc66cc'>$status</td>";
			  else
				  echo "<td bgcolor='cc66cc'>$status</td>";
			  if ($suspended != 0){
			    echo "<td>{$suspensionW['reason']} <a href=suspensionHistory.php?memNum=$memNum>History</a></td>";
			  }
                        }else{
			  echo "<td>$status</td>"; 
			  echo "<td><a href=suspensionHistory.php?memNum=$memNum>History</a></td>";
			}
                echo "</tr>";
                echo "<tr>";
                        echo "<td bgcolor='FFFF33'>First Name: </td>";
                        echo "<td> <input name=fName maxlength=25 value='" . $typeRow['FirstName'] . "'></td>";
                        echo "<td bgcolor ='FFFF33'>Last Name: </td>";
                        echo "<td><input name=lName maxlength=25 value='" . $typeRow['LastName'] . "' maxlength=25></td>";
                echo "</tr>";
                echo "<tr>";
                        echo "<td bgcolor='FFFF33'>Address1: </td>";
			$address = array();
			if (strstr($infoW['street'],"\n") === False)
				$address[0] = $infoW['street'];
			else
				$address = explode("\n",$infoW['street']);
                        echo "<td><input name=address1 maxlength=30 value='" . $address[0] . "'></td>";
			echo "<td bgcolor='FFFF33'>Gets mail: </td>";
			echo "<td><select name=mailflag>";
			echo "<option value=1";
			if ($getsMail != 0){
			  echo " selected";
			}
			echo ">Yes</option>";
			echo "<option value=0";
			if ($getsMail == 0){
			  echo " selected";
			}
			echo ">No</option>";
			echo "</select></td>";
                echo "</tr>";
                echo "<tr>";
                        echo "<td bgcolor='FFFF33'>Address2: </td>";
                        echo "<td><input name=address2 maxlength=30 value='" . (isset($address[1])?$address[1]:'') . "'></td>";
                        echo "<td bgcolor='FFFF33'>UPC: </td>";
			echo "<td><input name=cardUPC maxlength=13 value=\"".$cardUPC."\" /></td>";
                echo "</tr>";
                echo "<tr>";
                        echo "<td bgcolor='FFFF33'>City: </td>";
                        echo "<td><input maxlength=20 name=city value='" . $infoW['city'] . "'></td>";
                        echo "<td bgcolor='FFFF33'>State: </td>";
                        echo "<td><input name=state maxlength=2 value='" . $infoW['state'] . "'></td>";
                        echo "<td bgcolor='FFFF33'>Zip: </td>";
                        echo "<td><input name=zip maxlength=12 value='" . $infoW['zip'] . "'></td>";
                echo "</tr>";
                echo "<tr>";
			echo "<td bgcolor='FFF33'>Phone Number:</td>";
			echo "<td><input name=phone maxlength=12 value='".$infoW['phone'] . "'></td>";
                        echo "<td bgcolor='FFFF33'>Start Date: </td>";
                        echo "<td><input name=startDate value='" . $dateW[0] . "'></td>";
			echo "<td bgcolor='FFFF33'>End Date: </td>";                        
			echo "<td><input name=endDate value='".$dateW[1]."'></td>";
                        echo "</tr>";
			echo "<tr>";
			echo "<td bgcolor='FFF33'>Alt. Phone:</td>";
			echo "<td><input name=phone2 maxlength=12 value='".$infoW['email_2'] . "'></td>";
                        echo "<td bgcolor='FFFF33'>E-mail: </td>";
                        echo "<td><input colspan=2 maxlength=75 name=email value='" . $infoW['email_1'] . "'></td>";
			echo "</tr>";
                        echo "<tr>";
                        echo "<td bgcolor='FFFF33'>Stock Purchased: </td>";
                        echo "<td>" . $row2['payments'] . "</td>";
                        echo "<td bgcolor='FFFF33'>Discount: </td>";
			echo "<td>" ; 
			//echo $type;
			echo "<input type=hidden name=curDiscLimit value={$type}>";
            ?><select id=discList onchange="setvisible();" name=discList>
            <?php
            $selMemTypeQ = "SELECT * FROM memTypeID";
            $selMemTypeR = $sql->query($selMemTypeQ);
            while($selMemTypeW = $sql->fetch_array($selMemTypeR)){
                if($selMemTypeW['memTypeID'] == $type){
                   echo "<option value=".$selMemTypeW['memTypeID']." selected>"
                        .$selMemTypeW['memDesc']." ".$selMemTypeW['memTypeID']."</option>";
                }else{
                   echo "<option value=".$selMemTypeW['memTypeID'].">".$selMemTypeW['memDesc']." "
                       .$selMemTypeW['memTypeID']."</option>";
                }
            }
            
            ?>
		<!--
            <b style="display:none" id=textAR>Staff AR</b> 
            <input type="checkbox" id="ar" name=checkAR value="1" style="display:none">
             <input type="text" id="adpID" name=adpNum value="" style="display:none">
		-->
			<?php echo "</td>";
                        echo "<td><input name=discount size=5 value='" . $typeRow['Discount'] . "'></td>";
			if ($typeRow['Discount'] <> 10 && ($type == 9 || $type == 3))
				echo "<td><input type=checkbox name=doDiscount checked /> Discount override</td>";
			else if ($typeRow['Discount'] <> 0 && ($type == 0 || $type == 1 || $type == 6 || $type == 8))
				echo "<td><input type=checkbox name=doDiscount checked /> Discount override</td>";
			else
				echo "<td><input type=checkbox name=doDiscount /> Discount override</td>";
                echo "</tr>";
                echo "<tr>";
                        echo "<td bgcolor='FFFF33'>Charge Limit: </td>";
                        echo "<td><input name=chargeLimit value='" . $typeRow['MemDiscountLimit'] . "'></td>";
                        echo "<td bgcolor='FFFF33'>Current Balance: </td>";
                        echo "<td>" . $row3['balance'] . "</td>";
                echo "</tr>";
        	echo "<tr bgcolor='006633'><td colspan=5></td></tr>";
		echo "<tr>";
			echo "<td bgcolor='FFFF33' colspan=2>Additional household members</td>";
			echo "<td></td>";
			echo "<td bgcolor='FFFF33'>Additional Notes</td>";
			echo "<td><a href=noteHistory.php?memNum=$memNum>Notes history</a></td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td></td>";
			echo "<td bgcolor='FFFF33'>First Name</td>";
			echo "<td bgcolor='FFFF33'>Last Name</td>";
			$noteQ = "select note from memberNotes where cardno=$memNum order by stamp desc limit 1";
			$noteR = $sql->query($noteQ);
			$notetext = "";
			if ($sql->num_rows($noteR) == 1){
				$notetext = stripslashes(array_pop($sql->fetch_array($noteR)));
				$notetext = preg_replace("/<br \/>/","\n",$notetext);
			}
			echo "<td rowspan=4 colspan=3><textarea name=notetext rows=7 cols=50>$notetext</textarea></td>";
		echo "</tr>";
                for($i=0;$i<3;$i++){
                        echo "<tr>";
                                $rowNames =  $sql->fetch_row($nameResult);
                                $num = $i+1;

				if(empty($rowNames[1])){
					$rowFName = '';
				}else{
					$rowFName=$rowNames[1];
				}
				if(empty($rowNames[0])){
					$rowLName='';
				}else{
					$rowLName=$rowNames[0];
				}

                                echo "<td bgcolor='FFFF33'>".$num.".</td>";
                                echo "<td><input maxlength=25 type = text name=hhFname[] value=\"".$rowNames[1]."\"></td>";
                                echo "<td><input maxlength=25 type = text name=hhLname[] value=\"".$rowNames[0]."\"></td>";
                        echo "</tr>";
                }
		echo "<tr>";
			echo "<td><input type ='submit' value='Edit More' name='more'></td>";
			echo "<td><input type ='submit' value='Done Editing' name='done'><td>";
			echo "<td><input type ='reset' value='Reset (oops)' name='done'></td>";
		echo "</tr>";
	echo "</table>";
	echo "<input type=hidden name=memcoupons value=\"$memcoupons\" />";
	echo "<input type=hidden name=status value=\"$status\" />";
	echo "</form>";
}

function alterReason($memNum,$reasonCode,$status=False){
	global $sql;

	$username = checkLogin();
	$uid = getUID($username);

	$upQ = "UPDATE suspensions SET reasoncode=$reasonCode WHERE cardno=$memNum";
	$upR = $sql->query($upQ);
	if ($reasonCode == 0){
		activate($memNum);
	}
	else {
		$now = date("Y-m-d h:i:s");
		$insQ = "INSERT INTO suspension_history VALUES ('$username','$now','','$memNum',$reasonCode)";
		$insR = $sql->query_all($insQ);
		if ($status){
			$sql->query_all("UPDATE custdata SET type='$status' WHERE cardno=$memNum");
			if ($status == "TERM")
				$sql->query_all("UPDATE suspensions SET type='T' WHERE cardno=$memNum");
			else
				$sql->query_all("UPDATE suspensions SET type='I' WHERE cardno=$memNum");
		}
	}
}

function deactivate($memNum,$type,$reason,$reasonCode){
	global $sql;

  $username = checkLogin();
  $uid = getUID($username);
  $auditQ = "insert custUpdate select now(),$uid,1,* from custdata where cardno=$memNum";
  //$auditR = $sql->query($auditQ);
	
  if ($type == 'TERM'){
    $query = "select memtype,type,memDiscountLimit,discount from custdata where cardno=$memNum";
    $result = $sql->query($query);
    $row = $sql->fetch_array($result);
    $otherQ = "select ads_OK from meminfo where card_no=$memNum";
    $otherR = $sql->query($otherQ);
    $mailflag = array_pop($sql->fetch_array($otherR));
    
    $now = date('Y-m-d h:i:s');
    $query = "insert into suspensions values ($memNum,'T',$row[0],'$row[1]','$reason','$now',$mailflag,$row[3],$row[2],$reasonCode)";
    //echo $query."<br />";
    $result = $sql->query_all($query);
    
    $username = validateUserQuiet('editmembers');
    
    $query = "insert into suspension_history values ('$username','$now','$reason','$memNum',$reasonCode)";
    $result = $sql->query_all($query);
    
    $mQ = "update meminfo set ads_OK=0 where card_no = $memNum";
    $cQ = "update custdata set memtype=0, type='TERM',chargeok=0,discount=0,memdiscountlimit=0 where cardno=$memNum";
    $mR = $sql->query_all($mQ);
    $cR = $sql->query_all($cQ);
  }elseif($type=='INACT' || $type=='INACT2'){
    $query = "select memtype,type,memDiscountLimit,discount from custdata where cardno=$memNum";
    $result = $sql->query($query);
    $row = $sql->fetch_array($result);
    $otherQ = "select ads_OK from meminfo where card_no=$memNum";
    $otherR = $sql->query($otherQ);
    $mailflag = array_pop($sql->fetch_array($otherR));
    
    $now = date('Y-m-d h:i:s');
    $query = "insert into suspensions values ($memNum,'I',$row[0],'$row[1]','$reason','$now',$mailflag,$row[3],$row[2],$reasonCode)";
    //echo $query."<br />";
    $result = $sql->query_all($query);

    $username = validateUserQuiet('editmembers');

    $query = "insert into suspension_history values ('$username','$now','$reason','$memNum',$reasonCode)";
    $result = $sql->query_all($query);

    $mQ = "update meminfo set ads_OK=0 where card_no = $memNum";
    $cQ = "update custdata set memtype=0, type='$type',chargeok=0,discount=0,memDiscountLimit=0 where cardno=$memNum";
    $mR = $sql->query_all($mQ);
    $cR = $sql->query_all($cQ);
  }
}

function activate($memNum){
	global $sql;

  $username = checkLogin();
  $uid = getUID($username);
  $auditQ = "insert custUpdate select now(),$uid,1,* from custdata where cardno=$memNum";
  //$auditR = $sql->query($auditQ);

  $query = "select type,memtype1,memtype2,discount,chargelimit,mailflag from suspensions where cardno=$memNum";
  $result = $sql->query($query);
  $row = $sql->fetch_array($result);
  // type S shouldn't exist any more, in here to deal with historical rows
  if ($row[0] == 'I' || $row[0] == 'T' || $row[0] == 'S'){
    $mQ = "update meminfo set ads_OK=$row[5] where card_no=$memNum";
    $cQ = "update custdata set memtype=$row[1], type='$row[2]',chargeok=1,discount=$row[3],memDiscountLimit=$row[4] where cardno=$memNum";
    $mR = $sql->query_all($mQ);
    $cR = $sql->query_all($cQ);
  }
  else if ($row[0] == 'X'){
    $cQ = "update custdata set discount=$row[3], type='$row[2]', chargeOk = 1,
           memtype = $row[1], memdiscountlimit = $row[4], memcoupons = 1
           where cardno=$memNum";
    $cR = $sql->query_all($cQ);
    $mQ = "update meminfo set ads_OK=$row[5] where card_no=$memNum";
    $mR = $sql->query_all($mQ);
  }
  $query = "delete from suspensions where cardno=$memNum";
  $result = $sql->query_all($query);
  
  $username = validateUserQuiet('editmembers');
    
  $now = date("Y-m-d h:i:s");
  $query = "insert into suspension_history values ('$username','$now','Account reactivated','$memNum',-1)";
  $result = $sql->query_all($query);
}

function addressFormLimited($memNum)
{
	global $sql;
  $typeQ = "select * from custdata where CardNo = $memNum and personNum = 1";
  $typeR = $sql->query($typeQ);
  $typeRow = $sql->fetch_array($typeR);
        $type = trim($typeRow['memType']," ");
        $status = trim($typeRow['Type']," ");
	$memcoupons = $typeRow['memCoupons'];
	if ($status != "PC") $memcoupons = 0;
	if($status == 'PC') $status = 'ACTIVE';
	if($status == 'REG') $status = 'NONMEM';
        //echo "<br> Here is type: " .$type;
        $query1 = "SELECT t.* FROM memTypeID as t WHERE t.memTypeID = $type";
        //echo "<br>".$query1;
        $result1 = $sql->query($query1);
        $row1 = $sql->fetch_row($result1);
	$memIDQ = "SELECT * FROM memTypeID";

	$infoQ = "SELECT * FROM meminfo WHERE card_no=$memNum";
	$infoR = $sql->query($infoQ);
	$infoW = $sql->fetch_row($infoR);

	$cardsQ = "SELECT upc FROM memberCards WHERE card_no=$memNum";
	$cardsR = $sql->query($cardsQ);
	$cardUPC = "";
	if ($sql->num_rows($cardsR) > 0){
		$cardUPC = array_pop($sql->fetch_row($cardsR));
	}

	$suspensionQ = "select type,reason from suspensions where cardno=$memNum";
	$suspensionR = $sql->query($suspensionQ);
	$suspensionW = $sql->fetch_array($suspensionR);
	$suspended = $sql->num_rows($suspensionR);

	echo "<form method=post action=limitedSave.php name=edit>";
	echo "<input type='hidden' value=$memNum name=memNum>";
        echo "<table>";
                echo "<tr>";
			echo "<td bgcolor=006633><font color=FFFF33>Owner Num</font></td>";
                        echo "<td bgcolor=006633><font color=FFFF33>" . $memNum . "</font></td>";
			if($suspended != 0){
			  if ($suspensionW[0] == 'I')
				  echo "<td bgcolor='cc66cc'>$status</td>";
			  else if ($suspended != 0 and $suspensionW[0] == 'T')
				  echo "<td bgcolor='cc66cc'>$status</td>";
			  else
				  echo "<td bgcolor='cc66cc'>$status</td>";
			  if ($suspended != 0){
			    echo "<td>{$suspensionW['reason']} <a href=suspensionHistory.php?memNum=$memNum>History</a></td>";
			  }
                        }else{
			  echo "<td>$status</td>"; 
			  echo "<td><a href=suspensionHistory.php?memNum=$memNum>History</a></td>";
			}
                echo "</tr>";
                echo "<tr>";
                        echo "<td bgcolor ='FFFF33'>First Name: </td>";
                        echo "<td><input maxlength=25 name=fName value='" . $typeRow['FirstName'] . "' maxlength=25></td>";
                        echo "<td bgcolor ='FFFF33'>Last Name: </td>";
                        echo "<td><input maxlength=25 name=lName value='" . $typeRow['LastName'] . "' maxlength=25></td>";
                echo "</tr>";
                echo "<tr>";
                        echo "<td bgcolor='FFFF33'>Address1: </td>";
			$address = array();
			if (strstr($infoW['street'],"\n") === False)
				$address[0] = $infoW['street'];
			else
				$address = explode("\n",$infoW['street']);
                        echo "<td><input name=address1 maxlength=30 value='" . $address[0] . "'></td>";
                echo "</tr>";
                echo "<tr>";
                        echo "<td bgcolor='FFFF33'>Address2: </td>";
                        echo "<td><input name=address2 maxlength=30 value='" .(isset($address[1])? $address[1] :''). "'></td>";
                        echo "<td bgcolor='FFFF33'>UPC: </td>";
			echo "<td><input name=cardUPC maxlength=13 value=\"".$cardUPC."\" /></td>";
                echo "</tr>";
                echo "<tr>";
                        echo "<td bgcolor='FFFF33'>City: </td>";
                        echo "<td><input maxlength=20 name=city value='" . $infoW['city'] . "'></td>";
                        echo "<td bgcolor='FFFF33'>State: </td>";
                        echo "<td><input maxlength=2 name=state value='" . $infoW['state'] . "'></td>";
                        echo "<td bgcolor='FFFF33'>Zip: </td>";
                        echo "<td><input maxlength=10 name=zip value='" . $infoW['zip'] . "'></td>";
                echo "</tr>";
                echo "<tr>";
			echo "<td bgcolor='FFF33'>Phone Number:</td>";
			echo "<td><input maxlength=12 name=phone value='".$infoW['phone'] . "'></td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td bgcolor='FFF33'>Alt. Phone:</td>";
			echo "<td><input maxlength=12 name=phone2 value='".$infoW['email_2'] . "'></td>";
                        echo "<td bgcolor='FFFF33'>E-mail: </td>";
                        echo "<td><input colspan=2 maxlength=75 name=email value='" . $infoW['email_1'] . "'></td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td><input type ='submit' value='Edit More' name='more'></td>";
			echo "<td><input type ='submit' value='Done Editing' name='done'><td>";
			echo "<td><input type ='reset' value='Reset (oops)' name='done'></td>";
		echo "</tr>";
		$nameQ = "SELECT firstName,LastName FROM custdata WHERE cardno=$memNum and personnum > 1 order by personnum";
		$nameR = $sql->query($nameQ);
		$num = 1;
		while($nameW = $sql->fetch_row($nameR)){
			echo "<tr>";
				echo "<td bgcolor='FFFF33'>".$num.".</td>";
				echo "<td><input type=text maxlength=75 name=hfname[] value=\"".$nameW[0]."\" /></td>";
				echo "<td><input type=text maxlength=75 name=hlname[] value=\"".$nameW[1]."\" /></td>";
			echo "</tr>";
			$num++;
		}
		while($num <= 3){
			echo "<tr>";
				echo "<td bgcolor='FFFF33'>".$num.".</td>";
				echo "<td><input type=text name=hfname[] value=\"\" /></td>";
				echo "<td><input type=text name=hlname[] value=\"\" /></td>";
			echo "</tr>";
			$num++;
		}
	echo "</table>";
	echo "</table>";
	echo "<input type=hidden name=memcoupons value=\"$memcoupons\" />";
	echo "<input type=hidden name=status value=\"$status\" />";
	echo "</form>";
}

?>
