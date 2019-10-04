<?php

namespace COREPOS\Fannie\API\data;
use \FannieDB;
use \FannieConfig;
use \CustdataModel;
use \MemberCardsModel;

class MemberSync
{
    public static function sync($cardNo)
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));

        self::syncLocal($dbc, $cardNo);
        self::syncRemote($dbc, $cardNo);
    }

    private static function syncLocal($dbc, $cardNo)
    {
        $custdata = new CustdataModel($dbc);
        $custdata->CardNo($cardNo);
        foreach ($custdata->find() as $c) {
            $c->pushToLanes();
        }

        $cards = new MemberCardsModel($dbc);
        $cards->card_no($cardNo);
        $cards->load();
        $cards->pushToLanes();
    }

    private static function syncRemote($dbc, $cardNo)
    {
        $prep = $dbc->prepare('
            SELECT webServiceUrl FROM Stores WHERE hasOwnItems=1 AND storeID<>?
            ');
        $res = $dbc->execute($prep, array(FannieConfig::config('STORE_ID')));
        while ($row = $dbc->fetchRow($res)) {
            $client = new \Datto\JsonRpc\Http\Client($row['webServiceUrl']);
            $client->query(time(), 'COREPOS\\Fannie\\API\\webservices\\FannieMemberLaneSync', array('id'=>$cardNo));
            $client->send();
        }
    }
} 

