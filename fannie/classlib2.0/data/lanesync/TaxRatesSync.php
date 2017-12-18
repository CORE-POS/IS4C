<?php

namespace COREPOS\Fannie\API\data\lanesync;
use COREPOS\Fannie\API\data\SyncSpecial;
use \FannieDB;

/**
*/
class TaxRatesSync extends SyncSpecial
{
    public function push($tableName, $dbName, $includeOffline=false)
    {
        $ret = array('success'=>true, 'details'=>'');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        foreach ($this->config->get('LANES') as $lane) {
            if (!$includeOffline && isset($lane['offline']) && $lane['offline']) {
                continue;
            }
            $dbc->addConnection($lane['host'],$lane['type'],$lane['trans'],
                    $lane['user'],$lane['pw']);
            if ($dbc->isConnected($lane['trans'])) {
                $selectQ = '
                    SELECT id,
                        rate,
                        description
                    FROM taxrates';
                $insQ = '
                    INSERT INTO taxrates
                        (id, rate, description)';
                $dbc->query("TRUNCATE TABLE taxrates", $lane['trans']);
                $dbc->transfer($this->config->get('OP_DB'), $selectQ, $lane['trans'], $insQ);
                $ret['details'] .= "Lane completed {$lane['host']}\n";
            } else {
                $ret['success'] = false;
                $ret['details'] .= "Lane is offline {$lane['host']}\n";
            }
        }

        return $ret;
    }
}

