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

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/** Coop Cred - Debit accounts for members.
*/
class CoopCred extends FanniePlugin
{

    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and,
      upon enablement of the plugin, written to ini.php
        and from then on maintained there.
    */
    public $plugin_settings = array(
        'CoopCredDatabase' => array(
            'label'=>'Database',
            'default'=>'coop_cred',
            'description'=>'Database to store tables of plugin-specific
                    Coop Cred related info.
                    <br />Can be one of the default CORE databases or a separate one.
                    <br />The name of a new database should be all lower-case.
                    <br /><b>It must exist before the plugin is enabled.'
        )
        , 'CoopCredLaneDatabase' => array(
            'label'=>'LaneDatabase',
            'default'=>'coop_cred_lane',
            'description'=>'Database on lanes to store tables of plugin-specific
                    Coop Cred related info.
                    <br />Can be one of the default CORE databases or a separate one.
                    <br />The name of a new database should be all lower-case.
                    <br />The name must not be the same as the Coop Cred Server Database.'
        )
        /*
         * Probably not offer this option in production.
         * Developer: expose this if you need it.
        , 'CoopCredDropAllTables' => array(
            'label'=>'Re-create Tables',
            'default' => False,
            'description'=>'Erase (drop) each existing table and re-create an empty one.
                    <br />This erases any existing data and CANNOT BE UNDONE.
                    <br />If you do this, be sure to un-tick this option and "Save" again.'
                )
         */
        , 'CoopCredDropAllViews' => array(
            'label'=>'Re-create Views',
            'default' => False,
            'description'=>'Erase (drop) each existing view and re-create it.
                    <br />This does not erase any existing data.'
        )
    );

    public $plugin_description =
        'Plugin for maintaining Coop Cred,
        a system for endowing and managing debit accounts for members
        for purchases at the Co-op.
        <br /><b>N.B. At the moment it is necessary to create the database
        <code>coop_cred</code> BEFORE you enable the plugin.</b>';

