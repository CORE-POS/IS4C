<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
  @class SyncLanes
*/

class SyncLanes 
{

    /**
      Do not truncate any tables
    */
    const TRUNCATE_NONE         = 0;
    /**
      Truncate the source table AFTER
      copying it
    */
    const TRUNCATE_SOURCE        = 1;
    /**
      Truncate the destination table BEFORE
      inserting into it
    */
    const TRUNCATE_DESTINATION    = 2;
    

    /**
      Copy a table from the server to the lanes
      @param $table string table name
      @param $db string 'op' or 'trans'
        (default is 'op')
      @param $truncate integer
        (default is TRUNCATE_DESTINATION)
      @return array
        - sending => boolean attempted to copy table
        - messages => string result information
    */
    static public function pushTable($table,$db='op',$truncate=self::TRUNCATE_DESTINATION)
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_LANES;

        $ret = array('sending'=>True,'messages'=>'');

        $db = strtolower($db);
        if ($db != 'op' && $db != 'trans') {
            $ret['sending'] = False;
            $ret['messages'] = 'Error: Invalid database: '.$db;
            return $ret;
        } elseif(empty($table)) {
            $ret['sending'] = False;
            $ret['messages'] = 'Error: No table given';
            return $ret;
        } elseif (ereg("[^A-Za-z0-9_]",$table)) {
            $ret['sending'] = False;
            $ret['messages'] = 'Error: Illegal table name: '.$table;
            return $ret;
        }

        $special = dirname(__FILE__).'/../../sync/special/'.$table.'.php';
        if (file_exists($special)) {
            /* Use special script to send table.
             * Usually with mysqldump.
             *  Much faster if both sides are mysql.
            */
            ob_start();
            $outputFormat = 'plain';
            include($special);
            $tmp = ob_get_clean();
            $ret = array('sending'=>True,'messages'=>'');
            $ret['messages'] = $tmp;
            return $ret;
        } else {
            /* use the transfer option in SQLManager
            *   to copy records onto each lane
            */
            $server_db = $db=='op' ? $FANNIE_OP_DB : $FANNIE_TRANS_DB;
            $dbc = FannieDB::get( $server_db );
            $laneNumber=1;
            foreach($FANNIE_LANES as $lane) {
                $dbc->add_connection($lane['host'],$lane['type'],
                    $lane[$db],$lane['user'],$lane['pw']);
                if ($dbc->connections[$lane[$db]]) {
                    if ($truncate & self::TRUNCATE_DESTINATION) {
                        $dbc->query("TRUNCATE TABLE $table",$lane[$db]);
                    }
                    $success = $dbc->transfer($server_db,
                               "SELECT * FROM $table",
                               $lane[$db],
                               "INSERT INTO $table");
                    $dbc->close($lane[$db]);
                    if ($success) {
                        $ret['messages'] .= "Lane $laneNumber ({$lane['host']}) $table completed successfully";
                    } else {
                        $ret['messages'] .= "Error: Lane $laneNumber ({$lane['host']}) $table completed but with some errors";
                    }
                } else {
                    $ret['messages'] .= "Error: Couldn't connect to lane $laneNumber ({$lane['host']})";
                }
                $laneNumber++;
            }
            if ($truncate & self::TRUNCATE_SOURCE) {
                $dbc->query("TRUNCATE TABLE $table",$server_db);
            }

            return $ret;
        }
    }

    static public function push_table($table,$db='op',$truncate=self::TRUNCATE_DESTINATION)
    {
        return self::pushTable($table, $db, $truncate);
    }

    /**
      Copy a table from the lanes to the server
      @param $table string table name
      @param $db string 'op' or 'trans'
        (default is 'trans')
      @param $truncate integer
        (default is TRUNCATE_SOURCE)
      @return array
        - sending => boolean attempted to copy table
        - messages => string result information
    */
    static public function pullTable($table,$db='trans',$truncate=self::TRUNCATE_SOURCE)
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_LANES;

        $ret = array('sending'=>True,'messages'=>'');

        $db = strtolower($db);
        if ($db != 'op' && $db != 'trans') {
            $ret['sending'] = False;
            $ret['messages'] = 'Error: Invalid database: '.$db;
            return $ret;
        } elseif(empty($table)) {
            $ret['sending'] = False;
            $ret['messages'] = 'Error: No table given';
            return $ret;
        } elseif (ereg("[^A-Za-z0-9_]",$table)) {
            $ret['sending'] = False;
            $ret['messages'] = 'Error: Illegal table name: '.$table;
            return $ret;
        }

        // use the transfer option in SQLManager to copy
        // records from each lane
        $server_db = $db=='op' ? $FANNIE_OP_DB : $FANNIE_TRANS_DB;
        $dbc = FannieDB::get( $server_db );
        if ($truncate & self::TRUNCATE_DESTINATION) {
            $dbc->query("TRUNCATE TABLE $table",$server_db);
        }
        $laneNumber=1;
        foreach($FANNIE_LANES as $lane) {
            $dbc->add_connection($lane['host'],$lane['type'],
                $lane[$db],$lane['user'],$lane['pw']);
            if ($dbc->connections[$lane[$db]]) {
                $success = $dbc->transfer($lane[$db],
                           "SELECT * FROM $table",
                           $server_db,
                           "INSERT INTO $table");
                if ($truncate & self::TRUNCATE_SOURCE) {
                    $dbc->query("TRUNCATE TABLE $table",$lane[$db]);
                }
                $dbc->close($lane[$db]);
                if ($success) {
                    $ret['messages'] .= "Lane $laneNumber ({$lane['host']}) $table completed successfully";
                } else {
                    $ret['messages'] .= "Error: Lane $laneNumber ({$lane['host']}) $table completed but with some errors";
                }
            } else {
                $ret['messages'] .= "Error: Couldn't connect to lane $laneNumber ({$lane['host']})";
            }
            $laneNumber++;
        }

        return $ret;
    }

    static public function pull_table($table,$db='trans',$truncate=self::TRUNCATE_SOURCE)
    {
        return self::pullTable($table, $db, $truncate);
    }
}

