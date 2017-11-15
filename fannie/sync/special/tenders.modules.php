<?php

$dbc = FannieDB::get($FANNIE_OP_DB);
$model = new TendersModel($dbc);
$tenders = $model->find();
$ids = array();
foreach ($tenders as $t) {
    $ids[] = $t->TenderID();
}
list($idIn, $idArgs) = $dbc->safeInClause($ids);
foreach ($FANNIE_LANES as $lane) {
    $dbc->addConnection($lane['host'],$lane['type'],$lane['op'],
            $lane['user'],$lane['pw']);
    if ($dbc->isConnected($lane['op'])) {
        $clearP = $dbc->prepare("DELETE FROM tenders WHERE TenderID NOT IN ({$idIn})", $lane['op']);
        $clearR = $dbc->execute($clearP, $idArgs, $lane['op']);
        $chkP = $dbc->prepare("SELECT TenderID FROM tenders WHERE TenderID=?", $lane['op']);
        $insP = $dbc->prepare("INSERT INTO tenders (TenderID, TenderCode, TenderName, TenderType,
                    ChangeMessage, MinAmount, MaxAmount, MaxRefund, TenderModule, SalesCode)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'TenderModule', ?", $lane['op']);
        $upP = $dbc->prepare("UPDATE tenders SET TenderCode=?, TenderName=?, TenderType=?, ChangeMessage=?,
                    MinAmount=?, MaxAmount=?, MaxRefund=?, SalesCode=? WHERE TenderID=?", $lane['op']);
        foreach ($tenders as $t) {
            if ($dbc->getValue($chkP, array($t->TenderID()), $lane['op'])) {
                $dbc->execute($upP, array($t->TenderCode(), $t->TenderName(), $t->TenderType(), $t->ChangeMessage(),
                    $t->MinAmount(), $t->MaxAmount(), $t->MaxRefund(), $t->SalesCode(), $t->TenderID()), $lane['op']);
            } else {
                $dbc->execute($insP, array($t->TenderID(), $t->TenderCode(), $t->TenderName, $t->TenderType(),
                    $t->ChangeMessage(), $t->MinAmount(), $t->MaxAmount(), $t->MaxRefund(), $t->SalesCode()), $lane['op']);
            }
        }
    }
}

echo "<li>Tender table synched</li>";