    public function settingChange()
    {
        global $FANNIE_ROOT, $FANNIE_PLUGIN_SETTINGS;

        /* Would like to accumulate problems here
         *  and return.
         */
        $news = array();

        /* empty/absent if the plugin isn't enabled. */
        if (empty($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase'])) {
            return;
        }
        $db_name = $FANNIE_PLUGIN_SETTINGS['CoopCredDatabase'];
        $dropAllTables =
            (array_key_exists('CoopCredDropAllTables',$FANNIE_PLUGIN_SETTINGS)) ?
                $FANNIE_PLUGIN_SETTINGS['CoopCredDropAllTables'] : False;

        /* Check for problems in settings.
         */
        if ($FANNIE_PLUGIN_SETTINGS['CoopCredLaneDatabase'] ==
            $FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']) {
            $msg="***ERROR: CoopCredDatabase and CoopCredLaneDatabase are the same: " .
                $FANNIE_PLUGIN_SETTINGS['CoopCredLaneDatabase'];
            $news[]=$msg;
            echo "<br />$msg";
            return;
            /* Any advantage to?:
            if (!empty($news)) {
                return $news;
            } else {
                return True;
            }
             * caller $INSTALL/InstallPluginsPage.php cannot currently handle.
             */
        }

        // Creates the database if it doesn't already exist.
        $dbc = FannieDB::get($db_name);
        
        /* The tables named in the models will be created
         *  if they don't exist
         *  but will not be touched if they do,
         *   neither be re-created or modified.
         */
        $models = array(
            'CCredMembershipsModel'
            ,'CCredProgramsModel'
            ,'CCredHistoryModel'
            ,'CCredHistoryBackupModel'
            ,'CCredHistorySumModel'
            ,'CCredConfigModel'
        );

        foreach($models as $model_class){
            $filename = dirname(__FILE__).'/models/'.$model_class.'.php';
            if (!class_exists($model_class)) {
                include_once($filename);
            }
            $instance = new $model_class($dbc);
            $table = $instance->name();
            if ($dbc->tableExists($table)) {
                $msg="Table $table named in {$model_class} already exists. No change.";
                $news[] = $msg;
                $dbc->logger("$msg");
                continue;
            }
            $try = $instance->create();        
            if ($try) {
                $msg="Created table $table as specified in {$model_class}";
                $dbc->logger("$msg");
            } else {
                $msg="Failed to create table specified in {$model_class}";
                $dbc->logger("$msg");
            }
            /* Generate the accessor function code for each column.
             * The Model file must be writable by the webserver user.
            */
            if (is_writable($filename)) {
                $try = $instance->generate($filename);
                //$try = $instance->generate(dirname(__FILE__).'/models/'.$model_class.'.php');
                if ($try) {
                    //echo "Generated $model_class accessor functions\n";
                    $dbc->logger("[Re-]Generated $model_class accessor functions.");
                } else {
                    //echo "Failed to generate $model_class functions\n";
                    $dbc->logger("Failed to [re-]generate $model_class accessor functions.");
                }
            } else {
                $dbc->logger("Could not [re-]generate $model_class accessor functions " .
                    "because the model-file is not writable by the webserver user.");
            }
        // tables
        }
        
        /* Will be created from models/~ if they don't exist
         *  or dropped and re-created if they do.
         *  because some of them depend on changes in CCredPrograms
         *  that will occur as the plugin is used.
         */
        $models = array(
            array('name' => 'CCredHistoryTodayModel', 'drop' => True)
            ,array('name' => 'CCredHistoryTodaySumModel', 'drop' => True)
            ,array('name' => 'CCredLiveBalanceModel', 'drop' => True)
            ,array('name' => 'CCredMemCreditBalanceModel', 'drop' => True)
        );
        $dropAllViews =
            (array_key_exists('CoopCredDropAllViews',$FANNIE_PLUGIN_SETTINGS)) ?
            $FANNIE_PLUGIN_SETTINGS['CoopCredDropAllViews'] : False;

        foreach($models as $model){
            $model_class = $model['name'];
            $filename = dirname(__FILE__).'/models/vmodels/'.$model_class.'.php';
            if (!class_exists($model_class)) {
                include_once($filename);
            }
            $instance = new $model_class($dbc);
            /* #'v For views the view should be dropped if it exists
             *  because some of them depend on changes in CCredPrograms.
             *  isView() is in $conn, true only if the view exists
             */
            $view = $instance->name();
            if ($dbc->isView($view)) {
                if ($dropAllViews || $model['drop']) {
                    $try = $instance->delete();
                    if ($try) {
                        $msg="Dropped view $view prior to re-creating.";
                        $dbc->logger("$msg");
                    } else {
                        $msg="Failed to drop view $view prior to re-creating. " .
                            "Will not try to re-create.";
                        $dbc->logger("$msg");
                        continue;
                    }
                } else {
                    $msg="Not droping existing view $view prior to re-creating. " .
                        "Will not try to re-create.";
                    $dbc->logger("$msg");
                    continue;
                }
            }
            $try = $instance->create();        
            if ($try) {
                $msg="Created view $view specified in {$model_class}";
                $dbc->logger("$msg");
            } else {
                $msg="Failed to create view specified in {$model_class}";
                $dbc->logger("$msg");
            }
            /* Generate the accessor function code for each column.
             * The Model file must be writable by the webserver user.
            */
            if (is_writable($filename)) {
                $try = $instance->generate($filename);
                if ($try) {
                    $dbc->logger("[Re-]Generated $model_class accessor functions.");
                } else {
                    $dbc->logger("Failed to [re-]generate $model_class accessor functions.");
                }
            } else {
                $dbc->logger("Could not [re-]generate $model_class accessor functions " .
                    "because the model-file is not writable by the webserver user.");
            }
        // views
        }

        /* Would like to
         return True or $news.
         */

    // settingChange()
    }

// class CoopCred
}

