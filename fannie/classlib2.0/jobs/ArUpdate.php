<?php

namespace COREPOS\Fannie\API\jobs;
use \FannieConfig;
use \FannieDB;
use \CustdataModel;

use Datto\JsonRpc\Http\Client as JsonRpc;

/**
 * @class ArUpdate
 * 
 * Trigger AR account balance update
 * and push updated customers to all lanes
 *
 * Data format:
 * {
 *     'id': 'Customer#'
 * }
 */
class ArUpdate extends Job
{
    public function run()
    {
        $config = FannieConfig::factory();
        $dbc = FannieDB::get($config->get('OP_DB'));
        if (!isset($this->data['id'])) {
            echo "Error: no customer ID provided" . PHP_EOL;
            return false;
        }

        $prep = $dbc->prepare('SELECT balance
            FROM ' . FannieDB::fqn('ar_live_balance', 'trans') . '
            WHERE card_no=?');
        $balance = $dbc->getValue($prep, array($this->data['id']));
        if ($balance === false) {
            echo "No account found for " . $this->data['id'] . PHP_EOL;
            return false;
        }

        $upP = $dbc->prepare('UPDATE custdata SET Balance=? WHERE CardNo=?');
        $dbc->execute($upP, array($balance, $this->data['id']));

        $custdata = new CustdataModel($dbc);
        $custdata->CardNo($this->data['id']);
        foreach ($custdata->find() as $c) {
            $c->pushToLanes();
        }

        $prep = $dbc->prepare('
            SELECT webServiceUrl FROM Stores WHERE hasOwnItems=1 AND storeID<>?
            ');
        $res = $dbc->execute($prep, array($config->get('STORE_ID')));
        while ($row = $dbc->fetchRow($res)) {
            $client = new JsonRpc($row['webServiceUrl']);
            $client->query(time(), 'COREPOS\\Fannie\\API\\webservices\\FannieMemberLaneSync', array('id'=>$this->card_no));
            $client->send();
        }
    }
}

