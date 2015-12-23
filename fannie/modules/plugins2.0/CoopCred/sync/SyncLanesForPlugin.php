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
    @class SyncLanesForPlugin
    Clones SyncLanes at this point, redefines everything.
     When issues sorted would like to replace $FCL2D/SyncLanes with this.
    Supports syncing a single lane.
    Uses extension .inc in sync/special instead of .php to avoid API scanning.
*/

class SyncLanesForPlugin extends SyncLanes
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
      @param $one_lane integer
      (default is 0, do all lanes
        if > 0, the lane to do)
      @return array
        - sending => boolean attempted to copy table
        - messages => string result information
    */
    static public function pushTable($table,$db='op',
        $truncate=self::TRUNCATE_DESTINATION,$one_lane=0)
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_LANES;
        /* Only if needed.
        global $FANNIE_PLUGIN_LIST, $FANNIE_PLUGIN_SETTINGS;
        global $FANNIE_SERVER;
        */

        $ret = array('sending'=>True,'messages'=>'');

        $dbOrig = $db;
        $db = strtolower($db);
        if ($db != 'op' && $db != 'trans' && substr($db,0,7) != 'plugin:') {
            $ret['sending'] = False;
            $ret['messages'] = 'Invalid database: '.$db;
            return $ret;
        } elseif(empty($table)) {
            $ret['sending'] = False;
            $ret['messages'] = 'No table given';
            return $ret;
        } elseif (preg_match('/^[^A-Za-z0-9_]+$/',$table)) {
            $ret['sending'] = False;
            $ret['messages'] = 'Illegal table name: '.$table;
            return $ret;
        }
        /* If the name of the db rather than the plugin is being passed,
         * $dbOrig is not needed.
         * $PLUGIN_LIST and $PLUGIN_SETTINGS not needed.
         */
        $server_db = '';
        $lane_db = '';
        if (substr($db,0,7) == 'plugin:') {
            $dbs = substr($db,7);
            list($server_db,$lane_db) = explode('|',$dbs);
            // Are these database-name checks really needed?
            if(empty($server_db)) {
                $ret['sending'] = False;
                $ret['messages'] = 'No server database given';
                return $ret;
            } elseif (preg_match('/^[^A-Za-z0-9_]+$/',$server_db)) {
                $ret['sending'] = False;
                $ret['messages'] = 'Illegal database name: '.$server_db;
                return $ret;
            }
            if(empty($lane_db)) {
                $ret['sending'] = False;
                $ret['messages'] = 'No lane database given';
                return $ret;
            } elseif (preg_match('/^[^A-Za-z0-9_]+$/',$lane_db)) {
                $ret['sending'] = False;
                $ret['messages'] = 'Illegal database name: '.$lane_db;
                return $ret;
            }
        }
         /* If the name of the plugin is being passed to here.
        if (substr($db,0,7) == 'plugin:') {
            $plugin = substr($dbOrig,7);
            if (!in_array("$plugin",$FANNIE_PLUGIN_LIST)) {
                $ret['sending'] = False;
                $ret['messages'] = "Plugin: $plugin is not enabled.";
                return $ret;
            }
            if (!isset($FANNIE_PLUGIN_SETTINGS["{$plugin}Database"])) {
                $ret['sending'] = False;
                $ret['messages'] = "Database for Plugin: $plugin is not assigned.";
                return $ret;
            }
            $db = $FANNIE_PLUGIN_SETTINGS["{$plugin}Database"];
        }
          */

        // In the plugin/sync.
        $special = dirname(__FILE__).'/special/'.$table.'.inc';
        // If in $FCL2D path is different.
        //$special = dirname(__FILE__).'/../../../sync/special/'.$table.'.php';
        if (file_exists($special)) {
            /* Use special script to send table.
             * Usually with mysqldump.
             *  Much faster if both sides are mysql.
            */
            ob_start();
            $outputFormat = 'plain';

            $tmp2="";
            $tmp2 .= "In pushTable ";
            $tmp2 .= (isset($one_lane)) ? "one_lane is set to {$one_lane}." :
                "one_lane is not set. ";
            include($special);
            $tmp = ob_get_clean();
            $ret = array('sending'=>True,'messages'=>'');
            $ret['messages'] .= $tmp2;
            $ret['messages'] .= $tmp;
            return $ret;
        } else {
            $ret['messages'] .= "Doing by SQL Transfer\n";
            /* use the transfer option in SQLManager
            *   to copy records onto each lane
            */
            if ($db=='op') {
                $server_db = $FANNIE_OP_DB;
            } elseif ($db=='trans') {
                $server_db = $FANNIE_TRANS_DB;
            } else {
                $noop = 0;
            }
            $ret['messages'] .= "server_db: $server_db  lane_db: $lane_db \n";
            $dbc = FannieDB::get( $server_db );
            $laneNumber=0;
            foreach($FANNIE_LANES as $lane) {
                $laneNumber++;
                if (isset($one_lane) && $one_lane > 0 && $laneNumber != $one_lane) {
                    $ret['messages'] .= "pushTable: skip {$laneNumber}";
                    continue;
                }
                // If writing to a non-standard db reassign $lane['op'] to it.
                if ($lane_db != '') {
                    $db = 'op';
                    $lane[$db] = $lane_db;
                    /* Since lane and server db may not be the same this isn't needed.
                    if ($lane['host'] == $FANNIE_SERVER) {
                        // Message during development only.
                        $ret['messages'] .= "Lane $laneNumber ({$lane['host']}) " .
                            "Skipped lane on Fannie host: $FANNIE_SERVER \n";
                        continue;
                    }
                     */
                }
                // This creates the lane-side DB if it doesn't exist.
                $ret['messages'] .= "Before addConnection()\n";
                    $success = $dbc->addConnection($lane['host'],$lane['type'],
                                    $lane[$db],$lane['user'],$lane['pw']);
                if ($success) {
                    $ret['messages'] .= "After addConnection() OK\n";
                } else {
                    $ret['messages'] .= "After addConnection() failed\n";
                }
                if ($dbc->connections[$lane[$db]]) {
                    if (!$dbc->tableExists($table,$lane[$db])) {
                        $ret['messages'] .=
                            ("Lane $laneNumber ({$lane['host']}) $table does not exist.\n" .
                            " This utility cannot create destination tables " .
                            " but the fannie/sync/special utilties can.\n"
                        );
                        continue;
                    }
                    if ($truncate & self::TRUNCATE_DESTINATION) {
                        $success = $dbc->query("TRUNCATE TABLE $table",$lane[$db]);
                    }
                    if (!$success) {
                        $ret['messages'] .=
                            ("Lane $laneNumber ({$lane['host']}) $table " .
                            "could not be truncated.\n" .
                            " Aborting sync of this table to this lane.\n");
                        continue;
                    }
                    $success = $dbc->transfer($server_db,
                               "SELECT * FROM $table",
                               $lane[$db],
                               "INSERT INTO $table");
                    $dbc->close($lane[$db]);
                    if ($success) {
                        $ret['messages'] .=
                            "Lane $laneNumber ({$lane['host']}) $table " .
                            "completed successfully. ";
                    } else {
                        $ret['messages'] .=
                            "Lane $laneNumber ({$lane['host']}) $table " .
                            "completed but with some errors. ";
                    }
                } else {
                    $ret['messages'] .= "Couldn't connect to lane $laneNumber " .
                        "({$lane['host']}). ";
                }
            }
            if ($truncate & self::TRUNCATE_SOURCE) {
                $success = $dbc->query("TRUNCATE TABLE $table",$server_db);
                if (!$success) {
                    $ret['messages'] .=
                        "Server $server_db table: $table could not be truncated.";
                }
            }

            return $ret;
        }

    // pushTable()
    }

    static public function push_table($table,$db='op',
        $truncate=self::TRUNCATE_DESTINATION,$one_lane=0)
    {
        return self::pushTable($table, $db, $truncate, $one_lane);
    }

    /**
      Copy a table from the lanes to the server
      @param $table string table name
      @param $db string 'op' or 'trans'
        (default is 'trans')
      @param $truncate integer
        (default is TRUNCATE_SOURCE)
      @param $one_lane integer
      (default is 0, do all lanes
        if > 0, the lane to do)
      @return array
        - sending => boolean attempted to copy table
        - messages => string result information
      NOT USED IN COOP CRED, not tested.
    */
    static public function pullTable($table,$db='trans',
        $truncate=self::TRUNCATE_SOURCE,$one_lane=0)
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_LANES;

        $ret = array('sending'=>True,'messages'=>'');

        $db = strtolower($db);
        if ($db != 'op' && $db != 'trans') {
            $ret['sending'] = False;
            $ret['messages'] = 'Invalid database: '.$db;
            return $ret;
        } elseif(empty($table)) {
            $ret['sending'] = False;
            $ret['messages'] = 'No table given';
            return $ret;
        } elseif (preg_match('/^[^A-Za-z0-9_]+$/',$table)) {
            $ret['sending'] = False;
            $ret['messages'] = 'Illegal table name: '.$table;
            return $ret;
        }

        /* use the transfer option in SQLManager
         * to copy records from each lane
         */
        $server_db = $db=='op' ? $FANNIE_OP_DB : $FANNIE_TRANS_DB;
        $dbc = FannieDB::get( $server_db );
        if ($truncate & self::TRUNCATE_DESTINATION) {
            $dbc->query("TRUNCATE TABLE $table",$server_db);
        }
        $laneNumber=1;
        foreach($FANNIE_LANES as $lane) {
            if (isset($one_lane) && $one_lane > 0 && $laneNumber != $one_lane) {
                $ret['messages'] .= "pullTable: skip {$laneNumber}";
                $laneNumber++;
                continue;
            }
            $dbc->addConnection($lane['host'],$lane['type'],
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
                    $ret['messages'] .= "Lane $laneNumber ({$lane['host']}) $table completed but with some errors";
                }
            } else {
                $ret['messages'] .= "Couldn't connect to lane $laneNumber ({$lane['host']})";
            }
            $laneNumber++;
        }

        return $ret;

    // pullTable()
    }

    static public function pull_table($table,$db='trans',$truncate=self::TRUNCATE_SOURCE,$one_lane=0)
    {
        return self::pullTable($table, $db, $truncate,$one_lane);
    }
}

