<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto

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

class CoopCredUpdateBalanceTask extends FannieTask
{
    public $name = 'Coop Cred Update Lane Balance';
    public $pluginName = 'CoopCred';

    /* Keep lines to 60 chars for the cron manager popup window.
     *                             --------------  60 edge-v */
    public $description = '
- Updates Coop Cred member balances on each lane
  based on activity today.

- Updates Coop Cred Member records on each lane
  that have changed on Fannie today.
- Does not add Coop Cred Member records.

- Updates Coop Cred Program records on each lane
  that have changed on Fannie today.

Should be run frequently during store open hours,
perhaps every five minutes.

See also: Coop Cred History Task
';    

    public $default_schedule = array(
        'min' => 0,
        'hour' => 0,
        'day' => 1,
        'month' => 1,
        'weekday' => '*',
    );

    public function __construct() {
        $this->description = $this->name . "\n" . $this->description;
        //parent::__construct();
    }

    public function run()
    {
        global $FANNIE_LANES;
        global $FANNIE_PLUGIN_LIST;
        global $FANNIE_PLUGIN_SETTINGS;
        if (!FanniePlugin::isEnabled($this->pluginName)) {
            echo $this->cronMsg("Plugin '{$this->pluginName}' is not enabled.");
            return False;
        }
        if (
            !array_key_exists("{$this->pluginName}Database", $FANNIE_PLUGIN_SETTINGS) ||
            empty($FANNIE_PLUGIN_SETTINGS["{$this->pluginName}Database"])
        ) {
            echo $this->cronMsg("Setting: '{$this->pluginName}Database' is not set.");
            return False;
        }
        $server_db = $FANNIE_PLUGIN_SETTINGS["{$this->pluginName}Database"];

        $dbc = FannieDB::get($server_db);
        if ($dbc === False) {
            echo $this->cronMsg("Unable to connect to {$server_db}.");
            return False;
        }

        // get balances that changed today
        $balanceData = array();
        $fetchQ = "SELECT programID, cardNo, balance
                    FROM CCredMemCreditBalance
                    WHERE mark=1";
        //orig: $fetchQ = "SELECT CardNo, balance FROM memChargeBalance WHERE mark=1";
        $fetchR = $dbc->query($fetchQ);
        if ($fetchR === False) {
            echo $this->cronMsg("Fatal: $fetchQ");
            flush();
            return;
        }
        // Make a list of updates.
        while ($fetchW = $dbc->fetch_row($fetchR)) {
            $key = $fetchW['programID'] . '|' .  $fetchW['cardNo'];
            // The order of elements as needed in the UPDATE statement.
            $balanceData["$key"] = array(
                $fetchW['balance'],
                $fetchW['programID'],
                $fetchW['cardNo']
            );
        }
        /* Debug
        echo $this->cronMsg("Balance updates to do: " . count($balanceData));
         */

        /* Get CCredMemberships that have changed today.
         * Does not handle Members added today.
         */
        $memberData = array();
        $memberQ = "SELECT programID, cardNo, creditLimit, maxCreditBalance,
                    creditOK, inputOK, transferOK, modified
                    FROM CCredMemberships
                    WHERE date(modified) = date(" . $dbc->now() . ")";
        $memberR = $dbc->query($memberQ);
        if ($memberR === False) {
            echo $this->cronMsg("Failed: $memberQ");
            $errors = True;
        } else {
            while ($memberW = $dbc->fetch_row($memberR)) {
                $key = $memberW['programID'] . '|' .  $memberW['cardNo'];
                // The order of elements as needed in the UPDATE statement.
                $memberData["$key"] = array(
                    $memberW['creditLimit'],
                    $memberW['maxCreditBalance'],
                    $memberW['creditOK'],
                    $memberW['inputOK'],
                    $memberW['transferOK'],
                    $memberW['modified'],
                    $memberW['programID'],
                    $memberW['cardNo']
                );
            }
        }
        /* Debug
        echo $this->cronMsg("Member updates to do: " . count($memberData));
         */

        /* Get CCredPrograms that have changed today.
         * Does not handle Programs added today.
         */
        $programData = array();
        $programQ = "SELECT programID, active, startDate, endDate,
                        creditOK, inputOK, transferOK, maxCreditBalance,
                        modified
                    FROM CCredPrograms
                    WHERE date(modified) = date(" . $dbc->now() . ")";
        $programR = $dbc->query($programQ);
        if ($programR === False) {
            echo $this->cronMsg("Failed: $programQ");
            $errors = True;
        } else {
            while ($programW = $dbc->fetch_row($programR)) {
                $key = $programW['programID'];
                // The order of elements as needed in the UPDATE statement.
                $programData["$key"] = array(
                    $programW['active'],
                    $programW['startDate'],
                    $programW['endDate'],
                    $programW['creditOK'],
                    $programW['inputOK'],
                    $programW['transferOK'],
                    $programW['maxCreditBalance'],
                    $programW['modified'],
                    $programW['programID']
                );
            }
        }
        /* Debug
        echo $this->cronMsg("Program updates to do: " . count($programData));
         */

        $errors = False;
        // connect to each lane and update balances
        foreach($FANNIE_LANES as $lane){
            $dbL = new SQLManager($lane['host'],$lane['type'],$lane['op'],$lane['user'],$lane['pw']);
            if ($dbL === False) {
                echo $this->cronMsg("Can't connect to lane: ".$lane['host'] .
                " db: {$lane['op']} .");
                $errors = True;
                continue;
            }
            /* Find the name of the CoopCred db on the lane.
             * Why is PluginList in opdata.lane_config but PluginSettings isn't?
             * opdata.parameters has PluginList, CoopCredLaneDatabase
             */
            $coopCredEnabled = 0;
            $laneDB = "";
            $laneQ = "SELECT * FROM parameters
                WHERE param_key IN ('PluginList', 'CoopCredLaneDatabase')
                ORDER BY param_key, store_id, lane_id";
            $laneR = $dbL->query($laneQ);
            if ($laneR === False) {
                echo $this->cronMsg("Failed query on: ".$lane['host'] .
                " query: $query");
                $errors = True;
                continue;
            }
            /* Local values will override global. */
            while ($laneP = $dbL->fetch_row($laneR)) {
                if ($laneP['param_key'] == 'PluginList') {
                    $paramList = explode(',', $laneP['param_value']);
                    if (in_array($this->pluginName, $paramList)) {
                        $coopCredEnabled = 1;
                    }
                }
                if ($laneP['param_key'] == 'CoopCredLaneDatabase') {
                    $laneDB = $laneP['param_value'];
                }
            }
            if (!$coopCredEnabled) {
                echo $this->cronMsg("{$this->pluginName} is not enabled on: ".$lane['host']);
                continue;
            }
            if ($laneDB == '') {
                echo $this->cronMsg("No CoopCredDatabase named on: " . $lane['host']);
                continue;
            }

            // Change db on connection to the ccred db.
            $ccDB = $dbL->addConnection($lane['host'],$lane['type'],$laneDB,
                $lane['user'],$lane['pw']);
            if ($ccDB === False){
                echo $this->cronMsg("Can't add connection to $laneDB on: ".$lane['host']);
                $errors = True;
                continue;
            }
            $dbL->default_db = $laneDB;

            foreach($balanceData as $dt) {
                $upQ = "UPDATE CCredMemberships
                    SET creditBalance=?,
                    modified=" . $dbc->now() .",
                    modifiedBy=9998
                    WHERE programID=? AND cardNo=?";
                $upS = $dbL->prepare($upQ);
                $upR = $dbL->execute($upS,$dt);
                
                if ($upR === False) {
                    echo $this->cronMsg("Balance update failed: member: {$dt[2]} ".
                        "in  program {$dt[1]} on lane: {$lane['host']}");
                    $errors = True;
                }
                /* Debug
                else {
                    echo $this->cronMsg("Balance update OK: member: {$dt[2]} in  program {$dt[1]} on lane: {$lane['host']}");
                }
                 */
            }

            foreach($memberData as $dt) {
                $upQ = "UPDATE CCredMemberships
                    SET creditLimit=?,
                    maxCreditBalance=?,
                    creditOK=?,
                    inputOK=?,
                    transferOK=?,
                    modified=?,
                    modifiedBy=9999
                    WHERE programID=? AND cardNo=?";
                $upS = $dbL->prepare($upQ);
                $upR = $dbL->execute($upS,$dt);
                
                if ($upR === False) {
                    echo $this->cronMsg("Member update failed: member: {$dt[7]} in  program {$dt[6]} on lane: {$lane['host']}");
                    $errors = True;
                }
                /* Debug
                else {
                    echo $this->cronMsg("Member update OK: member: {$dt[7]} ".
                        "in  program {$dt[6]} on lane: {$lane['host']}");
                }
                 */
            }

            foreach($programData as $dt) {
                $upQ = "UPDATE CCredPrograms
                    SET active=?,
                    startDate=?,
                    endDate=?,
                    creditOK=?,
                    inputOK=?,
                    transferOK=?,
                    maxCreditBalance=?,
                    modified=?,
                    modifiedBy=9999
                    WHERE programID=?";
                $upS = $dbL->prepare($upQ);
                $upR = $dbL->execute($upS,$dt);
                
                if ($upR === False) {
                    echo $this->cronMsg("Program update failed: program {$dt[8]} ".
                        "on lane: {$lane['host']}");
                    $errors = True;
                }
                /* Debug
                else {
                    echo $this->cronMsg("Program update OK: program {$dt[8]} ".
                        "on lane: {$lane['host']}");
                }
                 */
            }

        // each lane
        }

        if ($errors) {
            echo $this->cronMsg("There was an error pushing balances to the lanes.");
            flush();
        }
        else {
            /* Debug
             echo $this->cronMsg("All OK.");
             */
            $noop=0;
        }

    // /run
    }

// /class
}

