<?php

namespace COREPOS\Fannie\API\webservices; 
use COREPOS\Fannie\API\member\MemberREST;
use \FannieDB;
use \FannieConfig;

/**
 * Sample request. Supported submethods are "get" and "set"
    {
        "jsonrpc": "2.0",
        "method": "\\COREPOS\\Fannie\\API\\webservices\\FannieEntity",
        "id": "9382839393292",
        "params": {
            "entity": "Products",
            "submethod": "set",
            "columns": {
                "upc" : "0000000004011",
                description" : "BANANAS"
            }
        }
    }
 */

class FannieEntity extends FannieWebService
{
    public $type = 'json'; // json/plain by default

    private $blacklist = array(
        'Custdata',
        'Meminfo',
        'MemDates',
        'CustomerAccounts',
        'Customers',
        'MemberCards',
        'MemContact',
        'VendorContact',
    );

    public function run($args=array())
    {
        $ret = array();
        if (!property_exists($args, 'entity') || !property_exists($args, 'submethod') || !property_exists($args, 'columns')) {
            // missing required arguments
            $ret['error'] = array(
                'code' => -32602,
                'message' => 'Invalid parameters',
            );
            return $ret;
        }

        $method = strtolower($args->submethod);
        if ($method !== 'get' && $method !== 'set') {
            $ret['error'] = array(
                'code' => -32601,
                'message' => 'Invalid submethod',
            );
            return $ret;
        }

        if ($method == 'set' && in_array($args->entity, $this->blacklist)) {
            $ret['error'] = array(
                'code' => -32600,
                'message' => 'Set not allowed on this entity',
            );
            return $ret;
        }

        $model = $args->entity . 'Model';
        if (!class_exists($model)) {
            $ret['error'] = array(
                'code' => -32600,
                'message' => 'Invalid entity',
            );
            return $ret;
        }

        $obj = new $model(FannieDB::get(FannieConfig::config('OP_DB')));
        $cols = $obj->getColumns();
        foreach ($args->columns as $key => $val) {
            if (!isset($cols[$key])) {
                $ret['error'] = array(
                    'code' => -32600,
                    'message' => 'Invalid column ' . $key,
                );
                return $ret;
            }
        }

        foreach ($args->columns as $key => $val) {
            $obj->$key($val);
        }
        if ($method == 'get') {
            $ret['result'] = array_map(function ($i) { return $i->toJSON(); }, $obj->find());
        } else {
            if ($obj->isUnique()) {
                $saved = $obj->save();
                if ($saved === false) {
                    $ret['error'] = array(
                        'code' => -32000,
                        'message' => 'Error saving data',
                    );
                } else {
                    $ret['result'] = 'OK';
                }
            } else {
                $ret['error'] = array(
                    'code' => -32600,
                    'message' => 'Invalid set of columns',
                );
            }
        }

        return $ret;
    }
}

