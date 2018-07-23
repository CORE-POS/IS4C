<?php

namespace COREPOS\Fannie\API\jobs;
use \FannieConfig;
use \FannieDB;

/**
 * @class SqlUpdate
 *
 * Push a database update into the job queue
 *
 * Data format:
 * {
 *     'table': 'Name.of.table',
 *     'set': {
 *         'columnName': 'columnValue',
 *         'columnName': 'columnValue',
 *         ...
 *     },
 *     'where': {
 *         'columnName': 'columnValue',
 *         'columnName': 'columnValue',
 *         ...
 *     }
 * }
 *
 * Table name is required.
 * One or more name/value pairs is required in 'set'.
 * One or more name/value pairs is required in 'where'.
 */
class SqlUpdate extends Job
{
    public function run()
    {
        $config = FannieConfig::factory();
        $dbc = FannieDB::get($config->get('OP_DB'));

        if (!isset($this->data['table'])) {
            echo 'Error: no table specified' . PHP_EOL;
            return false;
        }
        $table = $this->data['table'];
        if (!$dbc->tableExists($table)) {
            echo 'Error: table does not exist: ' . $table . PHP_EOL;
            return false;
        } 
        if (!isset($this->data['where']) || !isset($this->data['set'])) {
            echo 'Error: no update provided' . PHP_EOL;
            return false;
        }
        if (!is_array($this->data['where']) || !is_array($this->data['set'])) {
            echo 'Error: malformed update' . PHP_EOL;
            return false;
        }
        if (count($this->data['where']) == 0 || count($this->data['set']) == 0) {
            echo 'Error: empty update' . PHP_EOL;
            return false;
        }

        $query = 'UPDATE ' . $table . ' SET ';
        $args = array();
        foreach ($this->data['set'] as $col => $val) {
            $query .= $dbc->identifierEscape($col) . '=?,';
            $args[] = $val;
        }
        $query = substr($query, 0, strlen($query)-1);
        $query .= ' WHERE 1=1 ';
        foreach ($this->data['where'] as $col => $val) {
            $query .= ' AND ' . $dbc->identifierEscape($col) . '=?,';
            $args[] = $val;
            if ($col == $val) {
                echo "Skipping update. This looks dangerous\n";
                echo "WHERE values {$col} and {$val} appear to be equal\n";
                return false;
            }
        }
        $query = substr($query, 0, strlen($query)-1);

        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        if ($res === false) {
            echo "Update failed" . PHP_EOL;
            echo "SQL error: " . $dbc->error() . PHP_EOL;
            echo "Input data: " . print_r($this->data, true) . PHP_EOL;
        }
    }
}

