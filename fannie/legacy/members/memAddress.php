<?php
include_once($FANNIE_ROOT.'auth/login.php');

function addressList($memNum)
{
    global $sql,$FANNIE_URL;
    $custQ = $sql->prepare("SELECT * FROM custdata where CardNo = ? and personnum= 1");
    $custR = $sql->execute($custQ, array($memNum));
        $custN = $sql->num_rows($custR);
        $custW = $sql->fetch_row($custR);
    $status = $custW['Type'];

    if($status == 'PC') $status='ACTIVE';
    elseif($status == 'REG') $status='NONMEM';
    elseif($status == 'INACT2') $status='TERM (PENDING)';

    $infoQ = $sql->prepare("SELECT * FROM meminfo WHERE card_no=?");
    $infoR = $sql->execute($infoQ, array($memNum));
    $infoW = $sql->fetch_row($infoR);
    $getsMail = $infoW['ads_OK'];

    $cardsQ = $sql->prepare("SELECT upc FROM memberCards WHERE card_no=?");
    $cardsR = $sql->execute($cardsQ, array($memNum));
    $cardUPC = "";
    if ($sql->num_rows($cardsR) > 0){
        $cardsW = $sql->fetch_row($cardsR);
        $cardUPC = $cardsW['upc'];
    }

    $type = trim($custW['memType']," ");
    //echo "<br> Here is type: " .$type;
    $query1 = $sql->prepare("SELECT t.memDesc FROM memtype as t WHERE t.memtype = ?");
    //echo "<br>".$query1;
    $result1 = $sql->execute($query1, array($type));
    $row1 = $sql->fetch_row($result1);

    $dateQ = $sql->prepare("SELECT CASE WHEN start_date IS NULL or start_date='' OR start_date='1900-01-01' OR start_date=0
        THEN '' ELSE DATE(start_date) END,
        CASE WHEN end_date IS NULL OR end_date = '' OR end_date='1900-01-01' OR end_date=0
        THEN '' ELSE 
        DATE(end_date) END from memDates
        WHERE card_no=?");
    $dateR = $sql->execute($dateQ, array($memNum));
    $dateW = $sql->fetch_row($dateR);

    //updated to new stock view based on stockpurchases table....CvR 02/27/06
    $query2 = $sql->prepare("SELECT payments FROM is4c_trans.equity_live_balance WHERE memnum = ?");
    $stockResult = $sql->execute($query2, array($memNum));
    $row2 = $sql->fetch_row($stockResult);
        
    //$query3 = "SELECT * FROM newBalanceToday WHERE memnum = $memNum";
    $query3 = $sql->prepare("SELECT * FROM is4c_trans.ar_live_balance WHERE card_no= ?");
    $arResult = $sql->execute($query3, array($memNum));
    $row3 = $sql->fetch_row($arResult);

    $query4 = $sql->prepare("select LastName,FirstName from custdata where CardNo=? and PersonNum > 1 order by PersonNum");
    $nameResult = $sql->execute($query4, array($memNum));
    $nameRows = $sql->num_rows($nameResult);


    $suspensionQ = $sql->prepare("select type,reason,textStr,s.reasoncode&16 from suspensions s 
            left join reasoncodes r on s.reasoncode & r.mask <> 0
            where cardno=?");
    $suspensionR = $sql->execute($suspensionQ, array($memNum));
    $suspensionW = $sql->fetchRow($suspensionR);
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
                  while($suspensionW=$sql->fetchRow($suspensionR))
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
            if (validateUserQuiet('GiveUsMoney')) {
                echo "<td><a href=\"{$FANNIE_URL}modules/plugins2.0/GiveUsMoneyPlugin/GumMainPage.php?id=".$memNum."\">Owner Loans</a></td>";
            }
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
                        echo "<td>" . $row1['memDesc'] . "</td>";
                        echo "<td bgcolor='ffff33'>Discount:</td>";
                        echo "<td>".$custW['Discount']."</td>";
 
        echo "</tr>";
        echo "<tr>";
                        echo "<td bgcolor='FFFF33'>Charge Limit: </td>";
                        echo "<td>" . $custW['ChargeLimit'] . "</td>";
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
            $noteQ = $sql->prepare("select note from memberNotes where cardno=? order by stamp desc limit 1");
            $noteR = $sql->execute($noteQ, array($memNum));
            $notetext = "";
            if ($sql->num_rows($noteR) == 1)
                $notetext = stripslashes(array_pop($sql->fetchRow($noteR)));
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
    $custQ = $sql->prepare("SELECT * FROM custdata where CardNo = ? and personnum= 1");
    $custR = $sql->execute($custQ, array($memNum));
    $typeRow = $sql->fetchRow($custR);
    $type = trim($typeRow['memType']," ");
    $status = trim($typeRow['Type']," ");
    $memcoupons = $typeRow['memCoupons'];
    if ($status != "PC") $memcoupons = 0;
    if($status == 'PC') $status = 'ACTIVE';
    elseif($status == 'REG') $status='NONMEM';
    elseif($status == 'INACT2') $status='TERM (PENDING)';

    $infoQ = $sql->prepare("SELECT * FROM meminfo WHERE card_no=?");
    $infoR = $sql->execute($infoQ, array($memNum));
    $infoW = $sql->fetch_row($infoR);
    $getsMail = $infoW['ads_OK'];

    $cardsQ = $sql->prepare("SELECT upc FROM memberCards WHERE card_no=?");
    $cardsR = $sql->execute($cardsQ, array($memNum));
    $cardUPC = "";
    if ($sql->num_rows($cardsR) > 0){
        $cardsW = $sql->fetch_row($cardsR);
        $cardUPC = $cardsW['upc'];
    }

    $query2 = $sql->prepare("SELECT payments FROM is4c_trans.equity_live_balance WHERE memnum = ?");
    $stockResult = $sql->execute($query2, array($memNum));
    $row2 = $sql->fetch_row($stockResult);

    $query3 = $sql->prepare("SELECT * FROM is4c_trans.ar_live_balance WHERE card_no= ?");
    $arResult = $sql->execute($query3, array($memNum));
    $row3 = $sql->fetch_row($arResult);

    //$query4 = "SELECT * FROM memnames WHERE memnum = $memNum AND personnum > 1 AND active = 1";
    $query4 = $sql->prepare("select LastName,FirstName from custdata where CardNo=? and PersonNum > 1 order by PersonNum");
    $nameResult = $sql->execute($query4, array($memNum));
    $nameRows = $sql->num_rows($nameResult);

    $dateQ = $sql->prepare("SELECT CASE WHEN start_date IS NULL or start_date='' or start_date='1900-01-01'
        THEN '' ELSE DATE(start_date) END,
        CASE WHEN end_date IS NULL OR end_date = '' or end_date='1900-01-01'
        THEN '' ELSE 
        DATE(end_date) END from memDates
        WHERE card_no=?");
    $dateR = $sql->execute($dateQ, array($memNum));
    $dateW = $sql->fetch_row($dateR);

    $suspensionQ = $sql->prepare("select type,reason from suspensions where cardno=?");
    $suspensionR = $sql->execute($suspensionQ, array($memNum));
    $suspensionW = $sql->fetchRow($suspensionR);
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
            $selMemTypeQ = "SELECT * FROM memtype";
            $selMemTypeR = $sql->query($selMemTypeQ);
            while($selMemTypeW = $sql->fetchRow($selMemTypeR)){
                if($selMemTypeW['memtype'] == $type){
                   echo "<option value=".$selMemTypeW['memtype']." selected>"
                        .$selMemTypeW['memDesc']." ".$selMemTypeW['memtype']."</option>";
                }else{
                   echo "<option value=".$selMemTypeW['memtype'].">".$selMemTypeW['memDesc']." "
                       .$selMemTypeW['memtype']."</option>";
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
                        echo "<td><input name=chargeLimit value='" . $typeRow['ChargeLimit'] . "'></td>";
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
            $noteQ = $sql->prepare("select note from memberNotes where cardno=? order by stamp desc limit 1");
            $noteR = $sql->execute($noteQ, array($memNum));
            $notetext = "";
            if ($sql->num_rows($noteR) == 1){
                $notetext = stripslashes(array_pop($sql->fetchRow($noteR)));
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

    $model = new CustomerAccountSuspensionsModel($sql);
    $model->card_no($memNum);

    $upQ = $sql->prepare("UPDATE suspensions SET reasoncode=? WHERE cardno=?");
    $upR = $sql->execute($upQ, array($reasonCode, $memNum));
    if ($reasonCode == 0){
        activate($memNum);
    }
    else {
        $now = date("Y-m-d h:i:s");
        $m_status = 0;
        $insQ = $sql->prepare("INSERT INTO suspension_history (username, postdate, post, cardno, reasoncode) 
                                VALUES (?,?,'',?,?)");
        $insR = $sql->execute($insQ, array($username, $now, $memNum, $reasonCode));
        if ($status){
            $prep = $sql->prepare("UPDATE custdata SET type=? WHERE cardno=?");
            $sql->execute($prep, array($status, $memNum));
            if ($status == "TERM") {
                $custP = $sql->prepare("UPDATE suspensions SET type='T' WHERE cardno=?");
                $sql->execute($custP, array($memNum));
                $m_status = 2;
            } else {
                $custP = $sql->prepare("UPDATE suspensions SET type='I' WHERE cardno=?");
                $sql->execute($custP, array($memNum));
                $m_status = 1;
            }
        }

        $changed = false;
        $model->active(1);
        // find most recent active record
        $current = $model->find('tdate', true);
        foreach($current as $obj) {
            if ($obj->reasonCode() != $reasonCode || $obj->suspensionTypeID() != $m_status) {
                $changed = true;
            }
            $model->savedType($obj->savedType());
            $model->savedMemType($obj->savedMemType());
            $model->savedDiscount($obj->savedDiscount());
            $model->savedChargeLimit($obj->savedChargeLimit());
            $model->savedMailFlag($obj->savedMailFlag());
            // copy "saved" values from current active
            // suspension record. should only be one
            break;
        }
        
        // only add a record if something changed.
        // count($current) of zero means there is no
        // record. once the migration to the new data
        // structure is complete, that check won't
        // be necessary
        if ($changed || count($current) == 0) {
            $model->reasonCode($reasonCode);
            $model->tdate($now);
            $model->username($username);
            $model->suspensionTypeID($m_status);

            $new_id = $model->save();

            // only most recent should be active
            $model->reset();
            $model->card_no($memNum);
            $model->active(1);
            foreach($model->find() as $obj) {
                if ($obj->customerAccountSuspensionID() != $new_id) {
                    $obj->active(0);
                    $obj->save();
                }
            }
        }
    }
}

function deactivate($memNum,$type,$reason,$reasonCode){
    global $sql;

  $username = checkLogin();
  $uid = getUID($username);
  $auditQ = "insert custUpdate select now(),$uid,1,* from custdata where cardno=$memNum";
  //$auditR = $sql->query($auditQ);
  $model = new CustomerAccountSuspensionsModel($sql);
  $model->card_no($memNum);
    
  if ($type == 'TERM'){
    $query = $sql->prepare("select memType,Type,ChargeLimit,Discount from custdata where CardNo=?");
    $result = $sql->execute($query, array($memNum));
    $row = $sql->fetchRow($result);
    $otherQ = $sql->prepare("select ads_OK from meminfo where card_no=?");
    $otherR = $sql->execute($otherQ, array($memNum));
    $otherW = $sql->fetchRow($otherR);
    $mailflag = $otherW['ads_OK'];
    
    $now = date('Y-m-d h:i:s');
    $query = $sql->prepare("insert into suspensions (cardno, type, memtype1, memtype2, reason, suspDate, mailflag,
                discount, chargelimit, reasoncode) values (?,'T',?,?,?,?,?,?,?,?)");
    //echo $query."<br />";
    $result = $sql->execute($query, array($memNum, $row['memType'], $row['Type'], $reason, $now, $mailflag, $row['Discount'], $row['ChargeLimit'], $reasonCode));

    $model->savedType($row['Type']);
    $model->savedMemType($row['memType']);
    $model->savedDiscount($row['Discount']);
    $model->savedChargeLimit($row['ChargeLimit']);
    $model->savedMailFlag($mailflag);

    $model->reasonCode($reason);
    $model->tdate($now);
    $model->suspensionTypeID(2);
    
    $username = validateUserQuiet('editmembers');
    $model->username($username);
    
    $query = $sql->prepare("insert into suspension_history (username, postdate, post, cardno, reasoncode) 
                values (?, ?, ?, ?, ?)");
    $result = $sql->execute($query, array($username, $now, $reason, $memNum, $reasonCode));
    
    $mQ = $sql->prepare("update meminfo set ads_OK=0 where card_no = ?");
    $cQ = $sql->prepare("update custdata set memType=0, Type='TERM',ChargeOk=0,Discount=0,MemDiscountLimit=0,ChargeLimit=0 
            where CardNo=?");
    $mR = $sql->execute($mQ, array($memNum));
    $cR = $sql->execute($cQ, array($memNum));
  }elseif($type=='INACT' || $type=='INACT2'){
    $query = $sql->prepare("select memType,Type,ChargeLimit,Discount from custdata where CardNo=?");
    $result = $sql->execute($query, array($memNum));
    $row = $sql->fetchRow($result);
    $otherQ = $sql->prepare("select ads_OK from meminfo where card_no=?");
    $otherR = $sql->execute($otherQ, array($memNum));
    $otherW = $sql->fetchRow($otherR);
    $mailflag = $otherW['ads_OK'];
    
    $now = date('Y-m-d h:i:s');
    $query = $sql->prepare("insert into suspensions (cardno, type, memtype1, memtype2, reason, suspDate, mailflag,
                discount, chargelimit, reasoncode) values (?,'I',?,?,?,?,?,?,?,?)");
    //echo $query."<br />";
    $result = $sql->execute($query, array($memNum, $row['memType'], $row['Type'], $reason, $now, $mailflag, $row['Discount'], $row['ChargeLimit'], $reasonCode));

    $model->savedType($row['Type']);
    $model->savedMemType($row['memType']);
    $model->savedDiscount($row['Discount']);
    $model->savedChargeLimit($row['ChargeLimit']);
    $model->savedMailFlag($mailflag);

    $model->reasonCode($reason);
    $model->tdate($now);
    $model->suspensionTypeID(1);

    $username = validateUserQuiet('editmembers');
    $model->username($username);

    $query = $sql->prepare("insert into suspension_history (username, postdate, post, cardno, reasoncode) 
                values (?, ?, ?, ?, ?)");
    $result = $sql->execute($query, array($username, $now, $reason, $memNum, $reasonCode));

    $mQ = $sql->prepare("update meminfo set ads_OK=0 where card_no = ?");
    $cQ = $sql->prepare("update custdata set memType=0, Type=?,ChargeOk=0,Discount=0,MemDiscountLimit=0,ChargeLimit=0 
            where CardNo=?");
    $mR = $sql->execute($mQ, array($memNum));
    $cR = $sql->execute($cQ, array($type, $memNum));
  }

  $model->active(1);
  $new_id = $model->save();

  // only most recent should be active
  $model->reset();
  $model->card_no($memNum);
  $model->active(1);
  foreach($model->find() as $obj) {
    if ($obj->customerAccountSuspensionID() != $new_id) {
        $obj->active(0);
        $obj->save();
    }
  }
}

function activate($memNum){
    global $sql;

  $model = new CustomerAccountSuspensionsModel($sql);
  $model->card_no($memNum);

  $username = checkLogin();
  $uid = getUID($username);
  $auditQ = "insert custUpdate select now(),$uid,1,* from custdata where cardno=$memNum";
  //$auditR = $sql->query($auditQ);

  $query = $sql->prepare("select type,memtype1,memtype2,discount,chargelimit,mailflag from suspensions where cardno=?");
  $result = $sql->execute($query, array($memNum));
  $row = $sql->fetchRow($result);
  // type S shouldn't exist any more, in here to deal with historical rows
  $mQ = $sql->prepare("update meminfo set ads_OK=? where card_no=?");
  $cQ = $sql->prepare("update custdata set memType=?, Type=?,ChargeOk=1,Discount=?,MemDiscountLimit=?,ChargeLimit=?
        where CardNo=?");
  if ($row[0] == 'I' || $row[0] == 'T' || $row[0] == 'S'){
    $mR = $sql->execute($mQ, array($row['mailflag'], $memNum));
    $cR = $sql->execute($cQ, array($row['memtype1'], $row['memtype2'], $row['discount'], $row['chargelimit'], $row['chargelimit'], $memNum));
  }
  else if ($row[0] == 'X'){
    $mR = $sql->execute($mQ, array($row['mailflag'], $memNum));
    $cR = $sql->execute($cQ, array($row['memtype1'], $row['memtype2'], $row['discount'], $row['chargelimit'], $row['chargelimit'], $memNum));
  }
  $query = $sql->prepare("delete from suspensions where cardno=?");
  $result = $sql->execute($query, array($memNum));
  
  $username = validateUserQuiet('editmembers');
    
  $now = date("Y-m-d h:i:s");
  $query = $sql->prepare("insert into suspension_history (username, postdate, post, cardno, reasoncode)
            values (?,?,'Account reactivated',?,-1)");
  $result = $sql->execute($query, array($username, $now, $memNum));

  // add record to denote account was activated
  // this record is not considered "active" because
  // the account is not suspended
  $model->reasonCode(0);
  $model->suspensionTypeID(0);
  $model->username($username);
  $model->tdate($now);
  $model->active(0);
  $model->save();

  $model->reset();
  $model->card_no($memNum);
  $model->active(1);
  foreach($model->find() as $obj) {
    $obj->active(0);
    $obj->save();
  }
}

function addressFormLimited($memNum)
{
    global $sql;
    $custQ = $sql->prepare("SELECT * FROM custdata where CardNo = ? and personnum= 1");
    $custR = $sql->execute($custQ, array($memNum));
    $typeRow = $sql->fetchRow($custR);
    $type = trim($typeRow['memType']," ");
    $status = trim($typeRow['Type']," ");
    $memcoupons = $typeRow['memCoupons'];
    if ($status != "PC") $memcoupons = 0;
    if($status == 'PC') $status = 'ACTIVE';
    if($status == 'REG') $status = 'NONMEM';
    //echo "<br> Here is type: " .$type;

    $infoQ = $sql->prepare("SELECT * FROM meminfo WHERE card_no=?");
    $infoR = $sql->execute($infoQ, array($memNum));
    $infoW = $sql->fetch_row($infoR);
    $getsMail = $infoW['ads_OK'];

    $cardsQ = $sql->prepare("SELECT upc FROM memberCards WHERE card_no=?");
    $cardsR = $sql->execute($cardsQ, array($memNum));
    $cardUPC = "";
    if ($sql->num_rows($cardsR) > 0){
        $cardsW = $sql->fetch_row($cardsR);
        $cardUPC = $cardsW['upc'];
    }

    $suspensionQ = $sql->prepare("select type,reason from suspensions where cardno=?");
    $suspensionR = $sql->execute($suspensionQ, array($memNum));
    $suspensionW = $sql->fetchRow($suspensionR);
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
        echo '<tr>';
            echo "<td></td>";
            echo "<td bgcolor='FFFF33'>First Name</td>";
            echo "<td bgcolor='FFFF33'>Last Name</td>";
            $noteQ = $sql->prepare("select note from memberNotes where cardno=? order by stamp desc limit 1");
            $noteR = $sql->execute($noteQ, array($memNum));
            $notetext = "";
            if ($sql->num_rows($noteR) == 1){
                $notetext = stripslashes(array_pop($sql->fetchRow($noteR)));
                $notetext = preg_replace("/<br \/>/","\n",$notetext);
            }
            echo "<td rowspan=4 colspan=3><textarea name=notetext rows=7 cols=50>$notetext</textarea></td>";
        echo "</tr>";
        $nameQ = $sql->prepare("SELECT firstName,LastName FROM custdata WHERE cardno=? and personnum > 1 order by personnum");
        $nameR = $sql->execute($nameQ, array($memNum));
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

