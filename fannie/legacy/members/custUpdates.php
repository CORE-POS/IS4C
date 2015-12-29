<?php
include('../../config.php');

if (!class_exists('FannieAPI')) include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

/**
  @deprecated
  Using BasicModel::pushToLanes to re-add customer records
*/
function addCustomerAllLanes($cardno){
    global $sql, $FANNIE_LANES;
    $cardno = sprintf('%d', $cardno);
    foreach($FANNIE_LANES as $lane) {
        if ($lane['type'] == "MSSQL"){
            $addQ = "insert {$lane['host']}.{$lane['op']}.dbo.custdata
                SELECT CardNo,personNum,LastName,FirstName,
                                CashBack,Balance,Discount,ChargeLimit,ChargeOK,
                                WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
                                NumberOfChecks,memCoupons,blueLine,Shown 
                from custdata where cardno='$cardno' AND Type<>'TERM'";
            $addR = $sql->query($addQ);
        }
        else {
            $sql->addConnection($lane['host'],$lane['type'],$lane['op'],$lane['user'],$lane['pw']);
            $selQ = "SELECT CardNo,personNum,LastName,FirstName,
                                CashBack,Balance,Discount,ChargeLimit,ChargeOK,
                                WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
                                NumberOfChecks,memCoupons,blueLine,Shown FROM custdata WHERE cardno='$cardno'";    
            if ($lane['host'] != "129.103.2.16")
                $selQ .= " AND type <> 'TERM'";
            $ins = "INSERT INTO custdata (CardNo,personNum,LastName,FirstName,
                                CashBack,Balance,Discount,ChargeLimit,ChargeOK,
                                WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
                NumberOfChecks,memCoupons,blueLine,Shown)";
            $sql->transfer('is4c_op',$selQ,$lane['op'],$ins);
        }
    }
}

function deleteCustomerAllLanes($cardno)
{
    global $FANNIE_LANES, $sql;
    foreach($FANNIE_LANES as $lane) {
        $tmp = new SQLManager($lane['host'],$lane['type'],$lane['op'],$lane['user'],$lane['pw']);
        $delQ = $tmp->prepare("DELETE FROM custdata WHERE cardno=?");
        $delR = $tmp->execute($delQ, array($cardno));
    }
}

function redoCard($cardno)
{
    global $FANNIE_LANES, $sql;

    $upcQ = $sql->prepare("SELECT upc FROM memberCards WHERE card_no=?");
    $upcR = $sql->execute($upcQ, array($cardno));
    $upc = "";
    if ($sql->num_rows($upcR) > 0) {
        $upcW = $sql->fetch_row($upcR);
        $upc = $upcW['upc'];
    }

    foreach($FANNIE_LANES as $lane) {
        $tmp = new SQLManager($lane['host'],$lane['type'],$lane['op'],$lane['user'],$lane['pw']);
        $delQ = $tmp->prepare("DELETE FROM memberCards WHERE card_no=?");
        $delR = $tmp->execute($delQ, array($cardno));
        if (!empty($upc)){
            $ins = $tmp->prepare("INSERT INTO memberCards (card_no, upc)
                    VALUES (?, ?)");
            $tmp->execute($ins, array($cardno, $upc));
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

