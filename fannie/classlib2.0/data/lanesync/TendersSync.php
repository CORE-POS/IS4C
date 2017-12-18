<?php

namespace COREPOS\Fannie\API\data\lanesync;
use COREPOS\Fannie\API\data\SyncSpecial;
use \FannieDB;
use \TendersModel;

/**
  Special sync routine for the tenders table.
  The server doesn't know what values are in tenders.TenderModule or
  even know what values are possible to present an editor UI (available
  modules could vary depending on the lane's plugin configuration). So
  this routine updates existing records to preserve the lane-side configured
  tenders.TenderModule values.

  This table is generally a couple dozen records, tops, so the overhead of
  extra queries doesn't really matter
*/
class TendersSync extends SyncSpecial
{
    public function push($tableName, $dbName, $includeOffline=false)
    {
        $ret = array('success'=>true, 'details'=>'');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $model = new TendersModel($dbc);
        $tenders = $model->find();
        $ids = array();
        foreach ($tenders as $t) {
            $ids[] = $t->TenderID();
        }
        list($idIn, $idArgs) = $dbc->safeInClause($ids);
        
        foreach ($this->config->get('LANES') as $lane) {
            if (!$includeOffline && isset($lane['offline']) && $lane['offline']) {
                continue;
            }
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
            } else {
                $ret['success'] = false;
                $ret['details'] .= 'Could not connect to lane ' . $lane['host'] . '<br />';
            }
        }

        return $ret;
    }
}

