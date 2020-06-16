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

    private $notEditable = array(
        'Custdata',
        'Meminfo',
        'MemDates',
        'CustomerAccounts',
        'Customers',
        'MemberCards',
        'MemContact',
        'VendorContact',
    );

    /**
     * Check for basic errors before processing
     * @param $args [object] decoded JSON input
     * @param $ret [array] preliminary return value
     * @return
     *  - [array] error details OR
     *  - [boolean] false
     */
    private function checkBasicErrors($args, $ret)
    {
        if (!property_exists($args, 'entity') || !property_exists($args, 'submethod') || !property_exists($args, 'columns')) {
            // missing required arguments
            $ret['error'] = array(
                'code' => -32602,
                'message' => 'Invalid parameters',
            );
            return $ret;
        }

        if (count($args->columns) == 0) {
            $ret['error'] = array(
                'code' => -32601,
                'message' => 'Columns cannot be empty',
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

        if ($method == 'set' && in_array($args->entity, $this->notEditable)) {
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

        return false;
    }

    /**
     * Check if insert is supported for this model
     * @param $obj [BasicModel] instance
     * @return [boolean]
     *
     * Currently supported if:
     * - single primary key column w/ auto-increment behavior
     */
    private function canIncrementInsert($obj)
    {
        $pkCols = array_filter($obj->getColumns(), function ($i) { return isset($i['primary_key']) && $i['primary_key']; });

        return count($pkCols) === 1 && isset($pkCols[0]['increment']) && $pkCols[0]['increment'];
    }

    public function run($args=array())
    {
        $ret = array();
        $errored = $this->checkBasicErrors($args, $ret);
        if ($errored !== false) {
            return $errored;
        }

        /**
         * Make sure all provided columns are valid
         */
        $model = $args->entity . 'Model';
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

        /**
         * Set provided column values in model object
         */
        foreach ($args->columns as $key => $val) {
            $obj->$key($val);
        }

        $method = strtolower($args->submethod);
        if ($method == 'get') {
            $ret['result'] = array_map(function ($i) { return $i->toJSON(); }, $obj->find());
        } else {
            if ($obj->isUnique()) {
                /**
                 * PK column(s) were provided so saving should be possible.
                 * This may create a new record w/ natural primary keys
                 */
                $saved = $obj->save();
                if ($saved === false) {
                    $ret['error'] = array(
                        'code' => -32000,
                        'message' => 'Error saving data',
                    );
                } else {
                    $obj->load();
                    $ret['result'] = $obj->toJSON();
                }
            } elseif ($this->canIncrementInsert($obj)) {
                /**
                 * PK not specified but can create a new
                 * record by auto increment
                 */
                $newID = $obj->save();
                if ($newID === false) {
                    $ret['error'] = array(
                        'code' => -32000,
                        'message' => 'Error saving data',
                    );
                } else {
                    $pkCol = false;
                    foreach ($obj->getColumns() as $col => $info) {
                        if (isset($info['primary_key']) && $info['primary_key']) {
                            $pkCol = $col;
                            break;
                        }
                    }
                    $obj->reset();
                    $obj->$pkCol($newID);
                    $obj->load();
                    $ret['result'] = $obj->toJSON();
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

