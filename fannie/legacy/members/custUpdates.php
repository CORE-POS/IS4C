<?php
include('../../config.php');

include('../lanedefs.php');

if (!class_exists('FannieAPI')) include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

/**
  @deprecated
  Using BasicModel::pushToLanes to re-add customer records
*/
function addCustomerAllLanes($cardno){
	global $lanes,$numlanes,$dbs,$sql,$types;
	for ($i = 0; $i < $numlanes; $i++){
		if ($types[$i] == "MSSQL"){
			$addQ = "insert $lanes[$i].$dbs[$i].dbo.custdata
				SELECT CardNo,personNum,LastName,FirstName,
                                CashBack,Balance,Discount,ChargeLimit,ChargeOK,
                                WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
                                NumberOfChecks,memCoupons,blueLine,Shown 
				from custdata where cardno='$cardno' AND Type<>'TERM'";
			$addR = $sql->query($addQ);
		}
		else {
			$sql->add_connection($lanes[$i],$types[$i],$dbs[$i],'root','is4c');
			$selQ = "SELECT CardNo,personNum,LastName,FirstName,
                                CashBack,Balance,Discount,ChargeLimit,ChargeOK,
                                WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
                                NumberOfChecks,memCoupons,blueLine,Shown FROM custdata WHERE cardno='$cardno'";	
			if ($lanes[$i] != "129.103.2.16")
				$selQ .= " AND type <> 'TERM'";
			$ins = "INSERT INTO custdata (CardNo,personNum,LastName,FirstName,
                                CashBack,Balance,Discount,ChargeLimit,ChargeOK,
                                WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
				NumberOfChecks,memCoupons,blueLine,Shown)";
			$sql->transfer('is4c_op',$selQ,$dbs[$i],$ins);
		}
	}
}

function deleteCustomerAllLanes($cardno){
	global $lanes,$numlanes,$dbs,$sql,$types;
	for ($i = 0; $i < $numlanes; $i++){
		if ($types[$i] == "MSSQL"){
			$delQ = "delete from $lanes[$i].$dbs[$i].dbo.custdata where cardno='$cardno'";
			$delR = $sql->query($delQ);
		}
		else {
			$tmp = new SQLManager($lanes[$i],$types[$i],$dbs[$i],'root','is4c');
			$delQ = "DELETE FROM custdata WHERE cardno='$cardno'";
			$delR = $tmp->query($delQ);
		}
	}
}

function redoCard($cardno){
	global $lanes,$numlanes,$dbs,$sql,$types;

	$upcQ = "SELECT upc FROM memberCards WHERE card_no=$cardno";
	$upcR = $sql->query($upcQ);
	$upc = "";
	if ($sql->num_rows($upcR) > 0)
		$upc = array_pop($sql->fetch_row($upcR));

	for ($i = 0; $i < $numlanes; $i++){
		if ($types[$i] == "MSSQL"){
			$delQ = "delete from $lanes[$i].$dbs[$i].dbo.memberCards where card_no='$cardno'";
			$delR = $sql->query($delQ);
			if (!empty($upc)){
				$sql->query("INSERT INTO $lanes[$i].$dbs[$i].dbo.memberCards
						VALUES ($cardno,'$upc')");
			}
		}
		else {
			$tmp = new SQLManager($lanes[$i],$types[$i],$dbs[$i],'root','is4c');
			$delQ = "DELETE FROM memberCards WHERE card_no='$cardno'";
			$delR = $tmp->query($delQ);
			if (!empty($upc)){
				$tmp->query("INSERT INTO memberCards
						VALUES ($cardno,'$upc')");
			}
		}
	}

}

function updateCustomerAllLanes($cardno){
    global $sql;
	deleteCustomerAllLanes($cardno);
    $model = new CustdataModel($sql);
    $model->CardNo($cardno);
    foreach($model->find('personNum') as $obj) {
        $obj->pushToLanes();
    }
	redoCard($cardno);
}

?>
