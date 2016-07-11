<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op
    Copyright 2014 West End Food Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* Version for command line use WITH lane# argument.
 * This version will go away when FannieTask can pass arguments.
 */

class CoopCredOneLaneSyncTask extends FannieTaskArgs
{
    public $name = 'Coop Cred One Lane Sync';

public $description = "Bring tables on one lane into the same state as server tables.

DO NOT Enable this as an Automated Task.
Only run it from the command line:
sudo -u www-data php /path/to/FannieTask.php \
CoopCredOneLaneSyncTask lane#
        
Replace these tables on all lanes with contents of
  server table:
    coop_cred: CCredPrograms, CCredMemberships

If you can use fannie/sync/special/coopcred.mysql.inc
 the transfers will go much faster.

If you cannot use CoopCred/sync/special/coopcred.mysql.inc
 then you must create the tables and database on the
 lanes before you run this task.

Coordinate this task with tasks such as Coop Cred History
 that update the tables this is pushing to the lanes
 so that the lanes have the most current data.

If run from the command line can take a lane-number
 parameter: CoopCredOneLaneSyncTask lane#
 to sync a single lane.
";

    public $default_schedule = array(
        'min' => 30,
        'hour' => 0,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    /* When $FannieTask->arguments exists:
     public function run()
    public function run($lane=array(0))
     */
    public function run()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_LANES;

        /* When $FannieTask->arguments exists:
        $oneLane = $lane[0];
         */
        $oneLane = (isset($this->arguments[0])) ? $this->arguments[0] : 0;
        if (!preg_match('/^\d+$/',$oneLane)) {
        $this->cronMsg("lane argument <{$oneLane}> must be an integer.");
        return;
        }
        if ($oneLane > count($FANNIE_LANES)) {
            echo $this->cronMsg("oneLane: $oneLane is more than the ".
                count($FANNIE_LANES)." that are configured.");
            return;
        }

        set_time_limit(0);

        /* This was written with the idea that the tables of the plugin
         *  might be named in plugin settings,
         *  but at this point they aren't.
         *  Also that it might sync tables for more than one plugin.
         */
        $plugins = array('CoopCred'=>array('CCredPrograms','CCredMemberships'));
        foreach ($plugins as $plugin => $tables) {
            if (FanniePlugin::isEnabled($plugin)){
                foreach ($tables as $table) {
                    //echo $this->cronMsg("Doing: $table");
                    if (isset($FANNIE_PLUGIN_SETTINGS["{$plugin}LaneDatabase"])) {
                        $dbs = $FANNIE_PLUGIN_SETTINGS["{$plugin}Database"] . '|' .
                                $FANNIE_PLUGIN_SETTINGS["{$plugin}LaneDatabase"];
                        $result = SyncLanesForPlugin::pushTable("$table",
                            "plugin:{$dbs}",
                            SyncLanesForPlugin::TRUNCATE_DESTINATION,
                            $oneLane);
                        echo $this->cronMsg($result['messages']);
                    } else {
                        echo $this->cronMsg("Lane Database for Plugin: $plugin is not assigned.");
                    }
                }
            } else {
                echo $this->cronMsg("$plugin is not enabled.");
            }
        // each plugin
        }

        echo $this->cronMsg(basename(__FILE__) ." done.");

    }
}

