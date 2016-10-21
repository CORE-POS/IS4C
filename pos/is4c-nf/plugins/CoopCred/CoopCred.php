<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto

    This file is part of IT CORE.

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

use COREPOS\pos\plugins\Plugin;

/** Coop Cred - Debit accounts for members.
*/
class CoopCred extends Plugin
{

    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and,
      upon enablement of the plugin, written to ini.php
        and from then on maintained there.
    */
    public $plugin_settings = array(
        'CoopCredLaneDatabase' => array(
            'label'=>'LaneDatabase',
            'default'=>'coop_cred_lane',
            'description'=>'Database on lanes to store tables of plugin-specific
                    Coop Cred related info.
                    <br />Can be one of the default CORE databases or a separate one.
                    <br />The name of a new database should be all lower-case.
                    <br />The name must not be the same as the Coop Cred Server Database.'
        )
    );

    public $plugin_description =
        'Plugin for charging purchases to Coop Cred,
        a system of debit accounts for members
        for purchases at the Co-op.';

    /* 22Jul14 On lane this isn't supported yet
     *          and this code isn't runnable lane-side.
     */
    public function settingChange()
    {
        global $FANNIE_ROOT, $FANNIE_PLUGIN_SETTINGS;

        $news = array();

        // empty/absent if the plugin isn't enabled.
        if (empty($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase'])) {
            return;
        }
        $db_name = $FANNIE_PLUGIN_SETTINGS['CoopCredDatabase'];

        /* Check for problems in settings
         * Needs to be a way to report this back.
         * Should abort on some kinds of problems.
         */
        if ($FANNIE_PLUGIN_SETTINGS['CoopCredLaneDatabase'] ==
            $FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']) {
            $msg="***ERROR: CoopCredDatabase and CoopCredLaneDatabase are the same: " .
                $FANNIE_PLUGIN_SETTINGS['CoopCredLaneDatabase'];
            $news[]=$msg;
            /* Should abort.
             * to $INSTALL/InstallPluginsPage.php
             * return $news;
             */
            echo "<br />$msg";
            return;
        }

        // Creates the database if it doesn't already exist.
        $dbc = FannieDB::get($db_name);
        
        /* Will be created from models/~ if they don't exist
         *  but will not be touched if they do,
         *   neither re-created or modified.
         */
        $tables = array(
            'CCredMemberships',
            'CCredPrograms'
        );

        foreach($tables as $t){
            $model_class = $t.'Model';
            if (!class_exists($model_class))
                include_once(dirname(__FILE__).'/models/'.$model_class.'.php');
            $instance = new $model_class($dbc);
            $try = $instance->create();        
            if ($try) {
                $msg="Created table specified in {$model_class}";
                $dbc->logger("$msg");
            } else {
                $msg="Failed to create table specified in {$model_class}";
                $dbc->logger("$msg");
            }
            /* Generate the accessor function code for each column.
            */
            $try = $instance->generate(dirname(__FILE__).'/models/'.$model_class.'.php');
            if ($try) {
                $dbc->logger("[Re-]Generated $model_class accessor functions.");
            } else {
                $dbc->logger("Failed to [re-]generate $model_class accessor functions.");
            }
        }
    // settingChange()
    }

    public function plugin_transaction_reset(){
        global $CORE_LOCAL;

        $dbc = CoopCredLib::ccDataConnect();
        if ($dbc === False) {
            return False;
        }
        if (!$dbc->table_exists("CCredPrograms")) {
            $dbc->logger("Table CCredPrograms does not exist.");
            return False;
        }
        $q = "SELECT MAX(programID) maxpid FROM CCredPrograms";
        $s = $dbc->prepare("$q");
        $r = $dbc->execute($s,array());
        $maxPID = 0;
        foreach($r as $row) {
            $maxPID = $row['maxpid'];
            break;
        }

        if ($maxPID > 0) {
            $ccNames = array(
                'programID',
                'programName',
                'paymentDepartment',
                'paymentName',
                'paymentKeyCap',
                'tenderType',
                'tenderName',
                'tenderKeyCap',
                'memChargeTotal',
                'availBal',
                'availCreditBalance',
                'chargeTotal',
                'paymentTotal',
                'balance',
                'creditBalance'
                );
            if ($CORE_LOCAL->get('CCredProgramCode') != '') {
                $CORE_LOCAL->set('CCredProgramCode','');
            }
            if ($CORE_LOCAL->get('CCredProgramID') != '') {
                $CORE_LOCAL->set('CCredProgramID',0);
            }
            if ($CORE_LOCAL->get('CCredTendersUsed') != '') {
                $CORE_LOCAL->set('CCredTendersUsed','');
            }
            if ($CORE_LOCAL->get('CCredDepartmentsUsed') != '') {
                $CORE_LOCAL->set('CCredDepartmentsUsed','');
            }
            /* E.g. "CCred3programName"
             */
            for($pid=1 ; $pid <= $maxPID ; $pid++) {
                foreach($ccNames as $name) {
                    $var = "CCred{$pid}$name";
                    if (is_numeric($CORE_LOCAL->get("$var"))) {
                        //$varVal = $CORE_LOCAL->get("$var");
                        //$dbc->logger("Set $var of >{$varVal}< to 0.");
                        $CORE_LOCAL->set("$var",0);
                    } elseif ($CORE_LOCAL->get("$var") != '') {
                        //$varVal = $CORE_LOCAL->get("$var");
                        //$dbc->logger("Set $var of >{$varVal}< to ''.");
                        $CORE_LOCAL->set("$var",'');
                    } else {
                        //$dbc->logger("For $var do nothing.");
                        1;
                    }
                }
            }
            /* Check that no CCred session vars are other than 0/"".
             */
            $sss=0;
            foreach (array_keys($_SESSION) as $key) {
                if (preg_match("/^CCred/",$key)) {
                    // If it's not '' and not 0
                    if ($CORE_LOCAL->get("$key")) {
                        $sss++;
                        $dbc->logger("Still $key >" . $CORE_LOCAL->get("$key") . '<');
                    }
                }
            }
            //$dbc->logger("sss: $sss");
        }

        return True;

    // plugin_transaction_reset()
    }


// class CoopCred
}

