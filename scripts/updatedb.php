#!/usr/bin/env php
<?php

function printHelp()
{
    global $argv;
    print("Usage:\t{$argv[0]} NODETYPE DBTYPE [--dry-run]\n");
    print("\n");
    print("\tNODETYPE must be one of: office, lane\n");
    print("\tDBTYPE must be one of: op, trans, arch\n");
}


function updateOfficeDB($dbtype, $dryrun)
{
    include_once(dirname(__FILE__) . '/../fannie/classlib2.0/FannieAPI.php');

    $config = FannieConfig::factory();
    $db_name = normalize_db_name($config, $dbtype);
    $updates = [];

    // loop thru all known data models
    $models = FannieAPI::listModules('BasicModel');
    foreach($models as $class) {

        // we only want models for current $dbtype
        $model = new $class(null);
        if ($model->preferredDB() == $dbtype) {

            // first check if model needs any changes
            $changes = $model->normalize($db_name, BasicModel::NORMALIZE_MODE_CHECK);
            if ($changes === false) {
                print("ERROR: something went wrong checking model: $class\n");
            } else if ($changes > 0) {
                $updates[] = $class;

                if ($dryrun) {
                    print("NEEDS AN UPDATE: $class\n");

                } else { // apply changes to the model
                    $changes = $model->normalize($db_name, BasicModel::NORMALIZE_MODE_APPLY, true);
                    if ($changes === false) {
                        print("ERROR: something went wrong updating model: $class\n");
                    }
                }
            }
        }
    }

    if ($updates) {
        if ($dryrun) {
            print("\nMODELS IN NEED OF UPDATE:\n");
        } else {
            print("\nMODELS WERE UPDATED:\n");
        }
        print("-------------------------\n");
        foreach ($updates as $class) {
            print("$class\n");
        }

    } else { // no updates
        if ($dryrun) {
            print("\nNO MODELS IN NEED OF UPDATE\n");
        } else {
            print("\nNO MODELS WERE UPDATED\n");
        }
    }
}


// TODO: this was copied from fannie/install/InstallUpdatesPage.php
// but was modified; should refactor somehow so they share logic?
function normalize_db_name($config, $name)
{

    if ($name == 'op') {
        return $config->get('OP_DB');
    } elseif ($name == 'trans') {
        return $config->get('TRANS_DB');
    } elseif ($name == 'arch') {
        return $config->get('ARCHIVE_DB');
    // } elseif (substr($name, 0, 7) == 'plugin:') {
    //     $settings = $config->get('PLUGIN_SETTINGS');
    //     $pluginDB = substr($name, 7);
    //     return isset($settings[$pluginDB]) ? $settings[$pluginDB] : false;
    }

    return false;
}


function updateLaneDB($dbtype, $dryrun)
{
    print("TODO: lane updates not yet supported\n");
}


if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {

    //////////////////////////////
    // validate args
    //////////////////////////////

    if ($argc < 3) {
        printHelp();
        exit(1);
    }

    $nodetype = $argv[1];
    if (!in_array($nodetype, ['office', 'lane'])) {
        printHelp();
        exit(1);
    }

    $dbtype = $argv[2];
    if (!in_array($dbtype, ['op', 'trans', 'arch'])) {
        printHelp();
        exit(1);
    }

    if ($nodetype == 'lane' && $dbtype == 'arch') {
        print("ERROR: there is no 'arch' db for 'lane' nodes\n\n");
        printHelp();
        exit(1);
    }

    $dryrun = false;
    if ($argc > 3) {
        if ($argc == 4 && $argv[3] == '--dry-run') {
            $dryrun = true;
        } else {
            printHelp();
            exit(1);
        }
    }

    //////////////////////////////
    // update db
    //////////////////////////////

    if ($nodetype == 'office') {
        updateOfficeDB($dbtype, $dryrun);

    } else { // nodetype == lane
        updateLaneDB($dbtype, $dryrun);
    }
}
